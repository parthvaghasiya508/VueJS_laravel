<?php

namespace App\Support;

use DateTimeZone;
use Illuminate\Support\Carbon;
use SimpleXMLElement;

class XmlElement extends SimpleXMLElement {

  public const OTA_NAMESPACE = 'http://www.opentravel.org/OTA/2003/05';
  private const XML_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';
  private const ROOT_ATTRIBUTES = [
    'xmlns'         => self::OTA_NAMESPACE,
    'Version'       => '3.40',
    'PrimaryLangID' => 'en',
    'Target'        => 'Production',
    'AltLangID'     => 'All',
  ];
  private const ROOT_COLLAB_ATTRIBUTES = [
    'PrimaryLangID' => 'en',
  ];
  private const ROOT_MAPPINGS_ATTRIBUTES = [
    'SenderModule' => 'Mapping',
    'Version'      => '1.00',
    'Target'       => 'Test',
  ];
  private const RESP_VERSION = [
    'ReqRespVersion' => '3.42',
  ];

  /**
   * Creates root XML element of the given name.
   *
   * @param string $element
   * @param callable|null $func [optional] <p>This function will be called after element creation,
   * and the element with context will passed as arguments.</p>
   *
   * @return XmlElement <p>Created root element</p>
   */
  static function createRoot($element, callable $func = null)
  {
    $isCollab = strpos($element, '!') === 0;
    $isMappings = !$isCollab && strpos($element, 'Cultuzz') === 0;
    if ($isCollab) $element = substr($element, 1);
    $attrs = $isCollab ? self::ROOT_COLLAB_ATTRIBUTES : ($isMappings ? self::ROOT_MAPPINGS_ATTRIBUTES : self::ROOT_ATTRIBUTES);
    $root = (new static(self::XML_HEADER.'<'.$element.'/>'))->addAttributes(
      $attrs + [
        'TimeStamp' => self::xmlDateTime(),
      ]
    );

    if ($func) {
      $func($root);
    }
    return $root;
  }

  /**
   * Adds <code>ReqRespVersion</code> attribute to the root element.
   *
   * @return $this
   */
  public function respVersion()
  {
    $this->addAttributes(self::RESP_VERSION);
    return $this;
  }

  /**
   * Adds attributes to the element.
   *
   * @param array $attributes
   *
   * @return $this
   */
  public function addAttributes($attributes)
  {
    foreach($attributes as $name => $value) {
      $this->addAttribute($name, $value);
    }
    return $this;
  }

  /**
   * Adds element with given attributes and/or content, and calls wrapper function passing created element as argument,
   * allowing to chain creation multiple elements on the same level.
   *
   * @param string $element <p>Element name</p>
   * @param array|string|callable|null $attributes [optional] <p>Element attributes</p>
   * @param string|callable|null $content [optional] <p>Element content</p>
   * @param callable|null $wrap [optional] <p>Wrapper function</p>
   *
   * @return XmlElement <p>Added element</p>
   */
  public function add(string $element, $attributes = null, $content = null, callable $wrap = null): XmlElement
  {
    if(is_callable($attributes) && !is_string($attributes)) {
      $wrap = $attributes;
      $attributes = null;
    }
    if(!is_array($attributes)) {
      $content = $attributes;
      $attributes = null;
    }
    if(is_callable($content) && !is_string($content)) {
      $wrap = $content;
      $content = null;
    }
    /** @var self $child */
    $child = $this->addChild($element);
    if($content) {
      $child[0] = (string)$content;
    }
    if($attributes) {
      $child->addAttributes($attributes);
    }

    if(isset($wrap)) {
      $wrap($child);
    }
    return $child;
  }

  /**
   * Runs XPath query and returns first element
   *
   * @param string $path <p>An XPath path</p>
   * @param mixed $default <p>Return value if nothing found, or found an empty list</p>
   *
   * @return XmlElement|mixed|null
   */
  public function first($path, $default = null)
  {
    $res = $this->xpath($path);
    if (!isset($res) || !count($res)) return $default;
    return $res[0];
  }

  /**
   * @param string $path <p>An XPath path</p>
   *
   * @return XmlElement[]|false
   */
  public function xpath($path)
  {
    return parent::xpath($path);
  }

  /**
   * Returns <b>true</b> if attribute value is <b>"1"</b>/<b>"True"</b>/<b>"true"</b>, and <b>false</b> otherwise
   *
   * @param string $attr <p>Attribute name</p>
   *
   * @return bool
   */
  public function boolAttr($attr)
  {
    $v = (string)$this[$attr];
    return in_array(strtolower($v), ['1', 'true']);
  }

  /**
   * Returns string representation of an attribute value
   *
   * @param string $attr <p>Attribute name</p>
   * @param mixed $default <p>Return value if the attribute is not present</p>
   *
   * @return mixed
   */
  public function stringAttr($attr, $default = null)
  {
    $v = $this[$attr];
    return isset($v) ? (string)$v : $default;
  }

  /**
   * Returns numeric representation of an attribute value
   *
   * @param string $attr <p>Attribute name</p>
   * @param mixed $default <p>Return value if the attribute is not present</p>
   *
   * @return mixed
   */
  public function intAttr($attr, $default = null)
  {
    $v = $this[$attr];
    return isset($v) ? (int)$v : $default;
  }

  /**
   * Returns datetime representation, suitable for XML (in UTC timezone)
   *
   * @param Carbon|null $datetime [optional] <p>If omitted, current time will be used.</p>
   *
   * @return string
   */
  static function xmlDateTime(Carbon $datetime = null)
  {
    $timezone = new DateTimeZone('UTC');
    $dt = $datetime ?? Carbon::now($timezone);
    return $dt->format('Y-m-d\TH:i:s');
  }

  /**
   * Returns date representation, suitable for XML (in UTC timezone)
   *
   * @param Carbon|\Carbon\Carbon|null $datetime [optional] <p>If omitted, current date will be used.</p>
   *
   * @return string
   */
  static function xmlDate(Carbon $datetime = null)
  {
    $timezone = new DateTimeZone('UTC');
    $dt = $datetime ?? Carbon::now($timezone);
    return $dt->format('Y-m-d');
  }

}
