<?php

namespace App\Managers;

use App\Lib\Cultuzz;
use App\Models\Currency;
use App\Models\Hotel;
use App\Models\RoomImage;
use App\Models\User;
use App\Services\RoomDB\RoomDBParser;
use App\Support\HighOrderCacheProxy;
use App\Support\PMSError;
use App\Support\XmlElement;
use Carbon\CarbonInterval;
use Error;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use HTMLPurifier;
use HTMLPurifier_Config;
use App\Services\RoomDB\RoomDBService;

class PMSManager {

  private $config;
  private $debug = false;
  private $logger;
  private $xmllog = [];
  private $xmllogfilename = '';
  private $purifyConfig;
  private $htmlPurifier;

  private ?User $user = null;
  private ?Hotel $hotel = null;

  private $weekdays = [
    'Mon', 'Tue', 'Weds', 'Thur', 'Fri', 'Sat', 'Sun',
  ];
  static private $weekdaysMap = [
    'Mon' => 'Mon',
    'Tue' => 'Tue',
    'Wed' => 'Weds',
    'Thu' => 'Thur',
    'Fri' => 'Fri',
    'Sat' => 'Sat',
    'Sun' => 'Sun',
  ];
  static private $amountModes = [
    'amount' => 'Amount',
    'percent' => 'Percent',
  ];
  static private $timeUnits = [
    'hour' => 'Hour',
    'day' => 'Day',
    'week' => 'Week',
    'month' => 'Month',
    'year' => 'Year',
  ];
  static private $dropTimes = [
    'BeforeArrival' => 'BeforeArrival',
    'AfterBooking' => 'AfterBooking',
    'AfterConfirmation' => 'AfterConfirmation',
  ];
  static private $basisTypes = [
    'Nights' => 'Nights',
    'FullStay' => 'FullStay',
  ];
  static public $langs = [
    'en', 'de', 'tr', 'fr', 'ru', 'zh',
    'pt', 'it', 'es', 'pl', 'nl', 'ro',
  ];
  static public $bgarants = [
    '1' => ['text' => 'None', 'title' => 'No warranty', 'desc' => 'No credit card is needed to complete reservation and confirm the transaction.'],
    '2' => ['text' => 'GuaranteeRequired', 'title' => '', 'desc' => ''],
    '3' => ['text' => 'CC/DC/Voucher', 'title' => 'Credit card guarantee', 'desc' => 'Credit card is requested to complete reservation and confirm the transaction. Advanced payment may be needed.'],
    '4' => ['text' => 'Profile', 'title' => '', 'desc' => ''],
    '5' => ['text' => 'Deposit', 'title' => '', 'desc' => ''],
    '6' => ['text' => 'PrePay', 'title' => '', 'desc' => ''],
    '7' => ['text' => 'OnArrival', 'title' => '', 'desc' => ''],
    '8' => ['text' => 'OnDeparture', 'title' => '', 'desc' => ''],
  ];
  static public $meals = [
    '1'  => 'All inclusive',
    '2'  => 'American',
    '3'  => 'Bed and breakfast',
    '4'  => 'Buffet breakfast',
    '5'  => 'Caribbean breakfast',
    '6'  => 'Continental plan',
    '7'  => 'English breakfast',
    '8'  => 'European plan',
    '9'  => 'Family plan',
    '10' => 'Full Board',
    '11' => 'Full breakfast',
    '12' => 'Half Board',
    '13' => 'As brochured',
    '14' => 'Room only',
    '15' => 'Self catering',
    '16' => 'Bermuda',
    '17' => 'Dinner bed and breakfast plan',
    '18' => 'Family American',
  ];
  static public $defaultBookingGueranteeIDs = [1, 3];
  static public $defaultPaymentTypeIDs = [5, 6];

  const VALIDITY_UNLIMITED_YEARS = 5;
  const DEFAULT_CONTRACTORS = [
    'promo' => 'ExtranetAutoPromos',
    'contract' => 'ExtranetAutoContracts',
  ];


  /**
   * @var RoomDBService
   */
  protected $roomDbService;

  /**
   * @var RoomDBParser
   */
  protected $roomDbParser;

  public function __construct(array $config)
  {
    $this->config = $config;
    $this->logger = config('logging.channels.slack.url') ? Log::channel('pms_slack') : Log::channel('pms');

    $this->purifyConfig = HTMLPurifier_Config::createDefault();
    $this->purifyConfig->set('HTML.AllowedElements', ['ul', 'ol', 'li', 'p', 'strong', 'em', 'b', 'i', 'u', 'span']);
    $this->purifyConfig->set('HTML.AllowedAttributes', ['style']);
    $this->purifyConfig->set('CSS.AllowedProperties', ['color', 'background-color']);
    $this->purifyConfig->set('URI.Disable', true);
    $this->htmlPurifier   = new HTMLPurifier($this->purifyConfig);
    $this->roomDbService  = new RoomDBService();
    $this->roomDbParser   = new RoomDBParser();
  }

  /**
   * Enables debug log
   *
   * @param int|null $hotelID
   *
   * @return self
   */
  public function debug($hotelID = null)
  {
    $this->debug = true;
    if (isset($hotelID)) {
      /** @var Hotel $hotel */
      $hotel = Hotel::query()->find($hotelID);
      $user = $hotel->user;
      $this->setCredentials($hotel, $user);
    }
    return $this;
  }

  /**
   * Returns cache proxy that calls target method and caches response for future calls.
   *
   * @param int $ttl <p>TTL in seconds</p>
   *
   * @return HighOrderCacheProxy|self
   */
  public function cached(int $ttl = 30)
  {
    return new HighOrderCacheProxy($this, $ttl);
  }

  private function startXMLLog()
  {
    $this->xmllog = [];
    $this->xmllogfilename = Str::random(32);
  }

  private function flushXMLLog()
  {
    $this->xmllog = [];
  }

  private function saveXMLLog()
  {
    if (!$this->xmllogfilename || !$this->xmllog) return;
    Storage::disk('xmllog')->put($this->xmllogfilename.'.log', implode("\n", $this->xmllog)."\n");
    $this->flushXMLLog();
  }

  /**
   * @param XmlElement|string $message
   */
  private function logXML($message)
  {
    if ($message instanceof XmlElement) {
      $_ = new \DOMDocument('1.0');
      $_->loadXML($message->asXML());
      $_->formatOutput = true;
      $message = $_->saveXML();
      $_ = null;
    }
    if ($this->debug) {
      Log::channel('xmldebug')->debug($message);
    } else {
      $this->xmllog[] = $message;
    }
  }

  /**
   * @param string $func
   * @param Throwable $e
   * @param string $safeMessage
   * @param mixed|null $context
   *
   * @throws Throwable
   */
  private function throwError($func, Throwable $e, $safeMessage, $context = [])
  {
    if (is_array($context)) {
      $context = [
          'UserID'  => $this->user ? $this->user->id : '',
          'HotelID' => $this->hotel ? $this->hotel->id : '',
        ] + $context;
    }
    $message = "[$func] ".$e->getMessage();
    $ctx = $context ? ' '.(is_string($context) ? $context : json_encode($context)) : '';
    $throwSafe = true;
    $_ = $this->xmllogfilename ? ['Log ID' => $this->xmllogfilename] : [];
    if ($e instanceof PMSError) {
      if ($ctx) $_ += compact('ctx');
      $this->logger->error($message." --- ({$e->getCode()}) {$e->getTag()}", $_);
      $throwSafe = false;
    } elseif ($e instanceof Error) {
      $this->logger->error($message.$ctx, $_);
    } else {
      $this->logger->error($e);
    }
    if (!config('app.debug') && $throwSafe) {
      throw new Error($safeMessage);
    }
    throw $e;
  }

  public function setCredentials(Hotel $hotel, User $user = null)
  {
    $this->hotel = $hotel;
    $this->user = $user;
    return $this;
  }

  private function hotelCodeContext(Hotel $hotel = null)
  {
    $hotel ??= $this->hotel;
    return [
      'HotelCode' => $hotel->id,
      'HotelCodeContext' => $hotel->ctx,
    ];
  }

  /**
   * Adds <POS> xml element
   *
   * @param XmlElement $el
   * @param array|null $options
   * @param Hotel|null $hotel
   */
  private function addPOS(XmlElement $el, $options = null, Hotel $hotel = null)
  {
    if ($options instanceof Hotel) {
      $hotel = $options;
      $options = null;
    }
    $hotel ??= $this->hotel;
    $el->add('POS', function (XmlElement $el) use ($hotel, $options) {
      $collab = $options && !!Arr::get($options, 'collab', false);
      $noRequester = $options && !!Arr::get($options, 'noRequester', false);
      $auth = $options && Arr::exists($options, 'auth');
      $contracts = $options && Arr::exists($options, 'contracts');
      $suffix = $collab ? '_collab' : '';
      $el->add('Source', [
        'AgentSine'     => $this->config['agent_sine'.$suffix],
        'AgentDutyCode' => $this->config['agent_dutycode'.$suffix],
      ], function (XmlElement $el) use ($hotel, $collab, $noRequester, $contracts) {
        if(!$noRequester && !$collab) {
          $el->add('RequestorID', [
            'Type'       => '10',
            'ID'         => $hotel->id,
            'ID_Context' => $hotel->ctx,
          ]);
        }
        $el->add('BookingChannel', ['Type' => $collab || $contracts ? '7' : '4']);
      });
      if($auth) {
        $el->add('Source', function (XmlElement $el) use ($options, $contracts) {
          $el->add('BookingChannel', ['Type' => $contracts ? '7' : '4']);
          $el->add('RequestorID', [
            'Type' => '1',
            'ID'   => Arr::get($options, 'auth'),
            'URL'  => 'urn:cultuzz:cultswitch:auth:username',
          ]);
        });
      }
    });
  }

  /**
   * Returns a signature for specified content
   *
   * @param string $content
   *
   * @return string
   */
  private function sign($content)
  {
    $content = md5(preg_replace('/[\r\n\t ]+/', '', $content));
    $key = md5($this->config['signature_key']);
    return sha1($key.$content.$key);
  }

  /**
   * Formats price to always have 2 decimal digits
   *
   * @param float|string $price
   *
   * @return string
   */
  private function formatPrice($price)
  {
    return number_format(floatval($price), 2, '.', '');
  }

  /**
   * Strips unnecessary ".00" from price
   *
   * @param float|string $price
   *
   * @return string
   */
  private function trimPrice($price)
  {
    return str_replace('.00', '', $this->formatPrice($price));
  }

  static public function allWeekdaysList($xml=false)
  {
    $ret = $xml ? array_values(self::$weekdaysMap) : array_keys(self::$weekdaysMap);
    return implode(',', $ret);
  }

  static public function allAmountModesList($xml=false)
  {
    $ret = $xml ? array_values(self::$amountModes) : array_keys(self::$amountModes);
    return implode(',', $ret);
  }

  static public function allTimeUnitsList($xml=false)
  {
    $ret = $xml ? array_values(self::$timeUnits) : array_keys(self::$timeUnits);
    return implode(',', $ret);
  }

  static public function allBasisTypesList($xml=false)
  {
    $ret = $xml ? array_values(self::$basisTypes) : array_keys(self::$basisTypes);
    return implode(',', $ret);
  }

  static public function allDropTimesList($xml=false)
  {
    $ret = $xml ? array_values(self::$dropTimes) : array_keys(self::$dropTimes);
    return implode(',', $ret);
  }

  /**
   * Returns array of given date's (or the whole week) weekday representation.
   *
   * @param Carbon $date [optional] <p> If omitted, all weekdays will be returned. </p>
   * @param boolean $value <p> "True" or "False" will be set </p>
   * @return array <p> The array that could be used in <b>setAttributes</b> method.
   * <pre>
   * [ "Mon" => "True", "Tue" => "True", ... ]
   * </pre>
   * </p>
   */
  private function weekday($date, $value = null)
  {
    $wd = -1;
    if ($date instanceof Carbon) {
      $wd = $date->dayOfWeekIso - 1;
    } else {
      $value = $date;
    }
    $v = $value ? 'True' : 'False';
    $ret = [];
    foreach($this->weekdays as $k=>$day) {
      if (!~$wd || $k === $wd) {
        $ret[$day] = $v;
      }
    }
    return $ret;
  }

  /**
   * Converts normal weekday names to the ones the PMS expects
   *
   * @param string|array $days <p>Weekday name or an array of names to convert. </p>
   * @param bool $makeAttributes [optional] <p>If set to <b>true</b>, then attributes map will be returned.<br>
   * Thus, if <i>$days</i> is:
   * <pre>
   * [ "Mon", "Fri" ]
   * </pre>
   * Then result will be:
   * <pre>
   * [ "Mon" => "True", "Fri" => "True" ]
   * </pre>
   * </p>
   *
   * @param bool $fillEmpty [optional] <p>If set to <b>true</b>, then attributes map will be filled with non-present days.</p>
   * @param bool $useTrueFalse [optional] <p>Whether to use <b>"True"</b>/<b>"False"</b> or <b>"1"</b>/<b>"0"</b> for attributes values.</p>
   *
   * @return string|array|null
   */
  private function convertWeekdaysToXML($days, bool $makeAttributes = false, bool $fillEmpty = false, bool $useTrueFalse = true)
  {
    if(is_string($days)) {
      return Arr::get(self::$weekdaysMap, $days, null);
    }
    $ret = array_map(function ($d) { return self::$weekdaysMap[$d]; }, $days);
    if (!$makeAttributes) return $ret;

    $true = $useTrueFalse ? 'true' : '1';
    $false = $useTrueFalse ? 'false' : '0';
    if (!$fillEmpty) {
      return array_combine($ret, array_fill(0, count($ret), $true));
    }
    $alltrue = count($days) === 7;
    $init = $alltrue ? $true : $false;
    $_ = array_combine(array_values(self::$weekdaysMap), array_fill(0, 7, $init));
    if (!$alltrue) {
      foreach($ret as $day) {
        $_[$day] = $true;
      }
    }
    return $_;
  }

  /**
   * Returns a copy of passed array with values set to <b>"True"</b> or <b>"False"</b>
   *
   * @param array $attributes
   * @param bool $value
   *
   * @return mixed
   */
  private function setXMLWeekdaysValues($attributes, $value)
  {
    foreach($attributes as &$v) {
      $v = $value ? 'True' : 'False';
    }
    return $attributes;
  }

  private function weekdaysFromXMLAttributes(XmlElement $el)
  {
    $ret = [];
    foreach(self::$weekdaysMap as $wd => $xwd) {
      if ($el->boolAttr($xwd)) {
        $ret[] = $wd;
      }
    }
    return $ret;
  }

  /**
   * Returns Guarantee attributes to use in GuaranteePolicy element.
   *
   * @param string|int $code
   * @return array
   */
  private function guaranteeAttributesByCode($code)
  {
    if (!$code || !$o = Arr::get(self::$bgarants, $code)) {
      return [];
    }
    return [
      'GuaranteeCode' => $code,
      'GuaranteeType' => $o['text'],
    ];
  }

  /**
   * Returns MealPlan attributes to use in MealsIncluded element.
   *
   * @param string|int $code
   * @return array
   */
  private function mealsAttributesByCode($code)
  {
    if(!$code || !Arr::exists(self::$meals, $code)) {
      return [
        'Breakfast' => 'false',
      ];
    }
    return [
      'Breakfast'     => 'true',
      'MealPlanCodes' => "[$code]",
    ];
  }

  private function priceVariationFromAttributes(SimpleXMLElement $el)
  {
    $mode = ((string)$el['Code']) === '24' ? 'reduction' : 'surcharge';
    $attr = isset($el['Amount']) ? 'Amount' : 'Percent';
    return [
      $mode, [
        'mode'  => strtolower($attr),
        'value' => $this->trimPrice((string)$el[$attr]),
      ],
    ];
  }

  /**
   * @param string $date
   *
   * @return \Carbon\Carbon|Carbon
   */
  private function carbonDate($date)
  {
    return $date instanceof Carbon ? $date : Carbon::createFromFormat('Y-m-d', $date, 'UTC');
  }

  /**
   * Sends XML as request to PMS with given parameters
   *
   * @param XMLElement $xml <p>XML element to send as request</p>
   * @param array $params <p> Supported params:</p>
   * <p><code>authBasic</code> - use basic auth (used for registering hotels)</p>
   * <p><code>authSign</code> - sign request</p>
   * <p><code>noProcess</code> - do not process received response, return as raw text</p>
   * <p><code>collab</code> - send request to collaboration endpoint</p>
   * @return XmlElement|string
   * @throws Throwable
   */
  private function sendRequest(XMLElement $xml, $params = [])
  {
    $this->startXMLLog();
    $endpoint = $this->config['endpoint'];
    $content = $xml->asXML();
    $authBasic = Arr::get($params, 'authBasic', false);
    $authSign = Arr::get($params, 'authSign', false);
    $noProcess = Arr::get($params, 'noProcess', false);
    $isCollab = Arr::get($params, 'collab', false);
    $isMappings = Arr::get($params, 'mappings', false);
    $getCRates = Arr::get($params, 'getCRates', false);
    $httpParams = [];
    if($authBasic || $authSign) {
      $http = Http::asForm();
      $form_params = ['otaRQ' => $content];
      if($authBasic) {
        $http->withBasicAuth($this->config['register_token'], $this->config['auth_token']);
        $endpoint = $this->config['endpoint_basic'];
      } else {
        $form_params['secure_key'] = $this->sign($content);
      }
      $httpParams = compact('form_params');
    } elseif ($isCollab) {
      $collabPath = $collabVar = $isCollab;
      if (is_array($isCollab)) {
        [$collabPath, $collabVar] = $isCollab;
      }
      $endpoint = $this->config['endpoint_collab'].$collabPath;
      $http = Http::asForm();
      $form_params = [$collabVar => $content];
      $httpParams = compact('form_params');
    } elseif ($isMappings) {
      $endpoint = $this->config['endpoint_mappings'];
      $http = Http::asForm();
      $form_params = ['mapRQ' => $content];
      $httpParams = compact('form_params');
    } elseif ($getCRates) {
      $endpoint = $this->config['endpoint_roomrates'];
      $http = Http::contentType('text/xml');
      $httpParams = ['body' => $content];
    } else {
      $http = Http::contentType('text/xml');
      $httpParams = ['body' => $content];
    }
    $this->logXML($endpoint.' '.json_encode(compact('authBasic', 'authSign', 'isCollab', 'isMappings', 'noProcess')));
    $this->logXML('=== XML REQUEST === '.json_encode($params));
    $this->logXML($xml);
    $this->logXML('=== XML RESPONSE ===');
    $response = $http->send('post', $endpoint, $httpParams)->body();
    $this->logXML($response);
    $this->logXML('====================');
    if ($noProcess) {
      return $response;
    }
    // remove OTA xmlns to simplify xml processing
    $response = str_replace("xmlns=\"http://www.opentravel.org/OTA/2003/05\"", '', $response);

    try {
      $xml = new XmlElement($response);
    } catch (Throwable $exception) {
      $this->logXML('Malformed XML response from CultSwitch:');
      $this->saveXMLLog();
      $this->logger->error("Malformed XML response from CultSwitch:\n$response\n----------");
      throw new Error("CultSwitch service unavailable");
    }
    $ok = count($xml->xpath('//Success'));
    if(!$ok) {
      $error = $xml->first('//Error');
      if (!$error) {
        $this->logXML('Unknown XML response from CultSwitch:');
        $this->saveXMLLog();
        $this->logger->error("Unknown XML response from CultSwitch:\n$response\n----------");
        throw new Error("CultSwitch service error");
      }
      $this->saveXMLLog();
      throw new PMSError($error);
    }
    $this->flushXMLLog();
    return $xml;
  }

  /**
   * Send REST Requests
   *
   * @param $headers
   * @param $method
   * @param $payload
   * @param array $params
   * @return array|mixed
   */
  private function sendRestRequest($headers, $method, $payload, $params = [])
  {
    $endpoint = null;
    $getRooms = Arr::get($params, 'getRooms', false);
    $getChannelsHealth = Arr::get($params, 'getChannelsHealth', false);

    if ($getRooms) {
      $endpoint = $this->config['endpoint_rest_roomrates'];
    } else if ($getChannelsHealth) {
      $endpoint = $this->config['endpoint_rest_channelshealth'];
    }

    try {
      $response = Http::withHeaders($headers)->{$method}($endpoint, $payload);
    } catch (HttpException $e) {
      return [
        'Code' => $e->getCode(),
        'Message' => $e->getMessage()
      ];
    }

    return json_decode($response->body());
  }

  static public function defaultBookingGuarantees()
  {
    return array_map(function ($id) {
      $o = self::$bgarants[$id];
      return compact('id') + $o;
    }, self::$defaultBookingGueranteeIDs);
  }

  static public function defaultPaymentTypes()
  {
    return array_map(function ($id) {
      $o = self::$bgarants[$id];
      return compact('id') + $o;
    }, self::$defaultPaymentTypeIDs);
  }

  static public function defaultCancelTypes()
  {
    return array_map(function ($id) {
      $o = self::$bgarants[$id];
      return compact('id') + $o;
    }, self::$defaultPaymentTypeIDs);
  }

  private function extractRoomTypes(XmlElement $xml, $addImages = false, $short = false)
  {
    $xp = !$short
      ? '//RatePlans/RatePlan[not(@RatePlanID)]/SellableProducts/SellableProduct[@InvTypeCode="3"]/../..'
      : '//RatePlans/RatePlan[not(@RatePlanID)]/UniqueID[@ID_Context="ProductElementList"]/../SellableProducts/SellableProduct[@InvTypeCode="3"]';
    $_rooms = $xml->xpath($xp);
    return array_map(function ($room) use ($addImages, $short) {
      return $this->parseRoomType($room, $addImages, $short);
    }, $_rooms);
  }

  public function getRooms($withImages = false, $short = false)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($short) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos')
        ->add('ContentInfo', ['Name' => 'ProductElement'.($short ? 'List' : '')]);
    });

    try {
      $res = $this->sendRequest($xml);
      return $this->extractRoomTypes($res, $withImages, $short);
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch rooms.");
    }
  }

  public function getProductsCount($getPlanIDs = false)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) {
          $el->add('ContentInfo', ['Name' => 'ProductList']);
          $el->add('ContentInfo', ['Name' => 'ProductElementList']);
        });
    });

    try {
      $res = $this->sendRequest($xml);
      $types = count($res->xpath('//SellableProducts/SellableProduct[@InvTypeCode="3"]'));
      $plans = count($res->xpath('//RatePlans/RatePlan[@RatePlanCategory="1"]'));
      $ret = compact('types', 'plans');
      if ($getPlanIDs) {
        $ids = array_map(fn ($el) => (string)$el[0], $res->xpath('//RatePlans/RatePlan[@RatePlanCategory="1"]/@RatePlanID'));
        $ret += compact('ids');
      }
      return $ret;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch products count.");
    }
  }

  public function getRoomType($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos')
        ->add('ContentInfo', ['Name' => 'ProductElement', 'Code' => Arr::get($payload, 'pid')]);
    });

    try {
      $res = $this->sendRequest($xml);
      if (Arr::exists($payload, '_debug')) {
        return $res->asXML();
      }
      $plan = $res->first('//RatePlans/RatePlan');
      return $this->parseRoomType($plan);
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch rooms.", $payload);
    }
  }

  private function parseRoomType(XmlElement $plan, $addImages = false, $short = false)
  {
    $product = !$short ? $plan->first('.//SellableProduct[@InvTypeCode="3"]') : $plan;
    if (!$product) return null;
    $guestRoom = $product->first('./GuestRoom');
    $room = $guestRoom->Room;
    $ocp = $guestRoom->Quantities;
    $pid = (string)$product['InvCode'];
    $item = [
      'id'        => (string)$room['RoomID'],
      'pid'       => $pid,
      'text'      => (string)$guestRoom->Description['Name'],
    ];
    if (!$short) {
      $bookingRule = $plan->first(".//BookingRule/UniqueID[@ID=$pid]/..");
      $stdO = (int)$ocp['StandardOccupancy'];
      $minO = (int)$bookingRule['MinTotalOccupancy'] ?? $stdO;
      $maxO = (int)$bookingRule['MaxTotalOccupancy'] ?? $stdO;
      $item += [
        'typecode'  => (string)$room['RoomTypeCode'],
        'type'      => (string)$room['RoomType'],
        'amount'    => (int)$room['Quantity'],
        'protected' => (string)$product['InvCodeApplication'] === 'DoesNotApply',
        'occupancy' => [
          'std' => $stdO,
          'min' => $minO,
          'max' => $maxO,
        ],
        'langs'     => [],
      ];
      $descriptions = $guestRoom->first('./Description');
      foreach(['9' => 'name', '7' => 'desc'] as $x => $k) {
        foreach(self::$langs as $lang) {
          $_ = $descriptions->first("./ListItem[@ListItem='$x'][@Language='$lang']");
          $item['langs'][$lang][$k] = $_ ? trim((string)$_) : '';
        }
      }
      $item += $this->extractValidities($plan, true);
      if ($addImages) {
        $item += [
          'images' => RoomImage::imagesForRoom($pid),
        ];
      }
    }
    return $item;
  }

  public function modifyRoomType($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ratePlanAttributes = [
        'RatePlanType' => '11',
      ];

      $isCopy = Arr::exists($payload, '_copy');
      if ($isCopy) {
        $payload = $this->getRoomType($payload);
        $isCopy = false;
        Arr::forget($payload, ['id', 'pid', '_copy']);
        foreach(self::$langs as $lang) {
          foreach(['name', 'desc'] as $v) {
            $k = "langs.{$lang}.{$v}";
            if ($_ = Arr::get($payload, $k)) {
              Arr::set($payload, $k, '[COPY] '.$_);
            }
          }
        }
      }
      $isDelete = Arr::exists($payload, '_delete');
      $isUpdate = Arr::exists($payload, 'pid');
      $isCreate = !$isUpdate;
      if($isDelete) {
        $ratePlanAttributes['RatePlanNotifType'] = 'Remove';
      }
      $el->add('RatePlans')
        ->add('RatePlan', $ratePlanAttributes, function (XmlElement $el) use ($payload, $isCreate, $isCopy, $isDelete, $isUpdate) {
          $sp = null;
          $el->add('SellableProducts', function (XmlElement $el) use ($payload, &$sp, $isCreate, $isDelete) {
            $sp = $el->add('SellableProduct', [
              'IsRoom'          => 'true',
              'InvTypeCode'     => '3',
              'InvNotifType'    => $isCreate ? 'New' : 'Overlay',
              'InvCode'         => Arr::get($payload, 'pid'),
              'InvStatusType'   => 'Active',
            ]);
            if (!$isCreate) {
              $sp->add('UniqueID', [
                'Type'       => '18',
                'ID_Context' => $isDelete ? 'CltzCommonProductElement' : 'CltzProductElement',
                'ID'         => Arr::get($payload, 'pid'),
              ]);
            }
          });
          if ($isCopy || $isDelete) return;
          $el->add('BookingRules')
            ->add('BookingRule', [
              'MinTotalOccupancy' => Arr::get($payload, 'occupancy.min') ?: Arr::get($payload, 'occupancy.std'),
              'MaxTotalOccupancy' => Arr::get($payload, 'occupancy.max') ?: Arr::get($payload, 'occupancy.std'),
              'PriceViewable'     => 'false',
              'QualifiedRateYN'   => 'false',
            ])
            ->add('UniqueID', [
              'Type'       => '18',
              'ID_Context' => 'CltzProductElement',
              'ID'         => Arr::get($payload, 'pid'),
            ]);
          $sp->add('GuestRoom', function (XmlElement $el) use ($payload, $isCreate) {
            $el->add('Amenities');
            $el->add('Room', [
              'Quantity'     => Arr::get($payload, 'amount'),
              'RoomID'       => Arr::get($payload, 'id'),
              'RoomTypeCode' => Arr::get($payload, 'typecode'),
            ]);
            if ($isCreate) {
              $initprice = $this->formatPrice(Arr::get($payload, 'initprice', 0));
              $el->add('RoomLevelFees')
                ->add('Fee', ['Amount' => $initprice]);
            }
            $el->add('Quantities', [
              'MaxOccupancy'      => Arr::get($payload, 'occupancy.max') ?: Arr::get($payload, 'occupancy.std'),
              'StandardOccupancy' => Arr::get($payload, 'occupancy.std'),
            ]);
            // titles/descriptions
            $el->add('Description', ['Name' => Arr::get($payload, 'langs.en.name')], function (XmlElement $el) use ($payload) {
              foreach(['9' => 'name', '7' => 'desc', '8' => 'desc'] as $x => $k) {
                foreach(self::$langs as $lang) {
                  if ($_ = (Arr::get($payload, "langs.$lang.$k") ?? '')) {
                    $el->add('ListItem', [
                      'Language' => $lang,
                      'ListItem' => $x,
                    ], $_);
                  }
                }
              }
              // update room images
              $images = RoomImage::imagesForRoom(Arr::get($payload, 'pid'), false);
              $images->each(function (RoomImage $image) use ($el) {
                $el->add('ListItem', [
                  'Language' => 'en',
                  'ListItem' => '6',
                ], $image->image->url);
              });
            });
          });
          $sp->add('Description', ['Name' => Arr::get($payload, 'langs.en.name')]);
          $el->add('Rates', function (XmlElement $el) use ($payload) {
            // min price
            $el->add('Rate', function (XmlElement $el) use ($payload) {
              $el->add('UniqueID', [
                'ID_Context' => 'CltzInventoryLowestPrice',
                'ID'         => Arr::get($payload, 'pid'),
                'Type'       => '18',
              ]);
              $el->add('Fees')
                ->add('Fee', ['Amount' => '0.00']);
            });
            // validity
            foreach(Arr::get($payload, 'validity', []) as $key => $validity) {
              $from = $this->carbonDate(Arr::get($validity, 'from'));
              if(Arr::get($validity, 'unlim')) {
                $until = $from->clone()->addYears(self::VALIDITY_UNLIMITED_YEARS);
              } else {
                $until = $this->carbonDate(Arr::get($validity, 'until'));
              }
              $el->add('Rate', [
                'Start'  => $el::xmlDate($from),
                'End'    => $el::xmlDate($until),
                'Status' => 'Open',
                'InvCode' => Arr::get($payload, 'pid'),
              ], function (XmlElement $el) use ($payload) {
                $el->add('UniqueID', [
                  'ID_Context' => 'CltzProductElementValidity',
                  'ID'         => 'new',
                  'Type'       => '18',
                  'Instance'   => '1',
                ]);
                $el->add('GuaranteePolicies')
                  ->add('GuaranteePolicy', $this->guaranteeAttributesByCode(1));
              });
            }
            // blockouts
            foreach(Arr::get($payload, 'blockouts', []) as $key => $blockout) {
              $from = $this->carbonDate(Arr::get($blockout, 'from'));
              $until = $this->carbonDate(Arr::get($blockout, 'until'));
              $el->add('Rate', [
                'Start'  => $el::xmlDate($from),
                'End'    => $el::xmlDate($until),
                'Status' => 'Close',
              ], function (XmlElement $el) {
                $el->add('UniqueID', [
                  'ID_Context' => 'CltzProductElementValidity',
                  'ID'         => 'new',
                  'Type'       => '18',
                ]);
                $el->add('GuaranteePolicies')
                  ->add('GuaranteePolicy', $this->guaranteeAttributesByCode(1));
              });
            }
          });
        });
    });

    try {
//      return $xml->asXML();
      $res = $this->sendRequest($xml);
      $pid = (string)$res->first('//RatePlanCrossRefs/RatePlanCrossRef[@ResponseRatePlanGroupingCode="CltzProductElementID"]/@ResponseRatePlanCode');
      $isCopy = Arr::exists($payload, '_copy');
      $isCreate = !Arr::exists($payload, 'pid');
      if ($isCreate || $isCopy) {
        // fetch newly created/copied room type
        return $this->getRoomType(compact('pid'));
      }
      // update/delete calls returns success
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process room type.", $payload);
    }
  }

  public function getRoomsData($fromDate = null, $toDate = null, $todayPrices = false)
  {
    if (!isset($fromDate) && !isset($toDate) && !$todayPrices) {
//      throw new Error('Invalid params in .getRoomsData');
    }
    $xml = XmlElement::createRoot('OTA_HotelAvailRQ', function (XmlElement $el) use ($fromDate, $toDate, $todayPrices) {
      $this->addPOS($el);
      $el->add('AvailRequestSegments', function (XmlElement $el) use ($fromDate, $toDate) {
        $from = isset($fromDate) ? Carbon::createFromFormat('Y-m-d', $fromDate, 'UTC') : Carbon::today('UTC');
        $to = isset($toDate, $fromDate) ? Carbon::createFromFormat('Y-m-d', $toDate, 'UTC') : $from->clone();
        $to->addDay();
        $d1 = $from->format('Y-m-d');
        $d2 = $to->format('Y-m-d');
        $el->add('AvailRequestSegment', ['ResponseType' => 'RateInfoDetails'], function (XmlElement $el) use ($d1, $d2) {
          $el->add('StayDateRange', ['Start' => $d1, 'End' => $d2]);
          $el->add('RoomStayCandidates')
            ->add('RoomStayCandidate')
            ->add('GuestCounts')
            ->add('GuestCount', ['AgeQualifyingCode' => '10', 'Count' => '100']);
        });
      });
    });

    try {
      $res = $this->sendRequest($xml);

      $rooms = collect([]);
      $roomRates = $res->xpath('//RoomStay//RoomRate');
      $currency = null;
      foreach($roomRates as $roomRate) {
        $rid = (string)$roomRate['RoomTypeCode'];
        $rates = $roomRate->Rates->xpath('./Rate');
        foreach($rates as $rate) {
          $cc = (string)$rate->Base['CurrencyCode'];
          if (is_null($currency)) $currency = $cc;
          $room = [
            'i'      => (string)$rate['EffectiveDate'],
            'minlos' => (int)$rate['MinLOS'],
            'maxlos' => (int)$rate['MaxLOS'],
            'avail'  => (int)$rate['NumberOfUnits'],
            'price'  => $this->trimPrice($rate->Base['AmountAfterTax']),
            'cc'     => $cc,
            'csale'  => !((int)$rate['Duration']),
            'carr'   => false,
            'cdep'   => false,
          ];
          $desc = $rate->xpath('.//ListItem');
          foreach($desc as $listitem) {
            $val = !(int)$listitem['ListItem'];
            switch(trim((string)$listitem)) {
              case 'ArrivalDayAvail':
                $room['carr'] = $val;
                break;
              case 'DepartureDayAvail':
                $room['cdep'] = $val;
                break;
            }
          }
          if($_ = $rooms->get($rid)) {
            $_->add($room);
          } else {
            $rooms[$rid] = collect([$room]);
          }
        }
      }
      $rooms = $rooms->mapWithKeys(function (Collection $items, $rid) use ($todayPrices) {
        $items = $items->sortBy('i')->values();
        return [$rid => !$todayPrices ? $items : $items[0]['price']];
      });
      return compact('rooms', 'currency');
    } catch(Throwable $e) {
      $context = compact('fromDate', 'toDate', 'todayPrices');
      $this->throwError(__FUNCTION__, $e, "Failed to fetch rooms data.", $context);
    }
  }

  public function updateRoomAvailability($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelInvCountNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('Inventories')->add('Inventory', function (XmlElement $el) use ($payload) {
        $end = Carbon::createFromFormat('Y-m-d', $payload['day'], 'UTC')->addDay()->format('Y-m-d');
        $el->add('StatusApplicationControl', [
          'Start'   => $payload['day'],
          'End'     => $end,
          'InvCode' => $payload['id'],
          'IsRoom'  => '1',
        ]);
        $el->add('InvCounts')->add('InvCount', [
          'CountType' => '2',
          'Count'     => $payload['data']['avail'],
        ]);
      });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update availability.", $payload);
    }
  }

  public function updateRoomData($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRateAmountNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('RateAmountMessages', $this->hotelCodeContext())
        ->add('RateAmountMessage', function (XmlElement $el) use ($payload) {
          $start = Carbon::createFromFormat('Y-m-d', $payload['day'], 'UTC');
          $end = $start->clone()->addDay()->format('Y-m-d');
          $el->add('StatusApplicationControl', [
            'Start'        => $payload['day'],
            'End'          => $end,
            'InvCode'      => $payload['id'],
            'IsRoom'       => '1',
            'RatePlanType' => '13',
            'Override'     => '1',
          ]);
          $el->add('Rates', function (XmlElement $el) use ($payload, $start) {
            $id = $payload['id'];
            $data = $payload['data'];
            $price = number_format(floatval($data['price']), 2, '.', '');
            $el->add('Rate')
              ->add('BaseByGuestAmts')
              ->add('BaseByGuestAmt', [
                'DecimalPlaces'  => '2',
                'CurrencyCode'   => $this->getCurrencyCodeBasedOnPayload($this->getHotel($this->hotel)),
                'AmountAfterTax' => $price,
              ]);
            $el->add('Rate', ['MinLOS' => $data['minlos'], 'MaxLOS' => $data['maxlos']] + $this->weekday(true))
              ->add('UniqueID', ['Type' => '18', 'ID' => $id]);

            $el->add('Rate', $this->weekday($start, !$data['csale']))
              ->add('RateDescription', ['Name' => 'AvailableDays']);
            $el->add('Rate', $this->weekday($start, !$data['carr']))
              ->add('RateDescription', ['Name' => 'ArrivalDays']);
            $el->add('Rate', $this->weekday($start, !$data['cdep']))
              ->add('RateDescription', ['Name' => 'DepartureDays']);
          });
        });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update price/restrictions.", $payload);
    }
  }

  private function batchUpdateRoomsAvailability($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelInvCountNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('Inventories', function (XmlElement $el) use ($payload) {
        foreach($payload['rooms'] as $room) {
          $el->add('Inventory', function (XmlElement $el) use ($payload, $room) {
            $el->add('StatusApplicationControl', [
                'Start'   => $payload['from'],
                'End'     => $payload['until'],
                'InvCode' => $room,
                'IsRoom'  => '1',
              ] + $this->convertWeekdaysToXML($payload['days'], true));
            $el->add('InvCounts')->add('InvCount', [
              'CountType' => '2',
              'Count'     => $payload['fields']['avail'],
            ]);
          });
        }
      });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update availability.", $payload);
    }
  }

  public function batchUpdateRoomsData($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRateAmountNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('RateAmountMessages', $this->hotelCodeContext(), function (XmlElement $el) use ($payload) {
        $xmlDays = $this->convertWeekdaysToXML($payload['days'], true);
        foreach($payload['rooms'] as $room) {
          $el->add('RateAmountMessage', function (XmlElement $el) use ($payload, $room, $xmlDays) {
            $el->add('StatusApplicationControl', [
              'Start'        => $payload['from'],
              'End'          => $payload['until'],
              'InvCode'      => $room,
              'IsRoom'       => '1',
              'RatePlanType' => '13',
              'Override'     => '1',
            ]);
            $el->add('Rates', function (XmlElement $el) use ($payload, $room, $xmlDays) {
              $fields = $payload['fields'];
              $price = Arr::get($fields, 'price');
              $grnt = Arr::get($fields, 'grnt');
              if ($price !== null || $grnt !== null) {
                $el->add('Rate', $xmlDays, function (XmlElement $el) use ($price, $grnt) {
                  if($price != null) {
                    $el->add('BaseByGuestAmts')
                      ->add('BaseByGuestAmt', [
                        'DecimalPlaces'  => '2',
                        'CurrencyCode'   => $this->hotel->currency_code ?? 'EUR',
                        'AmountAfterTax' => $price,
                      ]);
                  }
                  if ($grnt !== null) {
                    $el->add('GuaranteePolicies')
                      ->add('GuaranteePolicy', $this->guaranteeAttributesByCode($grnt));
                  }
                });
              }
              $los = [];
              if ($minlos = Arr::get($fields, 'minlos')) {
                $los['MinLOS'] = $minlos;
              }
              if ($maxlos = Arr::get($fields, 'maxlos')) {
                $los['MaxLOS'] = $maxlos;
              }
              if ($los) {
                $el->add('Rate', $los + $xmlDays)
                  ->add('UniqueID', ['Type' => '18', 'ID' => $room]);
              }

              if (($osale = Arr::get($fields, 'osale')) !== null) {
                $el->add('Rate', $this->setXMLWeekdaysValues($xmlDays, $osale))
                  ->add('RateDescription', ['Name' => 'AvailableDays']);
              }
              if (($carr = Arr::get($fields, 'carr')) !== null) {
                $el->add('Rate', $this->setXMLWeekdaysValues($xmlDays, !$carr))
                  ->add('RateDescription', ['Name' => 'ArrivalDays']);
              }
              if (($cdep = Arr::get($fields, 'cdep')) !== null) {
                $el->add('Rate', $this->setXMLWeekdaysValues($xmlDays, !$cdep))
                  ->add('RateDescription', ['Name' => 'DepartureDays']);
              }
            });
          });
        }
      });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update price/restrictions.", $payload);
    }
  }

  public function batchUpdateRooms($payload)
  {
    $ret = true;
    if (Arr::exists($payload['fields'], 'avail')) {
      $ret = $ret && $this->batchUpdateRoomsAvailability($payload);
      Arr::forget($payload, 'fields.avail');
    }
    if ($payload['fields']) {
      $ret = $ret && $this->batchUpdateRoomsData($payload);
    }
    return $ret;
  }

  private function parseReservation(SimpleXMLElement $reservation)
  {
    $_ = $reservation->UniqueID;
    $item = [
      'id'         => (string)$_['ID'],
      'type'       => (string)$_['Type'],
      'created_at' => $reservation['CreateDateTime'].'Z',
      'remarks'    => [],
      'profiles'   => ['guests'=> []],
    ];
    $item += [
      'ok' => in_array($item['type'], ['14', '16']),
    ];
    if(!$item['ok']) {
      $item += [
        'cancelled_at' => $reservation['LastModifyDateTime'].'Z',
      ];
    }
    $roomStay = $reservation->xpath('.//RoomStay')[0];
    $item += [
      'source' => [
        'name' => (string)$roomStay['SourceOfBusiness'],
        'id'   => 'n/a',
      ],
    ];
    $indexNumber = (string)$roomStay['IndexNumber'];
    $_ = $roomStay->TimeSpan;
    $checkin_at = (string)$_['Start'];
    $checkout_at = (string)$_['End'];
    $nights = Carbon::parse($checkout_at)->diffInDays($checkin_at);
    $item += compact('checkin_at', 'checkout_at', 'nights');
    $_ = $roomStay->Total;
    $item += [
      'total' => [
        'price' => floatval($_['AmountAfterTax']),
        'cur' => (string)$_['CurrencyCode'],
      ],
    ];
    $rooms = [];
    $pids = [];
    $roomTypes = $roomStay->xpath('.//RoomType');
    foreach($roomTypes as $rt) {
      $key = (string) $rt['RoomTypeCode'];
      if ($rt['IsRoom'] == 'false') {
        $pids[] = $key;
      } else {
        $_ = $rt->RoomDescription;
        $rooms[$key] = [
          'name' => trim($_->Text) ?: trim($rt['Name']),
          'amt' => intval($rt['NumberOfUnits']),
        ];
      }
    }
    $_ = $roomStay->xpath('./Comments/Comment/Text[text()]');
    $item['remarks']['room'] = array_map(function ($o) { return (string)$o; }, $_) ?: [];

    $roomRates = $roomStay->xpath('.//RoomRates')[0];
    $ratePlans = $roomStay->xpath('.//RatePlans')[0];
    foreach($pids as $pid) {
      $productRates = $roomRates->xpath(".//RoomRate[@RoomTypeCode='$pid']/Rates/Rate");
      # collect days data
      $days = collect();
      $price = 0;
      $cur = '';
      foreach($productRates as $pr) {
        $days->add((string)$pr['EffectiveDate']);
        $price = floatval($pr->Base['AmountAfterTax']);
        $cur = (string)$pr->Base['CurrencyCode'];
      }
      $from = $days->min();
      $till = Carbon::parse($days->max())->addDay()->format('Y-m-d');
      $pid = substr($pid, 2);
      $_ = $roomRates->xpath(".//RoomRate[@RatePlanID='$pid']")[0];
      $rid = (string)$_['RoomTypeCode'];

      $rtp = $ratePlans->xpath(".//RatePlan[@RatePlanID='$pid']")[0];
      $plan = (string) $rtp->RatePlanDescription['Name'];

      $rooms[$rid] += compact('from', 'till', 'pid', 'rid', 'price', 'cur', 'plan');
    }
    $content = collect($rooms)->sortBy('pid')->values();
    $item += compact('content');

    $resGuests = $reservation->xpath('.//ResGuests')[0];
    $_ = $resGuests->xpath('.//GuestCounts[@IsPerRoom="true"]/GuestCount[@AgeQualifyingCode="10"]/@Count');
    if (!$_) {
      $_ = $reservation->xpath('./ResGlobalInfo/GuestCounts[@IsPerRoom="false"]/GuestCount[@AgeQualifyingCode="10"]/@Count');
      $adl = intval($_[0] ?? 0);
    } else {
      $adl = array_reduce($_, function ($a, $n) { return $a + intval($n['Count']); }, 0);
    }
    $_ = $resGuests->xpath('.//GuestCounts[@IsPerRoom="true"]/GuestCount[@Age]/@Count');
    if (!$_) {
      $_ = $reservation->xpath('./ResGlobalInfo/GuestCounts[@IsPerRoom="false"]/GuestCount[@Age]/@Count');
      $cld = intval($_[0] ?? 0);
    } else {
      $cld = array_reduce($_, function ($a, $n) { return $a + intval($n['Count']); }, 0);
    }
    $amt = $adl + $cld;
    $item += [ 'guests' => compact('amt', 'adl', 'cld') ];

    $rgs = $resGuests->xpath('.//ResGuest[@ResGuestRPH]');
    foreach($rgs as $rg) {
      if ((string)$rg['GroupEventCode']) {
        // guest profile
        $item['profiles']['guests'][] = $this->extractProfile($rg);
      } else {
        // booker profile
        $item['profiles']['booker'] = $this->extractProfile($rg, $person, true);
        $item['person'] = $person;
        $item['userId'] = (string)$rg['ResGuestRPH'];
        $_ = $rg->xpath('./Comments/Comment/Text[text()]');
        $item['remarks']['booker'] = array_map(function ($o) { return (string)$o; }, $_) ?: [];
      }
    }
    $_ = $reservation->xpath('./ResGlobalInfo/Comments/Comment/Text[text()]');
    $item['remarks']['channel'] = array_map(function ($o) { return (string)$o; }, $_) ?: [];

    $_ = $reservation->xpath('./ResGlobalInfo//HotelReservationID[@ResID_SourceContext="BookingID"]');
    if (!$_) {
      $_ = $reservation->xpath('./ResGlobalInfo//HotelReservationID[@ResID_SourceContext="eBayItemID"]');
    }
    if ($_) {
      $item['source']['id'] = (string)$_[0]['ResID_Value'];
    }

    $item['notes'] = count($item['remarks']['booker']);

    return $item;
  }

  public function getReservations($payload)
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('ReadRequests')
        ->add('ReadRequest')
        ->add('Verification')
        ->add('TPA_Extensions', function (XmlElement $el) use ($payload) {
          $el->add('LimitsInfo', [
            'FromIndex' => (Arr::get($payload, 'resultPerPage', 50) * (Arr::get($payload, 'currentPage', 1) - 1)),
            'ToIndex'   => ((Arr::get($payload, 'resultPerPage', 50) * Arr::get($payload, 'currentPage', 1)) - 1),
          ]);
          $el->add('Date', [
            'DateType' => $payload['type'],
            'Start'    => $payload['from'],
            'End'      => $payload['until'],
          ]);
        });
    })->respVersion();

    try {
      $res = $this->sendRequest($xml);
      $reservations = $res->xpath('//ReservationsList/HotelReservation');
      $maxRes = $res->xpath('//ReservationsList/parent::*/@MaxResponses');
      $count = $maxRes ? $maxRes[0]['MaxResponses'] : 0;
      $list = collect();
      foreach($reservations as $reservation) {
        $list[] = $this->parseReservation($reservation);
      }

      if (count($list)) {
        $result = [
          'list' => $list,
          'total' => (int) $count
        ];
      }else{
        $result = [
          'list' => [],
          'total' => 0,
        ];
      }
      return $result;
    } catch(Throwable $e) {
      if ($e->getMessage() == 'Server Error') {
        return [
          'list' => [],
          'total' => 0
        ];
      } else {
        $this->throwError(__FUNCTION__, $e, "Failed to get reservations.", $payload);
      }
    }
  }

  public function getReservation($payload)
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('ReadRequests')
        ->add('ReadRequest')
        ->add('UniqueID', [
          'ID_Context' => 'CltzBookingID',
          'ID' => $payload['id'],
        ]);
    })->respVersion();

    try {
      $res = $this->sendRequest($xml);
      $reservations = $res->xpath('//ReservationsList/HotelReservation');
      return $reservations ? $this->parseReservation($reservations[0]) : null;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get reservation.", $payload);
    }
  }

  private function extractProfile(SimpleXMLElement $el, &$person = null, $booker = false)
  {
    $acc = [];
    $xp = $booker ? './/Profile[@ProfileType="1"]/Customer' : './/Profile/Customer';
    if (!$prof = $el->xpath($xp)) {
      return [];
    }
    $profile = $prof[0];
    if ($personName = $profile->PersonName) {
      $firstName = (string)$personName->GivenName;
      $lastName = (string)$personName->Surname;
      $nameTitle = (string)$personName->NameTitle;

      $person = trim("$firstName $lastName");
      $acc[] = trim("$nameTitle $person");
    }

    $telephone = '';
    if ($_ = $profile->Telephone) {
      $telephone = trim((string)$_['PhoneNumber']);
    }
    $email = trim((string)$profile->Email);

    $xp = $booker ? './Address[@FormattedInd="false"]' : './Address';
    if ($address = $profile->xpath($xp)) {
      $adr = $address[0];
      $acc[] = trim((string)$adr->StreetNmbr);
      $acc[] = trim((string)$adr->PostalCode.' '.(string)$adr->CityName);
      $acc[] = $telephone;
      $acc[] = $email;
      $_ = trim((string)$adr->CompanyName);
      $acc[] = "Company Name: ".($_ ?: "n/a");
      $_ = $adr->CountryName;
      $cc = 'n/a';
      $cn = 'n/a';
      if($_) {
        if($__ = trim((string)$_)) {
          $cn = $__;
        }
        if($__ = trim((string)$_['Code'])) {
          $cc = $__;
        }
      }
      $acc[] = "Country Name: $cn";
      $acc[] = "Country Code: $cc";
    } else {
      $acc[] = $telephone;
      $acc[] = $email;
    }
    return array_values(array_filter($acc));
  }

  public function cancelReservation($payload)
  {
    $xml = XmlElement::createRoot('OTA_CancelRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $noshow = $payload['noshow'];
      $attrs = [
        'ID_Context' => $this->hotel->ctx,
        'Type'       => $noshow ? '18' : '15',
        'ID'         => $payload['id'],
      ];
      if ($noshow) {
        $attrs += [
          'URL' => 'urn:cultuzz:cultswitch:xml:request:cancelrq:noshow',
        ];
      }
      $el->add('UniqueID', $attrs);
      $el->add('UniqueID', [
        'ID_Context' => $this->hotel->ctx,
        'Type'       => '10',
        'ID'         => $this->hotel->id,
      ]);
      $el->add('TPA_Extensions')
        ->add('Reasons')
        ->add('Reason', [
          'Language' => 'en',
          'Type'     => $payload['reason'],
        ]);
    })->addAttributes(['CancelType' => $payload['noshow'] ? 'Modify' : 'Cancel']);

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to cancel reservations.", $payload);
    }
  }

  private function extractCancelPolicies(SimpleXMLElement $el)
  {
    $policies = $el->xpath('//RatePlan/UniqueID[@ID_Context="CancelPolicyList"]/..//CancelPenalty');
    return array_map(function(XmlElement $p) {
      $amountPercent = $p->xpath('.//AmountPercent')[0];
      $apValue = $amountPercent['Amount'];
      $apMode = 'amount';
      if (!$apValue) {
        $apValue = $this->trimPrice($amountPercent['Percent']);
        $apMode = 'percent';
      }
      $deadline = $p->xpath('.//Deadline')[0];
      $item =  [
        'id' => (string)$p['PolicyCode'],
        'langs' => [],
        'cancellationFee' => [
          'value' => trim((string)$apValue),
          'mode' => $apMode,
          'nmbrOfNights' => (int)$amountPercent['NmbrOfNights'],
          'basisType' => (string)$amountPercent['BasisType'],
        ],
        'cancellationTime' => [
          'unitMultiplier' => (int)$deadline['OffsetUnitMultiplier'],
          'timeUnit' => array_search (trim((string)$deadline['OffsetTimeUnit']), self::$timeUnits),
          'dropTime' => trim((string)$deadline['OffsetDropTime']),
        ],
        'protected' => (string)$p['ConfirmClassCode'] === 'SystemCancelPolicy',
      ];
      foreach(['txt:name' => 'name', 'txt:description_long' => 'desc'] as $x => $k) {
        $_ = $p->xpath("./PenaltyDescription[@Name='$x']")[0] ?? null;
        foreach (self::$langs as $lang) {
          $__ = $_ ? $_->xpath("./Text[@Language='$lang']") : null;
          $item['langs'][$lang][$k] = $__ ? ((string)$__[0]) ?? '' : '';
        }
        if ($_ && ($__ = $_->first('./Text[not(@Language)]')) !== null) {
          $item['langs']['en'][$k] = (string)$__;
        }
      }

      return $item;
    }, $policies);
  }

  private function extractValidities(XmlElement $plan, $multipleValidity = false)
  {
    $ret = [
      'validity'  => [],
      'blockouts' => [],
    ];
    $_ = $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductValidity" or @ID_Context="CltzProductElementValidity"]/..');
    foreach($_ as $validity) {
      try {
        $from = (string)$validity['Start'];
        $until = (string)$validity['End'];
        $status = (string)$validity['Status'];
        if ($status === 'Open') {
          $unlim = Carbon::createFromFormat('Y-m-d', $from, 'UTC')->diffInYears(Carbon::createFromFormat('Y-m-d', $until, 'UTC')) >= self::VALIDITY_UNLIMITED_YEARS;
          if ($multipleValidity) {
            $ret['validity'][] = compact('from', 'until', 'unlim');
          } else {
            $ret['validity'] = compact('from', 'until', 'unlim');
          }
        } else {
          $ret['blockouts'][] = compact('from', 'until');
        }
      } catch (Throwable $_) {
        // something's wrong in CS response
        // just ignore malformed element
      }
    }
    return $ret;
  }

  private function extractDuration(XmlElement $duration = null)
  {
    $number = '';
    $unit = 'd';
    if (isset($duration)) {
      $number = $duration->stringAttr('OffsetUnitMultiplier', '');
      $unit = $duration->stringAttr('OffsetTimeUnit', 'd');
    }
    return compact('number', 'unit');
  }

  /**
   * Get channels health
   *
   * @return array|array[]|void
   * @throws Throwable
   */
  public function getChannelsHealth()
  {
    $headers = [
      'API-KEY' => $this->config['channels_health_api_key']
    ];

    try {
      $channelsHealth = $this->sendRestRequest($headers, 'get', [], ['getChannelsHealth' => true]);

      return compact('channelsHealth');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get channels health.");
    }
  }

  /**
   * Get Room and Plans for the channel
   *
   * @param $channelId
   * @param $objectId
   * @return array|void
   * @throws Throwable
   */
  public function getRoomAndPlans($channelId, $objectId)
  {
    $payload = [
      "channel_id" => (int)$channelId,
      "object_id" => $objectId,
      "search_parameters" => [
        "getMappings"
      ]
    ];

    $headers = [
      'X-API-KEY' => $this->config['xapi_key']
    ];

    try {
      $res = $this->sendRestRequest($headers, 'post', $payload, ['getRooms' => true]);
      $plans = [];
      $rooms = [];
      if (isset($res->products)) {
        $rooms = array_column(array_map(function ($product) {
          if (isset($product->room) && isset($product->room->id)) {
            return [
              'id' => $product->room->id,
              'text' => $product->room->name ?? null,
            ];
          }
          return [];
        }, $res->products ?: []), null, 'id');

        $rooms = array_values(array_filter($rooms, function($a) {return $a !== null;}));

        $plans = array_map(function ($product) {
          if (isset($product->category)) {
            if ($product->category == 1 && isset($product->room->id)) {
              return [
                'id' => $product->id ?? null,
                'room' => $product->room->id,
                'text' => $product->name ?? null
              ];
            }
          }
          return [];
        }, $res->products ?: []);

        $plans = collect(array_values(array_filter($plans, function($a) {return $a !== null;})));
      }
      return compact('rooms', 'plans');
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch room types and rate plans.", $res);
    }
  }

  public function getRoomTypesAndRatePlans($fullRates = false, $withPromoMode = false)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) {
          $el->add('ContentInfo', ['Name' => 'Product']);
          $el->add('ContentInfo', ['Name' => 'ProductElementList']);
        });
    })->respVersion();

    $contract = $withPromoMode ? $this->getAutoContractors($this->config['default_pull_channel']) : null;

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $rooms = $this->extractRoomTypes($res, false, true);
      $plans = $this->extractRatePlans($res, !$fullRates, Arr::get($contract, 'codes'));
      return compact('rooms', 'plans');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch room types and rate plans.");
    }
  }

  public function getRatePlansWithExtraData($plansOnly = false)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) {
          $el->add('ContentInfo', ['Name' => 'AllPoliciesList']);
          $el->add('ContentInfo', ['Name' => 'Product']);
          $el->add('ContentInfo', ['Name' => 'ProductElement']);
        });
    })->respVersion();

    $contract = $this->getAutoContractors($this->config['default_pull_channel']);

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $cancels = $this->extractCancelPolicies($res);
      $meals = $this->extractMeals($res);
      $rooms = $this->extractRoomTypes($res);
      $policies = $this->extractPaymentPolicies($res, true);
      // get today prices for rooms
      $_ = $this->getRoomsData(null, null, true);
      foreach($rooms as &$room) {
        $room['price'] = $this->formatPrice(Arr::get($_, 'rooms.'.$room['id']));
      }
      $plans = $this->extractRatePlans($res, false, Arr::get($contract, 'codes'));
      return compact('rooms', 'meals', 'cancels', 'plans', 'policies');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch rate plans.");
    }
  }

  public function createRatePlanFromRoomType($data)
  {
    $wdays    = array_keys(self::$weekdaysMap);
    $payload = [
      'room'      => Arr::get($data, 'pid'),
      'occupancy' => Arr::get($data, 'occupancy'),
      'validity'  => Arr::get($data, 'validity') ? Arr::get($data, 'validity') : [],
      'blockouts' => Arr::get($data, 'blockouts'),
      'langs'     => Arr::get($data, 'langs'),
      'minlos'    => 1,
      'maxlos'    => 999,
      'bgarant'   => 1,
      'meals'     => 0,
      'cancels'   => [],
      'bdays'     => $wdays,
      'adays'     => $wdays,
      'ddays'     => $wdays,
      'price'     => [
        'mode' => 'standard',
      ],
    ];
    return $this->modifyRatePlan($payload);
  }

  public function modifyRatePlan($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ratePlanAttributes = [
        'RatePlanType'           => '11',
        'RatePlanNotifType'      => 'New',
        'RatePlanNotifScopeType' => 'RatePlanAndRate',
        'PromotionCode'          => '0',
        'RatePlanCategory'       => '1',
        'RatePlanStatusType'     => 'Active',
      ];

      $priceVariationId = Arr::get($payload, 'id', $this->hotel->id);
      $isCopy = Arr::exists($payload, '_copy');
      $isDelete = Arr::exists($payload, '_delete');
      if($id = Arr::get($payload, 'id')) {
        $ratePlanAttributes['RatePlanID'] = $id;
        if(!$isCopy) {
          $ratePlanAttributes['RatePlanNotifType'] = $isDelete ? 'Remove' : 'Overlay';
        }
      }
      $el->add('RatePlans')
        ->add('RatePlan', $ratePlanAttributes, function (XmlElement $el) use ($payload, $isCopy, $isDelete, $priceVariationId) {
          if ($isCopy || $isDelete) return;

          $bookingRuleAttrs = [
            'MinTotalOccupancy' => Arr::get($payload, 'occupancy.min'),
            'MaxTotalOccupancy' => Arr::get($payload, 'occupancy.max'),
            'GenerallyBookable' => '1',
          ];

          // Bookable data
          $bookable = Arr::get($payload, 'bookable.mode', Cultuzz::BOOKABLE_ANYTIME);

          if ($bookable == Cultuzz::BOOKABLE_FROMTO) {
            $from = Arr::get($payload, 'bookable.from');
            $to = Arr::get($payload, 'bookable.to');
            $bookingRuleAttrs['MinAdvancedBookingOffset'] = "P".min($from, $to)."D";
            $bookingRuleAttrs['MaxAdvancedBookingOffset'] = "P".max($from, $to)."D";
          }

          if ($bookable == Cultuzz::BOOKABLE_UNTIL) {
            $bookingRuleAttrs['MinAdvancedBookingOffset'] = "P".Arr::get($payload, 'bookable.until')."D";
          }

          if ($bookable == Cultuzz::BOOKABLE_WITHIN) {
            $bookingRuleAttrs['MaxAdvancedBookingOffset'] = "P".Arr::get($payload, 'bookable.within')."D";
          }

          $el->add('BookingRules')
            ->add('BookingRule', $bookingRuleAttrs, function (XmlElement $el) use ($payload) {
              if($bgarant = Arr::get($payload, 'bgarant')) {
                $el->add('AcceptableGuarantees')
                  ->add('AcceptableGuarantee', $this->guaranteeAttributesByCode($bgarant));
              }
              if ($policy = Arr::get($payload, 'policy')) {
                if (intval($policy)) {
                  $el->add('RequiredPaymts')
                    ->add('GuaranteePayment', ['InfoSource' => $policy]);
                }
              }
              if ($cancels = Arr::get($payload, 'cancels', [])) {
                $el->add('CancelPenalties', function (XmlElement $el) use ($payload, $cancels) {
                  foreach($cancels as $code) {
                    $el->add('CancelPenalty', ['ConfirmClassCode' => 'CancelPolicy', 'PolicyCode' => $code]);
                  }
                  // $el->add('CancelPenalty', ['ConfirmClassCode' => 'NoShowPolicy', 'PolicyCode' => '1']);
                });
              }
              $el->add('DOW_Restrictions', function (XmlElement $el) use ($payload) {
                $el->add('AvailableDaysOfWeek', $this->convertWeekdaysToXML(Arr::get($payload, 'bdays', []), true, true, false));
                $el->add('ArrivalDaysOfWeek', $this->convertWeekdaysToXML(Arr::get($payload, 'adays', []), true, true, false));
                $el->add('DepartureDaysOfWeek', $this->convertWeekdaysToXML(Arr::get($payload, 'ddays', []), true, true, false));
              });
            });
          $el->add('Rates', function (XmlElement $el) use ($payload, $priceVariationId) {
            $weekdays = $this->weekday(true);
            // validity
            foreach(Arr::get($payload, 'validity', []) as $key => $validity) {
              $from = $this->carbonDate(Arr::get($validity, 'from'));
              if(Arr::get($validity, 'unlim')) {
                $until = $from->clone()->addYears(self::VALIDITY_UNLIMITED_YEARS);
              } else {
                $until = $this->carbonDate(Arr::get($validity, 'until'));
              }
              $el->add('Rate', [
                'Start'  => $el::xmlDate($from),
                'End'    => $el::xmlDate($until),
                'Status' => 'Open',
              ], function (XmlElement $el) use ($payload) {
                $el->add('UniqueID', [
                  'ID_Context' => 'CltzProductValidity',
                  'ID'         => 'new',
                  'Instance'   => '1',
                  'Type'       => '18',
                ]);
              });
            }
            // blockouts
            foreach(Arr::get($payload, 'blockouts', []) as $key => $blockout) {
              $from = $this->carbonDate(Arr::get($blockout, 'from'));
              $until = $this->carbonDate(Arr::get($blockout, 'until'));
              $el->add('Rate', [
                'Start'  => $el::xmlDate($from),
                'End'    => $el::xmlDate($until),
                'Status' => 'Close',
              ])->add('UniqueID', [
                'ID_Context' => 'CltzProductValidity',
                'ID'         => 'new',
                'Instance'   => $key + 2,
                'Type'       => '18',
              ]);
            }
            // standard occupancy
            $el->add('Rate', ['MaxGuestApplicable' => Arr::get($payload, 'occupancy.std')])
              ->add('UniqueID', [
                'Type'       => '18',
                'ID_Context' => 'CltzProductOccupancy',
                'ID'         => '1',
              ]);
            // min/max LOS
            $el->add('Rate', [
              'MinLOS' => Arr::get($payload, 'minlos'),
              'MaxLOS' => Arr::get($payload, 'maxlos'),
            ])->add('UniqueID', [
              'Type'       => '18',
              'ID_Context' => 'CltzProductLengthOfStay',
              'ID'         => '1',
            ]);
            // room element
            $el->add('Rate', ['NumberOfUnits' => '1'])
              ->add('UniqueID', [
                'Type'       => '18',
                'ID_Context' => 'CltzProductElement',
                'ID'         => Arr::get($payload, 'room'),
              ]);
            // meals
            if($meals = Arr::get($payload, 'meals')) {
              $el->add('Rate', ['NumberOfUnits' => '1'])
                ->add('UniqueID', [
                  'Type'       => '18',
                  'ID_Context' => 'CltzProductElement',
                  'ID'         => $meals,
                ]);
            }
            // price variations
            // prepare data first, to omit having empty <Rate> later
            $personSurcharge = Arr::get($payload, 'price.calc.surcharge.value');
            $personReduction = Arr::get($payload, 'price.calc.reduction.value');
            $childrenSurcharges = collect(Arr::get($payload, 'price.calc.children', []))
              ->filter(function ($li) {
                return isset($li['surcharge']['value']) && $li['surcharge']['value'] !== '';
              })
              ->sortBy('age')
              ->values();
            if ($personSurcharge || $personReduction || $childrenSurcharges) {
              $el->add('Rate', [
                'MaxGuestApplicable' => Arr::get($payload, 'occupancy.max'),
                'MinGuestApplicable' => Arr::get($payload, 'occupancy.min'),
              ], function (XmlElement $el) use ($payload, $priceVariationId, $childrenSurcharges) {
                $el->add('UniqueID', [
                  'Type'       => '18',
                  'ID_Context' => 'CltzProductVariantRules',
                  'ID'         => $priceVariationId,
                ]);
                $el->add('AdditionalGuestAmounts', function (XmlElement $el) use ($payload, $childrenSurcharges) {
                  if ($value = Arr::get($payload, 'price.calc.surcharge.value')) {
                    $attr = Arr::get($payload, 'price.calc.surcharge.mode') === 'amount' ? 'Amount' : 'Percent';
                    $el->add('AdditionalGuestAmount', [
                      'AgeQualifyingCode' => 10,
                      'Code'              => 16,
                      $attr               => $this->formatPrice($value),
                    ]);
                  }
                  if ($value = Arr::get($payload, 'price.calc.reduction.value')) {
                    $attr = Arr::get($payload, 'price.calc.reduction.mode') === 'amount' ? 'Amount' : 'Percent';
                    $el->add('AdditionalGuestAmount', [
                      'AgeQualifyingCode' => 10,
                      'Code'              => 24,
                      $attr               => $this->formatPrice($value),
                    ]);
                  }
                  if($childrenSurcharges) {
                    $minAge = 0;
                    foreach($childrenSurcharges as $surcharge) {
                      $attr = Arr::get($surcharge, 'surcharge.mode') === 'amount' ? 'Amount' : 'Percent';
                      $maxAge = Arr::get($surcharge, 'age');
                      $el->add('AdditionalGuestAmount', [
                        'Code'   => 16,
                        'MinAge' => $minAge,
                        'MaxAge' => $maxAge,
                        $attr    => $this->formatPrice(Arr::get($surcharge, 'surcharge.value')),
                      ]);
                      $minAge = $maxAge + 1;
                    }
                  }
                });
              });
            }

            // add bookable periods
            $bookable = Arr::get($payload, 'bookable.mode', Cultuzz::BOOKABLE_ANYTIME);
            if ($bookable == Cultuzz::BOOKABLE_PERIODS) {
              foreach (Arr::get($payload, 'bookable.periods', []) as $bookable) {
                $el->add('Rate', [
                  'Start'  => Arr::get($bookable, 'from'),
                  'End'    => Arr::get($bookable, 'until'),
                  'Status' => 'Open',
                ])->add('UniqueID', [
                  'Type'       => '18',
                  'ID_Context' => 'CltzProductPurchasePeriod',
                  'ID'         => 'new',
                ]);
              }
            }
          });

          $el->add('SellableProducts', function (XmlElement $el) use ($payload) {
            $el->add('SellableProduct', [
              'InvGroupingCode' => Arr::get($payload, 'room'),
              'InvTypeCode'     => '3',
            ])->add('UniqueID', [
              'Type'       => '18',
              'ID_Context' => 'CltzProductElement',
              'ID'         => Arr::get($payload, 'room'),
            ]);
            if ($meals = Arr::get($payload, 'meals')) {
              $el->add('SellableProduct', [
                'InvGroupingCode' => $meals,
                'InvTypeCode'     => '2',
              ])->add('UniqueID', [
                'Type'       => '18',
                'ID_Context' => 'CltzProductElement',
                'ID'         => $meals,
              ]);
            }
          });
          // title/description
          foreach([
            'txt:name'              => 'name',
            'txt:description_short' => 'name',
            'txt:description_long'  => 'desc',
          ] as $x => $k) {
            $el->add('Description', ['Name' => $x], function (XmlElement $el) use ($payload, $k) {
              foreach(self::$langs as $lang) {
                if ($_ = (Arr::get($payload, "langs.$lang.$k", ''))) {
                  $el->add('Text', ['Language' => $lang], $_);
                }
              }
            });
          }
          // prices
          $priceAttrs = ['Code' => '25'];
          if(Arr::get($payload, 'price.mode') === 'fixed') {
            $priceAttrs = [
              'Code'   => '11',
              'Amount' => $this->formatPrice(Arr::get($payload, 'price.fixed')),
            ];
          } else {
            $calcMode = Arr::get($payload, 'price.stdcalc.mode');
            $priceMode = Arr::get($payload, "price.stdcalc.$calcMode");
            if($value = Arr::get($priceMode, 'value')) {
              $ap = Arr::get($priceMode, 'mode') === 'percent' ? 'Percent' : 'Amount';
              $fc = $calcMode === 'surcharge' ? '16' : '24';
              $priceAttrs = [
                'Code' => $fc,
                $ap    => $this->formatPrice($value),
              ];
            }
          }
          $el->add('RatePlanLevelFee')->add('Fee', $priceAttrs);

        });
    });
    try {
      $res = $this->sendRequest($xml);
      $id = (string)$res->xpath('//RatePlanCrossRefs/RatePlanCrossRef[@ResponseRatePlanGroupingCode="CltzProductID"]/@ResponseRatePlanCode')[0];
      $isCopy = Arr::exists($payload, '_copy');
      $isCreate = !Arr::exists($payload, 'id');
      if ($isCreate || $isCopy) {
        if ($isCreate && !Arr::get($payload, '_idonly')) {
          // activate rate plan upon creation
          $this->activateRatePlan(compact('id'));
        }
        // fetch newly created/copied rate plan
        if (Arr::get($payload, '_idonly')) {
          return $id;
        }
        return $this->getRatePlan(compact('id'));
      }
      // update/delete calls returns success
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process rate plan.", $payload);
    }
  }

  public function activateRatePlan($payload)
  {
    if ($ids = Arr::get($payload, 'ids')) {
      $active = Arr::get($payload, 'active', true);
      foreach ($ids as $id) {
        $this->activateRatePlan(compact('id', 'active'));
      }
      return true;
    }
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $id = Arr::get($payload, 'id');
      $el->add('RatePlans')
         ->add('RatePlan', [
           'RatePlanType'       => '11',
           'RatePlanID'         => $id,
           'RatePlanStatusType' => Arr::get($payload, 'active', true) ? 'Active' : 'Deactivated',
         ]);
    });

    try {
      $this->sendRequest($xml);
      return true;
      // $id = (string)$res->xpath('//RatePlanCrossRefs/RatePlanCrossRef[@ResponseRatePlanGroupingCode="CltzProductID"]/@ResponseRatePlanCode')[0];
      // return $id == Arr::get($payload, 'id');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to change rate plans active status.", $payload);
    }
  }

  public function updateRatePlan($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('RatePlans')
         ->add('RatePlan', [
           'RatePlanType'       => '11',
           'RatePlanID'         => $payload['Channel Rate ID'],
           'InventoryAllocatedInd' => 'true',
           'MarketCode' => $payload['id'],
           'RatePlanNotifType' => 'Overlay'
         ], function (XmlElement $el) use ($payload) {
           $el->add('SellableProducts')
              ->add('SellableProduct', [
                'InvCode' => $payload['Channel Room Id'],
                'InvType' => $payload['Channel Room name'],
                'InvGroupingCode' => $payload['Channel Rate ID'],
                'InvStatusType' => 'Active',
              ], function (XmlElement $el) use ($payload) {
                $el->add('Description', [
                  'Name' => $payload['Channel Rate Name']
                ]);
                $el->add('UniqueID', [
                  'Type' => '18',
                  'ID_Context' => 'ProductMapping',
                  'ID' => $payload['id']
                ]);
              });
         });
    });

    try {
      $this->sendRequest($xml);
      return true;
      // $id = (string)$res->xpath('//RatePlanCrossRefs/RatePlanCrossRef[@ResponseRatePlanGroupingCode="CltzProductID"]/@ResponseRatePlanCode')[0];
      // return $id == Arr::get($payload, 'id');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to change rate plans active status.", $payload);
    }
  }

  private function extractRatePlans(XmlElement $xml, $short = false, $codes = null)
  {
    return collect($xml->xpath('//RatePlans/RatePlan[@RatePlanCategory="1"]'))
      ->map(fn ($plan) => $this->parseRatePlan($plan, $short, $codes))
      ->filter()
      ->values();
  }

  private function parseRatePlan(XmlElement $plan, $short = false, $codes = null)
  {
    $item = [
      'id' => (string)$plan['RatePlanID'],
    ];
    if (!$short) {
      $item += [
        'langs'       => [],
        'protected'   => $plan->boolAttr('InventoryAllocatedInd'),
        'active'      => ((string)$plan['RatePlanStatusType']) === 'Active',
        'occupancy'   => [],
        'price'       => [
          'stdcalc' => [
            'mode'      => 'surcharge',
            'surcharge' => [],
            'reduction' => [],
          ],
          'calc'    => [
            'children'  => [],
            'surcharge' => [
              'value' => '',
              'mode'  => 'amount',
            ],
            'reduction' => [
              'value' => '',
              'mode'  => 'amount',
            ],
          ],
        ],
        'bookable' => [
          'mode'    => Cultuzz::BOOKABLE_ANYTIME,
          'periods' => [],
          'from'    => '',
          'to'      => '',
          'until'   => '',
          'within'  => '',
        ],
        'marketcodes' => array_map(function (XmlElement $el) {
          return trim((string)$el);
        }, $plan->xpath('./DestinationSystemCodes/DestinationSystemCode')),
        'promo' => null,
      ];
      if ($_ = trim($plan->stringAttr('MarketCode', ''))) {
        $item['marketcodes'][] = $_;
        if ($plan->boolAttr('InventoryAllocatedInd')) {
          $item['promo'] = $plan->stringAttr('RatePlanCode');
          $item['promomode'] = optional($codes)->firstWhere('code', $item['promo'])['mode'] ?? null;
        }
      }
    }
    $elangs = ['txt:name' => 'name'];
    if (!$short) $elangs += ['txt:description_long' => 'desc'];
    foreach($elangs as $x => $k) {
      $_ = $plan->xpath("./Description[@Name='$x']")[0] ?? null;
      foreach(self::$langs as $lang) {
        $__ = $_ ? $_->xpath("./Text[@Language='$lang']") : null;
        $item['langs'][$lang][$k] = $__ ? ((string)$__[0]) ?? '' : '';
      }
    }
    $room = $plan->first('./SellableProducts/SellableProduct[@InvTypeCode="3"]');
    if (!isset($room)) return null;
    $item['room'] = $room->stringAttr('InvCode');
    $item['text'] = Arr::get($item, 'langs.en.name', Arr::get($item, 'langs.de.name', ''));
    if ($short) {
      Arr::forget($item, 'langs');
    } else {
      $_ = $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductOccupancy"]/../@MaxGuestApplicable');
      Arr::set($item, 'occupancy.std', intval($_[0] ?? 0));
      $_ = $plan->first('./Rates/Rate/UniqueID[@ID_Context="CltzProductLengthOfStay"]/..');
      $minlos = 1;
      $maxlos = 999;
      if (isset($_)) {
        $minlos = intval($_['MinLOS']);
        $maxlos = intval($_['MaxLOS']);
      }
      $item += compact('minlos', 'maxlos');
      $item += $this->extractValidities($plan, true);
      $elements = array_map(function ($el) { return (string)$el[0]; },
        $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductElement"]/@ID'));
      $item['meals'] = Arr::first(array_map(function ($el) { return (string)$el[0]; },
          $plan->xpath('./SellableProducts/SellableProduct['.$this->mealPlanTypesForXml().']/@InvCode'))) ?? '0';

      $brule = $plan->xpath('./BookingRules/BookingRule')[0];
      Arr::set($item, 'occupancy.min', intval($brule['MinTotalOccupancy']));
      Arr::set($item, 'occupancy.max', intval($brule['MaxTotalOccupancy']));


      // Bookable data
      $_from = $brule->stringAttr('MinAdvancedBookingOffset');
      $_to = $brule->stringAttr('MaxAdvancedBookingOffset');
      $from = isset($_from) ? CarbonInterval::make($_from) : false;
      $to = isset($_to) ? CarbonInterval::make($_to) : false;

      if ($from !== false && $to !== false) {
        Arr::set($item, 'bookable.mode', Cultuzz::BOOKABLE_FROMTO);
        Arr::set($item, 'bookable.from', (string)min($from->dayz, $to->dayz));
        Arr::set($item, 'bookable.to', (string)max($from->dayz, $to->dayz));
      } elseif ($from !== false) {
        Arr::set($item, 'bookable.mode', Cultuzz::BOOKABLE_UNTIL);
        Arr::set($item, 'bookable.until', (string)$from->dayz);
      } elseif ($to !== false) {
        Arr::set($item, 'bookable.mode', Cultuzz::BOOKABLE_WITHIN);
        Arr::set($item, 'bookable.within', (string)$to->dayz);
      }

      $periods = $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductPurchasePeriod"]/..');
      if ($periods) {
        Arr::set($item, 'bookable.mode', Cultuzz::BOOKABLE_PERIODS);
        Arr::set($item, 'bookable.periods',
          array_map(fn (XmlElement $el) => ['from' => (string)$el['Start'], 'until' => (string)$el['End']], $periods));
      }


      foreach (['bdays' => 'Available', 'adays' => 'Arrival', 'ddays' => 'Departure'] as $k => $v) {
        $_ = $brule->xpath("./DOW_Restrictions/{$v}DaysOfWeek")[0];
        $item[$k] = $this->weekdaysFromXMLAttributes($_);
      }
      $item['bgarant'] = intval($brule->xpath('./AcceptableGuarantees/AcceptableGuarantee/@GuaranteeCode')[0] ?? 0);
      $_ = $brule->xpath('./CancelPenalties/CancelPenalty[@ConfirmClassCode="CancelPolicy"]/@PolicyCode');
      $item['cancels'] = array_map(function ($_) { return (string)$_[0]; }, $_);
      $_ = (string)$brule->first('./RequiredPaymts/GuaranteePayment/@PolicyCode') ?? '0';
      $item['policy'] = $_ === '91' ? '0' : ($_ ?: '0');
      $fee = $plan->xpath('./RatePlanLevelFee/Fee')[0];
      $code = intval($fee['Code']);
      if ($code === 11) {
        Arr::set($item, 'price.mode', 'fixed');
        Arr::set($item, 'price.fixed', $this->formatPrice((string)$fee['Amount']));
      } else {
        Arr::set($item, 'price.mode', 'standard');
        if ($code !== 25) {
          $_ = $code === 16 ? 'surcharge' : 'reduction';
          $__ = isset($fee['Amount']) ? 'amount' : 'percent';
          $___ = $this->trimPrice((string)($fee['Amount'] ?? $fee['Percent']));
          Arr::set($item, 'price.stdcalc.mode', $_);
          Arr::set($item, "price.stdcalc.$_.mode", $__);
          Arr::set($item, "price.stdcalc.$_.value", $___);
        }
      }
      $prules = $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductVariantRules"]/../AdditionalGuestAmounts/AdditionalGuestAmount');
      $children = collect();
      foreach ($prules as $prule) {
        [$mode, $block] = $this->priceVariationFromAttributes($prule);
        if ((string)$prule['AgeQualifyingCode'] !== '10') {
          $children[] = [
            $mode => $block,
            'age' => intval($prule['MaxAge']),
          ];
        } else {
          Arr::set($item, "price.calc.$mode", $block);
        }
      }
      $children = $children->sortBy('age')->values();
      for ($i = count($children); $i<3; $i++) {
        $children[] = [
          'age'       => '',
          'surcharge' => ['value' => '', 'mode' => 'amount'],
        ];
      }
      $children = $children->map(function ($c, $idx) {
        $c['id'] = $idx;
        return $c;
      });
      Arr::set($item, 'price.calc.children', $children->toArray());
    }
    return $item;
  }

  public function getRatePlan($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ids = Arr::get($payload, 'ids', (array)Arr::get($payload, 'id'));
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) use ($ids) {
          foreach($ids as $id) {
            $el->add('ContentInfo', ['Name' => 'Product', 'Code' => $id]);
          }
        });
    })->respVersion();

    $single = Arr::get($payload, 'id');
    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $ret = $this->extractRatePlans($res);
      if ($single) {
        $ret = $ret->firstWhere('id', $single);
      }
      return $ret;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch rate plans.", $payload);
    }
  }

  private function extractMeals(XmlElement $xml)
  {
//    $plans = $xml->xpath('//RatePlans/RatePlan[not(@RatePlanID)]');
//    $ret = [];
//    foreach($plans as $plan) {
//      foreach($plan->xpath('.//SellableProduct[@InvTypeCode="4"]') as $node) {
//        $ret[] = [
//          'id'   => (string)$node['InvCode'],
//          'text' => (string)$node->Description['Name'],
//        ];
//      }
//    }
    $types = collect(Cultuzz::MEAL_TYPES)->pluck('text', 'id');
    $ret = collect($this->extractMealPlans($xml))->map(fn ($m) => [
      'id' => $m['id'], 'text' => $types[$m['typecode']].': '.$m['text'],
    ]);
    $ret->add([
      'id' => '0',
      'text' => 'No meals',
    ]);
    return $ret->sortBy('id')->values()->toArray();
  }

  public function getBGarant()
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('ReadRequests')
        ->add('ReadRequest')
        ->add('Verification')
        ->add('TPA_Extensions', function (XmlElement $el) {
          $el->add('LimitsInfo', ['FromIndex' => 0, 'ToIndex' => 50]);
          $el->add('UniqueID', ['ID' => 3, 'ID_Context' => 'BookingGuarantee']);
        });
    })->respVersion();

    return $this->sendRequest($xml)->asXML();
  }

  public function getRoomTypeCodes()
  {
    $xml = XmlElement::createRoot('Cultuzz_RoomTypesRQ', function (XmlElement $el) {
      $this->addPOS($el, ['collab' => true]);
      $el->add('GetRoomTypes');
    });

    try {
      $res = $this->sendRequest($xml, ['collab' => 'roomTypeCodes']);
      return array_map(function (XmlElement $el) {
        return [
          'id'        => (string)$el['RoomTypeCode'],
          'occupancy' => (int)$el['StandardOccupancy'],
          'text'      => (string)$el,
        ];
      }, $res->xpath('//RoomType'));

    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch room type codes.");
    }
  }

  public function activateHotelBooking($mode = true)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($mode) {
      $this->addPOS($el, ['auth' => optional($this->user)->id ?? 0]);
      $el->add('HotelDescriptiveContents', $this->hotelCodeContext())
        ->add('HotelDescriptiveContent', $this->hotelCodeContext() + [
            'Overwrite' => '1',
          ])
        ->add('HotelInfo')
        ->add('Services')
        ->add('Service')
        ->add('Description', [
          'ContentData' => $mode ? 'Online' : 'Offline',
          'Name'        => 'BookingService',
        ]);
    });

    try {
      $this->sendRequest($xml);
      $this->hotel->toggleActive($mode);
      return true;
    } catch(Throwable $e) {
      $action = $mode ? 'activate' : 'deactivate';
      $this->throwError(__FUNCTION__, $e, "Failed to $action hotel booking.");
    }
  }

  public function registerHotel($payload)
  {
    $country        = $this->getCountry(Arr::get($payload, 'country', Arr::get($payload, 'country_id')));
    $currency_code  = $this->getCurrencyCodeBasedOnPayload($payload);
    $payload += [
      'country'         => $country['code'],
      'country_name'    => $country['name'],
      'currency_code'   => $currency_code,
    ];
    Arr::forget($payload, ['country_id']);

    $xml = XmlElement::createRoot('OTA_ProfileCreateRQ', function (XmlElement $el) use ($payload) {
      $el->add('Profile', [
        'ProfileType'    => '12',
        'CreateDateTime' => XmlElement::xmlDateTime(),
      ], function (XmlElement $el) use ($payload) {

        $el->add('Agreements')
          ->add('AllianceConsortium')
          ->add('AllianceMember', ['MemberCode' => '52']);

        $el->add('Accesses')
          ->add('Access', [
            'ActionType' => 'Create',
            'ID'         => 'Whatever',
            //             'ID'         => $ctx->user_id,
          ]);

        $el->add('CompanyInfo', function (XmlElement $el) use ($payload) {
          $el->add('CompanyName', ['TravelSector' => '3'], Arr::get($payload, 'name'));
          $el->add('AddressInfo', ['Type' => '2', 'UseType' => '12'], function (XmlElement $el) use ($payload) {
            $el->StreetNmbr = Arr::get($payload, 'street');
            $el->CityName = Arr::get($payload, 'city');
            $el->PostalCode = Arr::get($payload, 'zip');
            $el->StateProv = 'NA';
            $el->add('CountryName', ['Code' => Arr::get($payload, 'country')], Arr::get($payload, 'country_name'));
          });
          $el->add('TelephoneInfo', [
            'FormattedInd'  => '1',
            'DefaultInd'    => '1',
            'PhoneTechType' => '1',
            'PhoneNumber'   => Arr::get($payload, 'tel'),
          ]);
          $el->Email = Arr::get($payload, 'email');
        });
      });
    });

    try {
      $res = $this->sendRequest($xml, ['authBasic' => true]);

      [$uniqueId] = $res->xpath('//UniqueID');
      $name = (string)$uniqueId->CompanyName;
      $ctx = (string)$uniqueId['ID_Context'];
      $id = (string)$uniqueId['ID'];
      $user_id = Arr::get($payload, 'user_id');
      $currency_id = 1; // hardcode for now, because we will base on currency_code
      $country = $payload['country'];
      $city = $payload['city'];
      $group_id = Arr::get($payload, 'group_id');
      $agent_id = Arr::get($payload, 'agent_id');
      if ($user_id && !$agent_id) {
        $agent_id = optional(User::query()->find($user_id))->agent_id;
      }
      $data = compact('id', 'name', 'ctx', 'user_id', 'city', 'currency_id', 'country', 'group_id', 'agent_id', 'currency_code');
      $hotel = Hotel::create($data);
      // update currency
      try {
        $this->modifyHotel(Arr::only($payload, ['lang']), $hotel);
      } catch (Throwable $e) {
        // do nothing
      }
      return $hotel;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to register hotel.", $payload);
    }
  }

  public function getHotel(
    Hotel $hotel = null,
    $debug = false
  ) {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($hotel) {
      $this->addPOS($el, $hotel);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext($hotel), function (XmlElement $el) {
          $el->add('ContactInfo', ['SendData' => 1]);
          $el->add('HotelInfo', ['SendData' => 1]);
        });
    });

    try {
      $res = $this->sendRequest($xml);
      if ($debug) return $res->asXML();
      $info = $res->first('.//HotelDescriptiveContent');
      return $this->parseHotelData($info, $hotel);
    } catch(Throwable $e) {
//      $this->throwError(__FUNCTION__, $e, "Failed to get hotel data.");
        Log::error("Failed to get hotel data.", ['message' => $e->getMessage()]);
    }
    return $this->parseHotelDataFromRoomDB($this->roomDbService->getPropertyByHotel($hotel));
  }

  /**
   * Get hotels data
   * Maximum number of Hotels is 30
   *
   * @param Illuminate\Support\Collection $hotels
   * @param bool debug
   *
   * @return array
   * @throws Exception
   */
  public function getHotels(
    Collection $hotels = null,
    $debug = false
  ) {
    $hotels = $hotels->splice(0, 30);
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($hotels) {
      $this->addPOS($el, $hotels->first());
      $el->add('HotelDescriptiveInfos');
      foreach ($hotels as $hotel) {
        $el->add('HotelDescriptiveInfo', $this->hotelCodeContext($hotel), function (XmlElement $el) {
          $el->add('ContactInfo', ['SendData' => 1]);
          $el->add('HotelInfo', ['SendData' => 1]);
        });
      }
    });

    try {
      $res = $this->sendRequest($xml);
      if ($debug) return $res->asXML();
      return $this->parseHotelsData($res);
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get hotels data.");
    }
  }

  public function modifyHotel($payload, $hotel = null)
  {
    $hotel ??= $this->hotel;
    $country = $this->getCountry(Arr::get($payload, 'country', Arr::get($payload, 'country_id')));
    Arr::forget($payload, ['country_id']);
    if ($country) {
      $payload['country'] = $country['code'];
      $payload['country_name'] = $country['name'];
    };
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload, $hotel) {
      $this->addPOS($el, $hotel);
      $el->add('HotelDescriptiveContents', function (XmlElement $el) use ($payload, $hotel) {
        $attrs = ['Overwrite' => 'true',
                  'HotelName' => Arr::get($payload, 'name', $hotel->name)
        ] + $this->hotelCodeContext($hotel);
        if ($currency = $this->getCurrencyCodeBasedOnPayload($payload)) {
          $attrs += ['CurrencyCode' => $currency];
        }
        $el->add('HotelDescriptiveContent', $attrs, function (XmlElement $el) use ($payload, $hotel) {
          $el->add('HotelInfo', $this->hotelCodeContext($hotel), function (XmlElement $el) use ($payload, $hotel) {
            $el->add('Descriptions', function (XmlElement $el) use ($payload, $hotel) {
              $el->add('Description', ['ContentCode' => '8', 'Name' => 'txt:HotelName'])
                ->add('Text', ['Language' => Arr::get($payload, 'lang') ?? 'en'], Arr::get($payload, 'name', $hotel->name));
              $el->add('Description', ['ContentCode' => '4', 'Name' => 'img:company_logo'])
                ->add('Text', ['Language' => Arr::get($payload, 'lang') ?? 'en'], $hotel->logo);
              // If payload has descriptions
              if ($descriptions = Arr::get($payload, 'descriptions')) {
                foreach (Cultuzz::DESCRIPTION_CODE_NAMES as $code => $name) {
                  $el->add('Description', [
                    'ContentCode' => $code,
                    'Name'        => 'txt:' . $name
                  ], function (XmlElement $el) use ($descriptions, $name) {
                    $name = strtolower($name);
                    foreach (Arr::get($descriptions, $name.'.langs', []) as $lang => $value) {
                      $el->add('Text', ['Language' => $lang], $value);
                    }
                  });
                }
              }
            });
            if (Arr::get($payload, 'capacity', 0) > 0) {
              $el->add('CategoryCodes', function (XmlElement $el) use ($payload) {
                $el->add('HotelCategory', ['Code' => $payload['type']]);
                $el->add('GuestRoomInfo', ['Code' => '231', 'Quantity' => $payload['capacity_mode']]);
                $el->add('GuestRoomInfo', ['Code' => '230', 'Quantity' => $payload['capacity']]);
              });
            }
            if (($latitude = Arr::get($payload, 'latitude')) !== null && ($longitude = Arr::get($payload, 'longitude')) !== null) {
              $el->add('Position', ['Longitude' => $longitude, 'Latitude' => $latitude]);
            }
          });
          if (Arr::has($payload, 'country')) {
            $el->add('ContactInfos')
              ->add('ContactInfo', ['LastUpdated' => XmlElement::xmlDateTime()], function (XmlElement $el) use ($payload) {
                $el->add('Addresses', function (XmlElement $el) use ($payload) {
                  $el->add('Address', ['FormattedInd' => 'false',
                                       'UseType'      => '7'], function (XmlElement $el) use ($payload) {
                    $el->add('StreetNmbr', Arr::get($payload, 'street'));
                    if ($_ = Arr::get($payload, 'street_optional')) {
                      $el->add('BldgRoom', $_);
                    }
                    $el->add('CityName', Arr::get($payload, 'city'));
                    $el->add('PostalCode', Arr::get($payload, 'zip'));
                    $el->add('StateProv', Arr::get($payload, 'state'));
                    $el->add('CountryName', ['Code' => Arr::get($payload, 'country')], Arr::get($payload, 'country_name'));
                  });
                });

                $el->add('Phones')
                  ->add('Phone', ['PhoneNumber' => Arr::get($payload, 'tel'), 'PhoneTechType' => '1']);
                $el->add('Emails', function (XmlElement $el) use ($payload) {
                  $el->add('Email', Arr::get($payload, 'email'));
//                if ($profile->alternative_email) {
//                  $el->add('Email', ['DefaultInd' => 'false', 'EmailType' => '3'], $profile->alternative_email ?? '');
//                }
                });

                if (Arr::has($payload, 'website')) {
                  $url = preg_replace('/^https?:\/\//', '', Arr::get($payload, 'website', '') ?? '');
                  $el->add('URLs')
                    ->add('URL', $url);
                }
              });
          }
        });
      });
    });

    try {
      $this->sendRequest($xml, ['authBasic' => true]);
      $hotel->update($payload);
      $this->roomDbService->updatePropertyCurrency($hotel, $this->getCurrencyCodeBasedOnPayload($payload));
      return true;
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update hotel.", $payload);
    }
  }

  private function parseHotelData(XmlElement $el, Hotel $hotel = null)
  {
    $roomDbHotelCurrency = $hotel ? $this->roomDbParser->getPropertyCurrency($this->roomDbService->getPropertyByHotel($hotel)) : null;
    $currency_code = $roomDbHotelCurrency ?: (string)$el['CurrencyCode'];
    $id = $hotel->id;
    $name = (string)($el->first('.//HotelName'));
    $_ = $el->first('.//CategoryCodes/GuestRoomInfo[@Code="231"]');
    $capacity_mode = isset($_) ? (int)$_['Quantity'] : null;
    $_ = $el->first('.//CategoryCodes/GuestRoomInfo[@Code="230"]');
    $capacity = isset($_) ? (int)$_['Quantity'] : null;
    $latitude = $longitude = null;
    if ($pos = $el->first('.//Position')) {
      $latitude = (string)$pos['Latitude'];
      $longitude = (string)$pos['Longitude'];
    }
    $addr = $el->first('.//ContactInfo/Addresses/Address[@FormattedInd="false"][@UseType="7"]');
    $street = (string)$addr->StreetNmbr;
    $street_optional = (isset($addr->BldgRoom)) ? (string)$addr->BldgRoom : null;
    $city = (string)$addr->CityName;
    $zip = (string)$addr->PostalCode;
    $state = (string)$addr->StateProv ?? null;
    $country = (string)$addr->CountryName['Code'] ?? null;
    $_ = $el->first('.//Phones/Phone[@PhoneTechType="1"]');
    $tel = isset($_) ? (string)$_['PhoneNumber'] : null;
    $email = (string)$el->first('.//Emails/Email[not(@DefaultInd="false")]');
    $_ = $el->first('.//Emails/Email[@DefaultInd="false"]');
    $altemail = isset($_) ? (string)$_ : null;
    $_ = $el->first('.//URLs/URL');
    $website = isset($_) ? (string)$_ : null;
    $_ = $el->first('.//Services/Service/Description[@Name="BookingService"]');
    $active = strtolower($_->stringAttr('ContentData', '')) === 'online';
    $_ = $el->first('.//HotelInfo/CategoryCodes/HotelCategory');
    $type = isset($_) ? (int)$_['Code'] : null;
    $_ = $el->first('.//HotelInfo');
    $hasMapped = isset($_) ? ($_['HasMapped'] == 'true') : null;

    return compact(
      'id', 'currency_code', 'name', 'active', 'capacity', 'capacity_mode', 'latitude', 'longitude', 'website',
      'street', 'street_optional', 'city', 'zip', 'state', 'country', 'tel', 'email', 'altemail', 'type', 'hasMapped'
    );
  }

  public function parseHotelsData(XmlElement $response)
  {
    $els = $response->xpath('.//HotelDescriptiveContents/HotelDescriptiveContent');
    $results = [];
    foreach ($els as $el) {
      $results[] = $this->parseHotelData($el);
    }
    return $results;
  }

  /**
   * @param array $roomDbData
   * @return array
   */
  private function parseHotelDataFromRoomDB(array $roomDbData)
  {
    if ($roomDbData['status'] === Response::HTTP_OK) {
      $currency_code = $this->roomDbParser->getPropertyCurrency($roomDbData);
      return compact(
        'currency_code'
      );
    }
    return [];
  }

  /**
   * Get Country with
   * given iso code
   *
   * @return array|null
   */
  public function getCountry($iso)
  {
    if (!$iso) return null;

    $countries = $this->getCountriesOrStates();
    return Arr::first($countries, fn($value) => $value['code'] == $iso);
  }

  public function getContactPersons()
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext(), function (XmlElement $el) {
          $el->add('ContactInfo', ['SendData' => 1]);
          $el->add('HotelInfo', ['SendData' => 1]);
        });
    });

    try {
      $res = $this->sendRequest($xml);
      // return $res->asXML();
      $info = $res->first('.//HotelDescriptiveContent');
      return $this->extractContacts($info);
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get contact persons.");
    }
  }

  public function modifyContactPerson($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveContents', function (XmlElement $el) use ($payload) {
        $el->add('HotelDescriptiveContent', [
          'CurrencyCode'     => $this->hotel->currency_code ?? 'EUR',
          'LanguageCode'     => 'EN',
          'Overwrite'        => 'true',
          'HotelCode'        => $this->hotel->id,
          'BrandName'        => 'CULTUZZ',
          'HotelCodeContext' => $this->hotel->ctx,
          'HotelName'        => $this->hotel->name,
          'ChainName'        => 'CultSwitch',
          'TimeZone'         => 'GMT',
          'ID'               => 'whatever',
        ], function (XmlElement $el) use ($payload) {
          $el->add('ContactInfos', function (XmlElement $el) use ($payload) {
            $isDelete = Arr::exists($payload, '_delete');
            $isUpdate = Arr::exists($payload, 'id');
            $isCreate = !$isUpdate;
            $el->add('ContactInfo', [
              'ContactProfileID' => $isCreate ? 'new' : $payload['id'],
              'ContactProfileType' => $isCreate ? 3 : ($isDelete ? '': $payload['type']),
              'Removal' => $isDelete ? '1' : '0',
              'LastUpdated' => $isDelete ? '' : XmlElement::xmlDateTime($this->hotel->updated_at)
            ], function (XmlElement $el) use ($payload, $isDelete) {
              $el->add('Names', function (XmlElement $el) use ($payload, $isDelete) {
                $el->add('Name', [
                  'CodeDetail' => $isDelete ? '' :$payload['language'],
                  'Gender' => 'Male',
                ], function (XmlElement $el) use ($payload, $isDelete) {
                  if ($isDelete) return;
                  $el->add('NamePrefix', $payload['salutation']);
                  $el->add('GivenName', $payload['firstname']);
                  $el->add('Surname', $payload['surname']);
                  $el->add('MiddleName', '');
                  $el->add('JobTitle', [
                    'Type' => 'Corporate',
                  ], $payload['position']);
                });
              });
              if ($isDelete) return;
              $el->add('Phones')->add('Phone', ['PhoneNumber' => $payload['phone']]);
              $el->add('Emails')->add('Email', $payload['mail']);
            });
          });
        });
      });
    });

    try {
      $this->sendRequest($xml, ['authBasic' => true]);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to modify contact person.", $payload);
    }
  }

  private function extractContacts(XmlElement $xml)
  {
    $_this = $this;
    $_contacts = $xml->xpath('//ContactInfos/ContactInfo');
    $contacts = array_filter($_contacts, function ($contact) {
      return (int)$contact['ContactProfileID'] != 0;
    });
    $contacts = array_map(function ($contact) use ($_this) {
      return $_this->parseContact($contact);
    }, $contacts);
    return $contacts;
  }

  private function parseContact(XmlElement $contact)
  {
    $id = (int)$contact['ContactProfileID'];
    $type = (int)$contact['ContactProfileType'];
    $salutation = (string)$contact->first('.//Names/Name/NamePrefix');
    $surname = (string)$contact->first('.//Names/Name/Surname');
    $firstname = (string)$contact->first('.//Names/Name/GivenName');
    $position = (string)$contact->first('.//Names/Name/JobTitle');
    $mail = (string)$contact->first('.//Emails/Email');
    $_ = $contact->first('.//Phones/Phone');
    $phone = isset($_['PhoneNumber']) ? (string)$_['PhoneNumber'] : '';
    $_ = $contact->first('.//Names/Name');
    $language = isset($_['CodeDetail']) ? (string)$_['CodeDetail'] : '';

    return compact('salutation', 'surname', 'firstname', 'position', 'mail', 'phone', 'language', 'id', 'type');
  }

  public function getFacilities()
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext(), function (XmlElement $el) {
          $el->add('HotelInfo', ['SendData' => 1]);
        });
    });

    try {
      $res = $this->sendRequest($xml);
      $info = $res->first('.//HotelDescriptiveContent');
      return $this->extractFacilities($info);
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get facilities.");
    }

  }

  public function extractFacilities(XmlElement $el)
  {
    return array_map(fn (XmlElement $el) => (int)$el['Code'], $el->xpath('./HotelInfo/Services/Service[@Code]'));
  }

  public function updateFacilities($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveContents', $this->hotelCodeContext())
        ->add('HotelDescriptiveContent', $this->hotelCodeContext() + [
            'Overwrite' => 'true',
          ])
        ->add('HotelInfo')
        ->add('Services', function (XmlElement $el) use ($payload) {
          if ($codes = Arr::get($payload, 'facilities', [])) {
            foreach ($codes as $Code) {
              $el->add('Service', compact('Code'));
            }
          } else {
            // workaround for cultuzz's inability to understand empty <Services/> (no facilities)
            $el->add('Service', ['Code' => '0']);
          }
        });
    });

    try {
      $res = $this->sendRequest($xml);
      return $res->asXML();
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to update facilities.", $payload);
    }
  }

  public function getPolicies()
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) {
          $el->add('ContentInfo', ['Name' => 'CancelPolicyList']);
          $el->add('ContentInfo', ['Name' => 'PaymentPolicyList']);
        });
    })->respVersion();

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $cxlPols = $this->extractCancelPolicies($res);
      $pymtPols = $this->extractPaymentPolicies($res);

      // Sort an array by value of 'id' key.
      $cxlPols = collect($cxlPols)->sortBy('id')->values()->all();
      $pymtPols = collect($pymtPols)->sortBy('id')->values()->all();

      return compact('cxlPols', 'pymtPols');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch payment policies.");
    }
  }

  public function modifyCancelPolicy($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ratePlanAttributes = [
        'RatePlanType'           => '11',
        'RatePlanNotifType'      => 'New',
        'RatePlanNotifScopeType' => 'RatePlanOnly',
      ];

      $isCopy = Arr::exists($payload, '_copy');
      $isDelete = Arr::exists($payload, '_delete');
      if(!$isCopy && Arr::exists($payload, 'id')) {
        $ratePlanAttributes['RatePlanNotifType'] = $isDelete ? 'Remove' : 'Overlay';
      }
      $el->add('RatePlans')
        ->add('RatePlan', $ratePlanAttributes, function (XmlElement $el) use ($payload, $isCopy, $isDelete) {
          $el->add('BookingRules')
            ->add('BookingRule')
            ->add('CancelPenalties', ['CancelPolicyIndicator' => 'true'], function (XmlElement $el) use ($payload, $isCopy, $isDelete) {
              // FIXME!
              $cancelPenaltyAttributes = [
                'ConfirmClassCode' => 'CancelPolicy'
              ];
              if(!$isCopy && $id = Arr::get($payload, 'id')) {
                $cancelPenaltyAttributes['PolicyCode'] = $id;
                if ($isDelete) {
                  $el->add('CancelPenalty', $cancelPenaltyAttributes);
                  return;
                }
              }
              $el->add('CancelPenalty', $cancelPenaltyAttributes, function (XmlElement $el) use ($payload) {
                $el->add('Deadline', [
                  'OffsetTimeUnit' => Arr::get(self::$timeUnits, Arr::get($payload, 'cancellationTime.timeUnit')),
                  'OffsetUnitMultiplier' => Arr::get($payload, 'cancellationTime.unitMultiplier'),
                  'OffsetDropTime' => Arr::get($payload, 'cancellationTime.dropTime')
                ]);
                $el->add('AmountPercent', [
                  Arr::get(self::$amountModes, Arr::get($payload, 'cancellationFee.mode')) => Arr::get($payload, 'cancellationFee.value'),
                  'BasisType' => Arr::get($payload, 'cancellationFee.basisType'),
                  'NmbrOfNights' => Arr::get($payload, 'cancellationFee.nmbrOfNights')
                ]);
                // title & description
                foreach([
                  'txt:name'              => 'name',
                  'txt:description_long'  => 'desc',
                ] as $x => $k) {
                  $el->add('PenaltyDescription', ['Name' => $x], function (XmlElement $el) use ($payload, $k) {
                    foreach(self::$langs as $lang) {
                      if ($_ = (Arr::get($payload, "langs.$lang.$k", ''))) {
                        $el->add('Text', ['Language' => $lang], $_);
                      }
                    }
                  });
                }
              });
            });
        });
    });

    try {
//      return $xml->asXML();
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $isDelete = Arr::exists($payload, '_delete');
      if (!$isDelete) {
        // fetch all policies in create/update/duplicate calls
        return $this->getPolicies();
      }
      // delete calls returns success
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process cancel policy.", $payload);
    }
  }

  public function modifyPaymentPolicy($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ratePlanAttributes = [
        'RatePlanType'           => '11',
        'RatePlanNotifType'      => 'New',
        'RatePlanNotifScopeType' => 'RatePlanOnly',
      ];

      $isCopy = Arr::exists($payload, '_copy');
      $isDelete = Arr::exists($payload, '_delete');
      if(!$isCopy && Arr::exists($payload, 'id')) {
        $ratePlanAttributes['RatePlanNotifType'] = $isDelete ? 'Remove' : 'Overlay';
      }
      $el->add('RatePlans')
        ->add('RatePlan', $ratePlanAttributes, function (XmlElement $el) use ($payload, $isCopy, $isDelete) {
          $el->add('BookingRules')
            ->add('BookingRule', function (XmlElement $el) use ($payload, $isCopy, $isDelete) {
              // FIXME!
              $guaranteePaymentAttributes = [];
              if(!$isCopy && $id = Arr::get($payload, 'id')) {
                $guaranteePaymentAttributes['InfoSource'] = $id;
                if ($isDelete) {
                  $el->add('RequiredPaymts')
                    ->add('GuaranteePayment', $guaranteePaymentAttributes);
                  return;
                }
              }
              $guaranteePaymentAttributes['PaymentCode'] = Arr::get($payload, 'paymentType');
              $guaranteePaymentAttributes['PaymentType'] = Arr::get(self::$bgarants, Arr::get($payload, 'paymentType'))['text'];
              $el->add('RequiredPaymts')
                ->add('GuaranteePayment', $guaranteePaymentAttributes, function (XmlElement $el) use ($payload) {
                  $el->add('AmountPercent', [
                    Arr::get(self::$amountModes, Arr::get($payload, 'paymentFee.mode')) => Arr::get($payload, 'paymentFee.value')
                  ]);
                  $el->add('Deadline', [
                    'OffsetTimeUnit' => Arr::get(self::$timeUnits, Arr::get($payload, 'paymentTime.timeUnit')),
                    'OffsetUnitMultiplier' => Arr::get($payload, 'paymentTime.unitMultiplier')
                  ]);
                  // title & description
                  foreach([
                    'txt:name'              => 'name',
                    'txt:description_long'  => 'desc',
                  ] as $x => $k) {
                    $el->add('Description', ['Name' => $x], function (XmlElement $el) use ($payload, $k) {
                      foreach(self::$langs as $lang) {
                        if ($_ = (Arr::get($payload, $k, ''))) {
                          $el->add('Text', ['Language' => $lang], $_);
                        }
                      }
                    });
                  }
                });
            });
        });
    });

    try {
//      return $xml->asXML();
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $isDelete = Arr::exists($payload, '_delete');
      if (!$isDelete) {
        // fetch all payment policies in create/update/duplicate calls
        return $this->getPolicies();
      }
      // delete calls returns success
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process payment policy.", $payload);
    }
  }

  private function extractPaymentPolicies(XmlElement $xml, $addEmpty = false)
  {
    $_policies = $xml->xpath('//RatePlan/UniqueID[@ID_Context="PaymentPolicyList"]/..//GuaranteePayment');
    $ret = collect($_policies)->map(function ($room) {
      return $this->parsePaymentPolicy($room);
    });
//    if ($addEmpty) {
//      $ret->prepend([
//        'id'   => '0',
//        'name' => 'No payment policy',
//      ]);
//    }
    return $ret->sortBy('id')->values()->all();
  }

  private function parsePaymentPolicy(XmlElement $policy)
  {
    $amountPercent = $policy->first('.//AmountPercent');
    $apValue = $amountPercent['Amount'];
    $apMode = 'amount';
    if(!$apValue) {
      $apValue = $this->trimPrice($amountPercent['Percent']);
      $apMode = 'percent';
    }
    $deadline = $policy->xpath('.//Deadline')[0];
    return [
      'id'          => (string)$policy['PolicyCode'],
      'name'        => trim((string)($policy->first('./Description[@Name="txt:name"]/Text'))),
      'desc'        => trim((string)($policy->first('./Description[@Name="txt:description_long"]/Text') ?? '')),
      'paymentType' => (int)$policy['PaymentCode'],
      'paymentFee'  => [
        'value' => trim((string)$apValue),
        'mode'  => $apMode,
      ],
      'paymentTime' => [
        'unitMultiplier' => (int)$deadline['OffsetUnitMultiplier'],
        'timeUnit'       => array_search(trim((string)$deadline['OffsetTimeUnit']), self::$timeUnits),
      ],
    ];
  }

  public function channelRatesList1($id, $extractTypes = true)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($id) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos')
        ->add('ContentInfo', ['Name' => 'DistributorRateList', 'Code' => $id]);
    });

    try {
      $res = $this->sendRequest($xml);
      return $this->parseChannelMappings($res, $extractTypes);
    } catch(Throwable $e) {
      $context = $id ? ['ID' => $id] : [];
      $this->throwError(__FUNCTION__, $e, "Failed to get channel's rates list.", $context);
    }
  }

  /**
   * Get all the CPLANS and CTYPES for PUSH channels (CultuzzCommRQ)
   *
   * @param $payload
   * @return array|void
   * @throws Throwable
   */
  public function channelRatesList($payload)
  {
    $xml = XmlElement::createRoot('CultuzzCommRQ', function (XmlElement $el) use ($payload) {
      $el->add('Message', [
        'Type'          => 'RoomRate',
        'CultuzzUserID' => '1',
        'MessageDomain' => 'CultAgentMappingRQ',
      ])
        ->add('RelevantObjects')
        ->add('CltzObject', ['Cultuzz_ID' => $this->hotel->id], function (XmlElement $el) use ($payload) {
          $el->add('object_id', $this->hotel->id);
          $el->add('distributor_id', $payload['id']);
          $el->add('credentials', function (XmlElement $el) use ($payload) {
            if (isset($payload['values']['ChannelUserID'])) {
              $el->add('username', $payload['values']['ChannelUserID']);
            }
            if (isset($payload['values']['ChannelPassword'])) {
              $el->add('password', $payload['values']['ChannelPassword']);
            }
            if (isset($payload['values']['ChannelHotelID'])) {
              $el->add('hotel_key', $payload['values']['ChannelHotelID']);
            }
          });
        });
    });

    try {
      $res = $this->sendRequest($xml, ['getCRates' => true]);

      return $this->parseChannelMappings($res->first('//RoomRates'));
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get rates list.");
    }
  }

  public function isEnabledChannel($id)
  {
    return true; // $id == $this->config['default_pull_channel'] || in_array($id, $this->config['enabled_channels']);
  }

  public function isDefaultChannel($id)
  {
    return $id == $this->config['default_pull_channel'];
  }

  public function getChannelType($id)
  {
    $channel = $this->channelsList(compact('id'));
    return ($channel['type'] ?? null) ?: null;
  }

  public function channelsList($payload = [])
  {
    $id = Arr::get($payload, 'id');
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($id) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) use ($id) {
          $el->add('ContentInfo', ['Name' => 'DistributorsConnection'] + ($id ? ['Code' => $id] : []));
          $el->add('ContentInfo', ['Name' => 'MaskCCMList'] + ($id ? ['Code' => $id] : []));
        });
    });

    $debug = Arr::get($payload, '_debug');
    try {
      $res = $this->sendRequest($xml);
      if ($debug) return $res->asXML();
      $channels = collect($res->xpath('.//ChannelInfo'))
        ->map(fn (XmlElement $channel) => $this->parseChannel($channel, false))
        ->sortBy('name');
        // TODO: remove filtering later
//        ->whereIn('id', array_merge($this->config['enabled_channels'], [$this->config['default_pull_channel']]))
//        ->whereIn('type', 'pull')
      $channels = $id ? $channels->firstWhere('id', $id) : $channels->values();
      if ($id) {
        if ($channels['type'] === 'push') {
          // add channel fields info
          $channels['fields'] = $this->channelsFields($id, true);
//        $channels['fields'] = $this->extractChannelFields($res, $id);
          // extract field values
          $this->extractChannelValues($res->first('.//ChannelInfo'), $channels['fields'], $channels);
        }
      } else if (Arr::exists($payload, '_fields')) {
        $allFields = $this->extractChannelFields($res);
        $channels = $channels->map(function ($channel) use ($allFields) {
          $fields = Arr::get($allFields, $channel['id']);
          return $channel + compact('fields');
        });
      }
      return $channels;
    } catch(Throwable $e) {
      $context = $id ? ['ID' => $id] : [];
      $this->throwError(__FUNCTION__, $e, "Failed to get channels list.", $context);
    }
  }

  public function channelsFields($ids = [], $flat = false)
  {
    if (!$ids) return [];
    $ids = (array) $ids;
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($ids) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) use ($ids) {
          foreach($ids as $id) {
            $el->add('ContentInfo', ['Name' => 'MaskCCMList', 'Code' => $id]);
          }
        });
    });

    try {
      $res = $this->sendRequest($xml);
      return $this->extractChannelFields($res, $flat && count($ids) == 1 ? $ids[0] : null);
    } catch(Throwable $e) {
      $context = $ids ? ['IDs' => $ids] : [];
      $this->throwError(__FUNCTION__, $e, "Failed to get channels fields list.", $context);
    }
  }

  private function parseChannel(XmlElement $info, $disableNotDefault = true, $fields = null)
  {
    $status = (int)$info['Status'];
    $state = (string)$info['State'];
    $cstate = !$status ? 'inactive' : ($state === 'open' ? 'active' : 'blocked');
    $ret = [
      'id'     => (string)$info['DistributorID'],
      'name'   => (string)$info['Name'],
      'type'   => strtolower((string)$info['Type']),
      'easyMapping' => (int)$info['EasyMapping'],
      'status' => $cstate,
      'mapped'  => (string)$info->first('./ProductInfo/@MappedProducts'),
      'count'  => (int)$info->first('./ProductInfo/@MappedProductsCount'),
      'total'  => (int)$info->first('./ProductInfo/@TotalProductsCount'),
      'dt'     => (string)$info->first('./HistoryInfos/HistoryInfo/@Date'),
    ];
    $url = $info->stringAttr('URL');
    if ($url && !Str::startsWith($url, ['http://', 'https://'])) {
      $url = "https://{$url}";
    }
    $ret['url'] = $url;
    $ret['enabled'] = $this->isEnabledChannel($ret['id']);
    if (strtolower($ret['name']) === 'myproperty') $ret['enabled'] = false;
    $ret['default'] = $ret['id'] === $this->config['default_pull_channel'];

    if ($ret['type'] === 'push') {
      $ret['reg_dt'] = (string)$info['RegistrationDate'];
//      $ret['url'] = (string)$info['URL'];
      $RegInfo = $info->first('./RegistrationInfo');
      if ($RegInfo) {
        $until = $RegInfo->stringAttr('ContractEndDate');
        $period = [
            'until' => $until ? $this->carbonDate(explode('T', $until)[0])->format('Y-m-d') : null,
          ] + $this->extractDuration($RegInfo->first('./Periods/Period[@Code="timeInAdavnce"]/Deadline'));
        $ret['period'] = $period;
      }
      $this->extractChannelValues($info, $fields, $ret);
      if (!Arr::exists($ret, 'period')) {
        $ret['period'] = ['until' => null, 'unit' => 'd', 'number' => 0];
      }
      if (!Arr::exists($ret, 'values')) {
        $ret['values'] = [];
      }
//      if ($period = Arr::get($v, 'period')) {
//        $ret['period'] = $period;
//      }
//      if ($values = Arr::get($v, 'values')) {
//        $ret['values'] = $values;
//      }
    }
//    if ($ret['id'] === '7563') {
//      $ret['status'] = 'inactive';
//      $ret['state'] = 'closed';
//      $ret['dt'] = null;
//    }
    if ($disableNotDefault) {
      $ret['enabled'] = $ret['id'] === $this->config['default_pull_channel'];
      if (!$ret['enabled']) {
        $ret['status'] = 'inactive';
      }
    }
    return $ret;
  }

  private function extractChannelFields(XmlElement $el, $id = null, $easyMapping = true)
  {
    $xp = './/HotelInfo/Services/Service';
    if ($id) $xp.= '[@Code="'.$id.'"]';
    $ret = collect($el->xpath($xp))->mapWithKeys(function (XmlElement $service) use ($easyMapping) {
      $id = $service->stringAttr('Code');
      $fields = $this->parseChannelFields($service, $easyMapping);
      return [$id => $fields];
    });
    if ($id && $ret->has($id)) $ret = $ret[$id];
    return $ret ? $ret->toArray() : [];
  }

  private function parseChannelFields(XmlElement $service, $easyMapping = true)
  {
    return collect($service->xpath('./Features/Feature'))->map(function (XmlElement $feature) use ($easyMapping) {
      $key = $feature->stringAttr('CodeDetail');
      $code = $feature->stringAttr('ExistsCode');
      if (!Cultuzz::isValidFeature($code) && $easyMapping) return null;
      if (Cultuzz::isValidFeature($code) && !$easyMapping) return null;
      $ret = compact('key', 'code');
      try {
        $v = $feature->first('./Description[@CodeDetail="APIVersion"]')->stringAttr('ContentCaption');
        $v = "1";
        $title = $feature->first('./Description[@CodeDetail="Label"]')->stringAttr('ContentCaption');
        $type = $feature->first('./Description[@CodeDetail="DataType"]')->stringAttr('ContentCaption');
        $subtype = $feature->first('./Description[@CodeDetail="HTMLFormTag"]')->stringAttr('ContentCaption');
        $ret += compact('v', 'title', 'type', 'subtype');
      } catch (Throwable $_) {
        $ret += Cultuzz::featureData($code);
      }
      return $ret;
    })->filter()->values();
  }

  private function extractChannelValues(XmlElement $info, $fields, &$appendTo = null)
  {
    $ret = [];
    // extract period first
    $number = $info->first('.//Feature[@ExistsCode="23"]/Description/@ContentCaption');
    $unit = $info->first('.//Feature[@AccessibleCode="23"]/Description/@ContentCaption');
    $duration = $info->first('.//Feature[@ExistsCode="29"]/Description/@ContentCaption');
    $ret['period'] = [
      'number' => (string) $number,
      'unit' => (string) $unit,
      'until' => $duration ? $this->carbonDate(explode('T', (string)$duration)[0])->format('Y-m-d') : null,
    ];
    if ($fields) {
      $values = [];
      foreach($fields as $field) {
        $key = $field['key'];
        $code = Cultuzz::featureCode($key);
        $val = $info->first('.//Feature[@CodeDetail="'.$key.'"]/Description/@ContentCaption');
        if (!isset($val) && Cultuzz::isFeatureRegInfo($code)) {
          $val = $info->first('./RegistrationInfo/@'.$key);
        }
        if (isset($val)) {
          $values[$key] = (string) $val;
        }
      }
      if ($values) {
        $ret += compact('values');
      }
    }
    if (isset($appendTo)) {
      if (Arr::has($ret, 'period')) {
        $appendTo['period'] = $ret['period'];
      }
      if (Arr::has($ret, 'values')) {
        $appendTo['values'] = $ret['values'];
      }
    }
    return $ret;
  }

  public function modifyChannel($payload, $fields = null)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload, $fields) {
      $this->addPOS($el);
      $mode = Arr::get($payload, 'mode');
      $attrs = [
        'CodeContext'   => 'DistributorsConnection',
        'DistributorID' => Arr::get($payload, 'id', $this->config['default_pull_channel']),
        'Status'        => in_array($mode, ['start', 'activate', 'block', 'update']) ? 2 : 0,
      ];
      if(!in_array($mode, ['start', 'disconnect'])) {
        $attrs += [
          'State' => in_array($mode, ['activate', 'update']) ? 'open' : 'closed',
        ];
      }
      if ($mode === 'disconnect') {
        // push channel disconnect
        $attrs += [
          'Action' => 'NewConnection',
        ];
      }

      foreach ($fields as $field) {
        $key = Arr::get($field, 'key');
        $code = Cultuzz::featureCode($key);
        $v = Arr::get($field, 'v', 0);
        $value = Arr::get($payload, $key);
        if (!$v || !Cultuzz::featureData($code)) {
          $key = str_replace(" ", "", $key);
          if ($key == 'SetURL') $key = 'URL';
          $attrs +=[
            $key => $value
          ];
        }
      }

      $el->add('HotelDescriptiveContents', $this->hotelCodeContext())
        ->add('HotelDescriptiveContent', $this->hotelCodeContext())
        ->add('TPA_Extensions')
        ->add('ChannelInfos')
        ->add('ChannelInfo', $attrs, function (XmlElement $el) use ($payload, $fields) {
          if ($fields) {
            $UserName = Arr::get($payload, 'ChannelUserID');
            $Oldpassword = $Newpassword = Arr::get($payload, 'ChannelPassword');
            $RegInfo = $el->add('RegistrationInfo', compact('UserName', 'Oldpassword', 'Newpassword'));
            $RegInfo->add('Periods', function (XmlElement $el) use ($payload) {
              $OffsetTimeUnit = Arr::get($payload, 'period.unit');
              $OffsetUnitMultiplier = Arr::get($payload, 'period.number');
              $attrs = compact('OffsetTimeUnit', 'OffsetUnitMultiplier');
              $el->add('Period', ['CodeContext' => 'timeInAdvance'])->add('Deadline', $attrs);
            });
            $until = $this->carbonDate(Arr::get($payload, 'period.until'));
            $until->startOfDay();
            $RegInfo->addAttribute('ContractEndDate', $el::xmlDateTime($until));
            $features = [];
            $hasV1 = false;
            foreach($fields as $field) {
              $key = Arr::get($field, 'key');
              $code = Cultuzz::featureCode($key);
              $v = Arr::get($field, 'v', 0);
              $value = Arr::get($payload, $key);
              if (!$v || Cultuzz::isFeatureRegInfo($code)) {
                $key = str_replace(" ", "", $key);
                $RegInfo->addAttribute($key, $value);
              }
              if ($v > 0) {
                $features[] = compact('key', 'code', 'value');
                $hasV1 = true;
              }
            }
            if ($hasV1) {
              // add periods to features
              $features[] = [
                'key'   => 'Period',
                'code'  => '23',
                'value' => Arr::get($payload, 'period.number'),
              ];
              $features[] = [
                'key'   => 'DurationType',
                'code'  => '25',
                'acode' => '23',
                'value' => Arr::get($payload, 'period.unit'),
              ];
              $until = $this->carbonDate(Arr::get($payload, 'period.until'));
              $until->startOfDay();
              $features[] = [
                'key' => 'ContactEndDate',
                'code' => '29',
                'value' => $el::xmlDateTime($until),
              ];
            }
            if ($features) {
              $el->add('Services')
                 ->add('Service', ['Code' => Arr::get($payload, 'id'), 'CodeDetail' => 'CltzDistributorID'])
                 ->add('Features', function (XmlElement $el) use ($features) {
                   foreach($features as $feature) {
                     $el->add('Feature', [
                       'ExistsCode'     => $feature['code'],
                       'CodeDetail'     => $feature['key'],
                       'AccessibleCode' => $feature['acode'] ?? '0',
                     ])->add('Description', ['CodeDetail' => 'Value', 'ContentCaption' => $feature['value']]);
                   }
                 });
            }
          }
        });
    });

    try {
      $res = $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to map pull channel.", $payload);
    }
  }

  public function modifyPullMappings($payload)
  {
    if(!$rooms = Arr::get($payload, 'rooms', [])) {
      return true;
    }
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload, $rooms) {
      $this->addPOS($el);
      $el->add('RatePlans', function (XmlElement $el) use ($payload, $rooms) {
        $channelId = Arr::get($payload, 'id', $this->config['default_pull_channel']);
        $promo = Arr::get($payload, 'promo');
        foreach ($rooms as $room) {
          $inv = $room['inv'] && (!isset($promo) || $promo !== false) ? 'true' : 'false';
          $attrs = [
            'RatePlanID'                 => $room['id'],
            'RatePlanNotifType'          => 'Overlay',
            'RatePlanType'               => '11',
            'InventoryAllocatedInd'      => $inv,
            'RestrictedDisplayIndicator' => 'false',
            'MarketCode'                 => $channelId,
          ];
          if ($promo !== false) {
            $attrs += ['RatePlanCodeType' => isset($promo) ? 'RatePlanCode' : 'DoesNotApply'];
            if (isset($promo)) {
              $attrs += [
                'RatePlanCode'       => $promo,
                'RatePlanStatusType' => 'Active',
              ];
            }
          }
          $el->add('RatePlan', $attrs);
        }
      });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to map products to pull channel.", $payload);
    }
  }

  public function modifyPushMappings($payload)
  {
    if(!$rooms = Arr::get($payload, 'rooms', [])) {
      return true;
    }
    $crates = $this->channelRatesList($payload)['cplans'];

    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload, $rooms, $crates) {
      $this->addPOS($el);
      $el->add('RatePlans', function (XmlElement $el) use ($payload, $rooms, $crates) {
        $channelId = Arr::get($payload, 'id');
        foreach($rooms as $room) {
          $isDel = !$room['inv'];
          $uniq = "{$room['id']}_{$room['typeid']}";
          $crate = collect($crates)->filter(function($crate) use ($uniq) {
            return Arr::get($crate, 'uniq') == $uniq;
          })->first();

          if (!$crate) return;
          $rp = $el->add('RatePlan', [
            'RatePlanID'                 => $room['rid'],
            'RatePlanNotifType'          => 'Overlay',
            'RatePlanType'               => '11',
            'InventoryAllocatedInd'      => !$isDel ? 'true' : 'false',
            'RestrictedDisplayIndicator' => 'false',
            'MarketCode'                 => $channelId,
          ]);

          $rp->add('SellableProducts')
            ->add('SellableProduct', [
              'InvCode'         => $room['typeid'],
              'InvGroupingCode' => $room['id'],
              'InvStatusType'   => !$isDel ? 'Active' : 'Deactivated',
              'InvType'         => $crate['typeid'],
            ], function (XmlElement $el) use ($room, $channelId, $crate, $isDel) {
              if (!$isDel) {
                $el->add('Description', ['CreatorID' => $room['mode'], 'Name' => $crate['name']]);
              }
              $el->add('UniqueID', ['ID' => $channelId, 'ID_Context' => $room['inv'] ? 'ProductMapping' : 'ProductDeMapping', 'Type' => '18']);
            });
        }
      });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to map products to push channel.", $payload);
    }
  }

  public function channelPullMappings()
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) {
          $el->add('ContentInfo', ['Name' => 'Product']);
          $el->add('ContentInfo', ['Name' => 'ProductElement']);
          $el->add('ContentInfo', ['Name' => 'GetMappings']);
        });
    });

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $types = collect($this->extractRoomTypes($res))
        ->map(fn ($a) => Arr::only($a, ['id', 'pid', 'langs']))
        ->values();
      $plans = collect($this->extractRatePlans($res))
        ->map(fn ($a) => Arr::only($a, ['id', 'marketcodes', 'room', 'langs']))
        ->values();
      return compact('plans', 'types');
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get PULL channel mappings.");
    }
  }

  public function channelInfoWithMappings1($id, $debug = false)
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($id) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) use ($id) {
          $el->add('ContentInfo', ['Name' => 'Product']);
          $el->add('ContentInfo', ['Name' => 'ProductElementList']);
          $el->add('ContentInfo', ['Name' => 'ProductsOfDistributor', 'Code' => $id]);
          $el->add('ContentInfo', ['Name' => 'GetMappings']);
          $el->add('ContentInfo', ['Name' => 'DistributorsConnection', 'Code' => $id]);
          $el->add('ContentInfo', ['Name' => 'MaskCCMList', 'Code' => $id]);
//          $el->add('ContentInfo', ['Name' => 'DistributorsConnection']);
//          $el->add('ContentInfo', ['Name' => 'MaskCCMList']);
        });
    });

    $rates = [];
    $mapped = [];

    try {
      $res = $this->sendRequest($xml);
      if ($debug) {
        return $res->asXML();
      }
      $channelInfo = $res->first('//ChannelInfos/ChannelInfo[@DistributorID="'.$id.'"]');
      $fields = $this->extractChannelFields($res, $id);
      $channel = $this->parseChannel($channelInfo, false, $fields);
      $channel['fields'] = $fields;
      $contract = $channel['default'] ? $this->getAutoContractors($this->config['default_pull_channel']) : null;
      $rooms = $this->extractRoomTypes($res, false, true);
      $plans = $this->extractRatePlans($res, !$channel['default'], Arr::get($contract, 'codes'));
      $rates = compact('rooms', 'plans');
//      $rates = $this->getRoomTypesAndRatePlans($channel['default'], $channel['default']);
      if ($channel['type'] != 'push') {
        $pids = collect($rates['plans'])->pluck('id');
        $mapped = collect($res->xpath('//RatePlan[@MarketCode][@InventoryAllocatedInd="true"]'))
          ->map(fn (XmlElement $el) => (string)$el['RatePlanID'])
          ->filter(fn ($m) => $pids->contains($m))
          ->values();
      } else {
        $mapped = collect($res->xpath('//RatePlan[@MarketCode]'))->mapWithKeys(function (XmlElement $el) {
          $rid = (string)$el['RatePlanID'];
          $_ = $el->first('./SellableProducts/SellableProduct/UniqueID[@ID_Context="ProductMapping"]/..');
          if (!isset($_)) return [];
          $typeid = (string)$_['InvCode'];
          $id = (string)$_['InvGroupingCode'];
          $mode = (int)$_->Description['CreatorID'];
          $uniq = "{$id}_{$typeid}";
          return [$rid => compact('id', 'typeid', 'uniq', 'mode')];
        });
      }
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get PULL channel mappings.");
    }

    $invalid = false;
    $crates = [];

    if ($channel['type'] == 'push') {
      $crates = ['ctypes' => [], 'cplans' => []];
      $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($id) {
        $this->addPOS($el);
        $el->add('HotelDescriptiveInfos')
          ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
          ->add('ContentInfos')
          ->add('ContentInfo', ['Name' => 'DistributorRateList', 'Code' => $id]);
      });
      try {
        $res = $this->sendRequest($xml);
        if ($debug) {
          return $res->asXML();
        }
        $crates = $this->parseChannelMappings($res->first('//RatePlan[not(@MarketCode)]'));
      } catch (Throwable $e) {
        $invalid = true;
      }
    }
    $channel['invalid'] = $invalid;
    if ($channel['default']) {
      $channel['contractor'] = $this->getAutoContractors($channel['id']);
    }

    return compact('mapped', 'channel') + $crates + $rates;
  }

  public function channelInfoWithMappings($id, $debug = false)
  {
    $push = $this->getChannelType($id) === 'push';
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($id, $push) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos', function (XmlElement $el) use ($id, $push) {
          $el->add('ContentInfo', ['Name' => 'Product']);
//           $el->add('ContentInfo', ['Name' => 'ProductList']);
          $el->add('ContentInfo', ['Name' => 'ProductsOfDistributor', 'Code' => $id]);
          $el->add('ContentInfo', ['Name' => 'GetMappings']);
          $el->add('ContentInfo', ['Name' => 'DistributorsConnection', 'Code' => $id]);
          if ($push) {
            $el->add('ContentInfo', ['Name' => 'MaskCCMList', 'Code' => $id]);
          }
        });
    });

    $rates = [];
    $mapped = [];

    try {
      $res = $this->sendRequest($xml);
      if ($debug) {
        return $res->asXML();
      }
      $channelInfo = $res->first('//ChannelInfos/ChannelInfo[@DistributorID="'.$id.'"]');
      $easyMapping = (bool)(int)$channelInfo['EasyMapping'];
      $fields = $this->extractChannelFields($res, $id);
      $nonEasyMappingFields = $easyMapping ? null : $this->extractChannelFields($res, $id, $easyMapping);
//      if ($fields) {
//        $ret['fields'] = $fields;
//        $ret['values'] = $this->extractChannelValues($channelInfo, $fields);
//      }
//      return $_->asXML();
      $channel = $this->parseChannel($channelInfo, false, $fields);
      $channel['fields'] = $fields;
      $channel['notEasyMappingFields'] = $nonEasyMappingFields;
//      $rates = $this->getRoomTypesAndRatePlans($channel['default'], $channel['default']);
      $rates = $this->getRoomAndPlans($id, $this->hotel->id);
      if (!$push) {
        $pids = collect($rates['plans'])->pluck('id');
        $mapped = collect($res->xpath('//RatePlan[@MarketCode][@InventoryAllocatedInd="true"]'))
          ->map(fn (XmlElement $el) => (string)$el['RatePlanID'])
          ->filter(fn ($m) => $pids->contains($m))
          ->values();
      } else {
        $mapped = collect($res->xpath('//RatePlan[@MarketCode]'))->mapWithKeys(function (XmlElement $el) {
          $rid = (string)$el['RatePlanID'];
          $_ = $el->first('./SellableProducts/SellableProduct/UniqueID[@ID_Context="ProductMapping"]/..');
          if (!isset($_)) return [];
          $typeid = (string)$_['InvCode'];
          $id = (string)$_['InvGroupingCode'];
          $mode = (int)$_->Description['CreatorID'];
          $uniq = "{$id}_{$typeid}";
          return [$rid => compact('id', 'typeid', 'uniq', 'mode')];
        });
      }
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get PULL channel mappings.");
    }

    $invalid = false;
    $crates = ['ctypes' => [], 'cplans' => []];

    if ($push && $easyMapping) {
      $xml = XmlElement::createRoot('CultuzzCommRQ', function (XmlElement $el) use ($channel) {
        $el->add('Message', [
          'Type'          => 'RoomRate',
          'CultuzzUserID' => '1',
          'MessageDomain' => 'CultAgentMappingRQ',
        ])
          ->add('RelevantObjects')
          ->add('CltzObject', ['Cultuzz_ID' => $this->hotel->id], function (XmlElement $el) use ($channel) {
            $el->add('object_id', $this->hotel->id);
            $el->add('distributor_id', $channel['id']);
            $el->add('credentials', function (XmlElement $el) use ($channel) {
                if (isset($channel['values']['ChannelUserID'])) {
                  $el->add('username', $channel['values']['ChannelUserID']);
                }
                if (isset($channel['values']['ChannelPassword'])) {
                  $el->add('password', $channel['values']['ChannelPassword']);
                }
                if (isset($channel['values']['ChannelHotelID'])) {
                  $el->add('hotel_key', $channel['values']['ChannelHotelID']);
                }
              });
          });
      });

      try {
        $res = $this->sendRequest($xml, ['getCRates' => true]);
        if ($debug) {
          return $res->asXML();
        }
        $crates = $this->parseChannelMappings($res->first('//RoomRates'));
      } catch (Throwable $e) {
        $invalid = true;
      }
    }
    $channel['invalid'] = $invalid;
    if ($channel['default']) {
      $channel['contractor'] = $this->getAutoContractors($channel['id']);
    }

    return compact('mapped', 'channel') + $crates + $rates;
  }

  private function parseChannelMappings(XmlElement $el = null, $extractTypes = true)
  {
    if (!isset($el)) return [];
    $rates = array_map(function (XmlElement $el) {
      $type = (string)$el->Room->name;
      $typeid = (string)$el->Room->id;
      $id = (string)$el->Room->id;
      $name = (string)$el->Room->name;
      $uniq = "{$id}_{$typeid}";
      return compact('id', 'name', 'type', 'typeid', 'uniq');
    }, $el->xpath('//RoomRate'));
    if (!$extractTypes) return collect($rates)->groupBy('uniq')->map->pop()->toArray();
    $ctypes = collect($rates)->pluck('type', 'typeid')->toArray();
    $cplans = collect($rates)->map(fn ($i) => Arr::except($i, 'type'))->toArray();
    return compact('ctypes', 'cplans');
  }

  public function getChannelMappings($payload)
  {
    $xml = XmlElement::createRoot('CultuzzCommRQ', function (XmlElement $el) use ($payload) {
      $el->add('Message', [
        'Type'          => 'RoomRate',
        'CultuzzUserID' => '1',
        'MessageDomain' => 'CultAgentMappingRQ',
      ])
         ->add('RelevantObjects')
         ->add('CltzObject', ['Cultuzz_ID' => $this->hotel->id], function (XmlElement $el) use ($payload) {
           $el->add('object_id', $this->hotel->id);
           $el->add('distributor_id', Arr::get($payload, 'channel_id'));
           $el->add('credentials')->add('hotel_key', Arr::get($payload, 'hotel_key'));
         });
    });

    $debug = Arr::get($payload, '_debug');
//    if ($debug) return $xml->asXML();
    try {
      $res = $this->sendRequest($xml, ['mappings' => true]);
      if ($debug) {
        return $res->asXML();
      }

    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get PUSH channel mappings (new method).");
    }

  }

  private function extractMealValidities(XmlElement $plan)
  {
    $ret = [
      'validity'  => [],
      'blockouts' => [],
      'prices'    => [],
    ];
    $rates = $plan->xpath('./Rates/Rate/UniqueID[@ID_Context="CltzProductValidity" or @ID_Context="CltzProductElementValidity" or @ID_Context="CltzInventoryPriceTime"]/..');
    foreach ($rates as $rate) {
      $from = (string)$rate['Start'];
      $until = (string)$rate['End'];
      $status = (string)$rate['Status'];

      $days = $this->weekdaysFromXMLAttributes($rate);
      $context = (string)$rate->UniqueID['ID_Context'];

      if ($context == 'CltzInventoryPriceTime') {
        $price = '0.00';
        $_ = $rate->first('./Fees/Fee');
        if (isset($_)) {
          $price = $_->stringAttr('Amount', '0.00');
        }
        $ret['prices'][] = compact('from', 'until', 'price', 'days');
      } else {
        if ($status === 'Open') {
          $ret['validity'][] = compact('from', 'until', 'days');
        } else {
          $ret['blockouts'][] = compact('from', 'until', 'days');
        }
      }
    }
    return $ret;
  }

  private function mealPlanTypesForXml()
  {
    return collect(array_column(Cultuzz::MEAL_TYPES, 'id'))->map(fn ($id) => "@InvTypeCode=\"$id\"")->join(' or ');
  }

  private function parseMealPlan(XmlElement $plan)
  {
    $product = $plan->first('.//SellableProduct');
    if (!$product) return null;
    try {
      $price = (string)$product->GuestRoom->RoomLevelFees->Fee['Amount'];
    } catch (Throwable $e) {
      $price = 0;
    }
    $type = (string)$product['InvTypeCode'];
    $deletable = (string)$product['InvCodeApplication'];
    $item = [
      'id'       => (string)$product->UniqueID['ID'],
      'price'    => $price,
      'typecode' => $type,
      'deletable'=> ($deletable == 'DoesNotApply'),
      'text'     => (string)$product->Description['Name'],
      'langs'    => [],
    ];
    $descriptions = $product->first('./Description');
    foreach (['9' => 'name', '7' => 'desc'] as $x => $k) {
      foreach (self::$langs as $lang) {
        $_ = $descriptions->first("./ListItem[@ListItem='$x'][@Language='$lang']");
        $item['langs'][$lang][$k] = $_ ? trim((string)$_) : '';
      }
    }
    $item += $this->extractMealValidities($plan);
    return $item;
  }

  public function getMealPlans($payload = [])
  {
    $id = Arr::get($payload, 'id');
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($payload, $id) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext())
        ->add('ContentInfos')
        ->add('ContentInfo', ['Name' => 'ProductElement'] + ($id ? ['Code' => $id] : []));
    });
    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $ret = $this->extractMealPlans($res);
      return Arr::get($payload, 'id') ? array_pop($ret) : $ret;
    } catch (Throwable $e) {
      $context = $id ? ['ID' => $id] : [];
      $this->throwError(__FUNCTION__, $e, "Failed to fetch meal plans.", $context);
    }
  }

  private function extractMealPlans(XmlElement $xml)
  {
    $_rooms = $xml->xpath('//RatePlans/RatePlan[not(@RatePlanID)]/SellableProducts/SellableProduct['.$this->mealPlanTypesForXml().']/../..');
    return array_map(fn ($room) => $this->parseMealPlan($room), $_rooms);
  }

  public function modifyMealPlan($payload)
  {
    $xml = XmlElement::createRoot('OTA_HotelRatePlanNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $ratePlanAttributes = [
        'RatePlanType' => '11',
      ];

      $isCopy = Arr::exists($payload, '_copy');
      if ($isCopy) {
        $payload = $this->getMealPlans($payload);
        Arr::forget($payload, ['id', '_copy']);
        $isCopy = false;
        foreach (self::$langs as $lang) {
          foreach (['name', 'desc'] as $v) {
            $k = "langs.{$lang}.{$v}";
            if ($_ = Arr::get($payload, $k)) {
              Arr::set($payload, $k, '[COPY] '.$_);
            }
          }
        }
      }

      $isDelete = Arr::exists($payload, '_delete');
      $isUpdate = Arr::exists($payload, 'id');
      $isCreate = !$isUpdate;
      if ($isDelete) {
        $ratePlanAttributes['RatePlanNotifType'] = 'Remove';
      }
      $el->add('RatePlans')
        ->add('RatePlan', $ratePlanAttributes, function (XmlElement $el) use ($payload, $isCreate, $isCopy, $isDelete, $isUpdate) {
          $sp = null;
          $el->add('SellableProducts', function (XmlElement $el) use ($payload, &$sp, $isCreate, $isDelete) {
            $sp = $el->add('SellableProduct', [
              'IsRoom'        => 'false',
              'InvTypeCode'   => Arr::get($payload, 'typecode'),
              'InvNotifType'  => $isCreate ? 'New' : 'Overlay',
              'InvStatusType' => 'Active',
            ]);
          });
          if (!$isCreate) {
            $sp->add('UniqueID', [
              'Type'       => '18',
              'ID_Context' => 'CltzCommonProductElement',
              'ID'         => Arr::get($payload, 'id'),
            ]);
          }

          $sp->add('Description', ['Name' => Arr::get($payload, 'langs.en.name')], function (XmlElement $el) use ($payload) {
            foreach (['9' => 'name', '7' => 'desc', '8' => 'desc'] as $x => $k) {
              foreach (self::$langs as $lang) {
                if ($_ = (Arr::get($payload, "langs.$lang.$k", ''))) {
                  $el->add('ListItem', [
                    'Language' => $lang,
                    'ListItem' => $x,
                  ], $_);
                }
              }
            }
          });

          if ($isCopy || $isDelete) return;
          $el->add('BookingRules')
            ->add('BookingRule', [
              'PriceViewable'   => 'true',
              'QualifiedRateYN' => 'true',
            ]);
          $sp->add('GuestRoom')
            ->add('RoomLevelFees')
            ->add('Fee', ['Amount' => $this->formatPrice(Arr::get($payload, 'price'))]);


          $el->add('Description', ['Name' => Arr::get($payload, 'langs.en.name')]);
          $el->add('Rates', function (XmlElement $el) use ($payload) {
            // min price
            $el->add('Rate', function (XmlElement $el) use ($payload) {
              $el->add('UniqueID', [
                'ID_Context' => 'CltzInventoryLowestPrice',
                'ID'         => 'new',
                'Type'       => '18',
              ]);
              $el->add('Fees')->add('Fee', ['Amount' => '0.00']);
            });

            foreach (['validity', 'blockouts', 'prices'] as $mode) {
              foreach (Arr::get($payload, $mode, []) as $period) {
                $from = $this->carbonDate(Arr::get($period, 'from'));
                $until = $this->carbonDate(Arr::get($period, 'until'));
                $status = $mode === 'blockouts' ? 'Close' : 'Open';
                $context = $mode === 'prices' ? 'CltzInventoryPriceTime' : 'CltzProductElementValidity';
                $el->add('Rate', [
                    'Start'  => $el::xmlDate($from),
                    'End'    => $el::xmlDate($until),
                    'Status' => $status,
                    //                    'InvCode' => Arr::get($payload, 'id'),
                  ] + $this->convertWeekdaysToXML(Arr::get($period, 'days'), true)
                  , function (XmlElement $el) use ($context, $mode, $period) {
                    $el->add('UniqueID', [
                      'ID'         => 'new',
                      'ID_Context' => $context,
                      'Type'       => '18',
                    ]);
                    if ($mode === 'prices') {
                      $el->add('Fees')
                        ->add('Fee', ['Amount' => $this->formatPrice(Arr::get($period, 'price'))]);
                    }
                  });
              }
            }

          });
        });
    });

    try {
      $res = $this->sendRequest($xml);
      $id = (string)$res->first('//RatePlanCrossRefs/RatePlanCrossRef[@ResponseRatePlanGroupingCode="CltzProductElementID"]/@ResponseRatePlanCode');
      $isCopy = Arr::exists($payload, '_copy');
      $isCreate = !Arr::exists($payload, 'id');
      if ($isCreate || $isCopy) {
        // fetch newly created/copied meal plan
        return $this->getMealPlans(compact('id'));
      }
      // update/delete calls returns success
      return true;
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process meal plan.", $payload);
    }
  }

  public function getNearBy($payload = [])
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos', $this->hotelCodeContext())
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext(), function (XmlElement $el) {
          $el->add('HotelInfo', ['SendData' => 1]);
          $el->add('AreaInfo', ['SendRecreations' => 1, 'SendRefPoints' => 1]);
        });
    });

    try {
      $res = $this->sendRequest($xml);
      if (Arr::exists($payload, '_debug')) {
        return $res->asXML();
      }
      $area = $res->first('//AreaInfo');
      return $this->parseNearBy($area);
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to fetch what's nearby.", $payload);
    }
  }

  private function parseNearBy(XmlElement $area): ?array
  {
    if (!$area) {
      return null;
    }

    $refPoints = collect($area->xpath('//RefPoints/RefPoint'))->map(fn($point) => [
      'id'       => (string)$point['RefPointCategoryCode'],
      'code'     => (string)$point['Code'],
      'distance' => (int)$point['Distance'],
      'unit'     => Cultuzz::distanceUnitFromXML((string)$point['UnitOfMeasureCode']),
    ]);

    $recreations = collect($area->xpath('//Recreations/Recreation/RecreationDetails/RecreationDetail'))->map(fn($rec) => ['code' => (string)$rec['Code']]);

    $attractions = collect($area->xpath('//Attractions/Attraction'))->map(fn($attr) => ['code' => (string)$attr['AttractionCategoryCode']]);

    $data = collect(Cultuzz::REF_POINT_CODES)->mapWithKeys(fn($item) => [Str::camel($item) => []])->toArray();
    foreach ($refPoints as $refPoint) {
      $data[Str::camel(Arr::get(Cultuzz::REF_POINT_CODES, $refPoint['id']))][] = $refPoint;
    }
    return $data;
  }

  public function updateNearBy(array $payload): bool
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveContents', $this->hotelCodeContext())
        ->add('HotelDescriptiveContent', ['Overwrite' => 'true'] + $this->hotelCodeContext())
        ->add('AreaInfo', function (XmlElement $el) use ($payload) {
          $el->add('RefPoints', function (XmlElement $el) use ($payload) {
            $refPoints = $this->buildNearByRefPoint($payload);
            if (!empty($refPoints)) {
              foreach ($refPoints as $refPoint) {
                $el->add('RefPoint', $refPoint);
              }
            } else {
              $el->add('RefPoint');
            }
          });
          // $el->add('Attractions', function (XmlElement $el) use ($payload) {
          //   foreach ($payload['attractions'] as $attractionCode) {
          //     $el->add('Attraction', ['AttractionCategoryCode' => $attractionCode]);
          //   }
          // });
          // $el->add('Recreations', function (XmlElement $el) use ($payload) {
          //     $el->add('Recreation')
          //       ->add('RecreationDetails', function (XmlElement $el) use ($payload) {
          //         foreach ($payload['recreations'] as $recreationCode) {
          //           $el->add('RecreationDetail', ['Code' => $recreationCode]);
          //         }
          //       });
          // });
        });
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to process room type.", $payload);
    }
  }

  protected function buildNearByRefPoint(array $payload): array
  {
    $refPoint = [];
    if (!empty($payload)) {
      foreach ($payload as $category => $items) {
        foreach ($items as $item) {
          if ($category === 'publics') {
            $refPointCode = array_flip(Cultuzz::REF_POINT_PUBLIC_TRANS_CODES)[$item['code']];
          } else {
            $refPointCode = array_flip(Cultuzz::REF_POINT_CODES)[$category];
          }

          $refPoint[] = [
            'Distance'             => $item['distance'],
            'UnitOfMeasureCode'    => Cultuzz::distanceUnitForXML($item['unit']),
            'RefPointName'         => Cultuzz::getRefPointCategory($refPointCode)['appellation'],
            'Name'                 => $category,
            'RefPointCategoryCode' => $category === 'publics' ? 123 : $refPointCode,
            'Code'                 => $item['code'],
            'ExistsCode'           => 1,
          ];
        }
      }
    }
    return $refPoint;
  }


  public function getDescriptions($payload = [])
  {
    // get description from Room DB for Property
    $roomdbId = $this->hotel ? $this->hotel->roomdb_id : null;
    if($roomdbId){
      $property = $this->roomDbService->getProperty($roomdbId);
      $descriptions = $property['data']['result']['descriptions'];
      $descriptionTypes = $this->getDescriptionTypes();

      // Get RoomDb Languages dymanically from API
      $languages = $this->getRoomDbLanguages();

      $response = [];
      foreach ($descriptionTypes as $key => $value) {
        $response[$value] = ['langs' => []];
      }

      foreach ($descriptions as $value) {
        $typeId = $value['descriptionType']['id'];
        $response[$descriptionTypes[$typeId]]['langs'][$value['language']['code']] = $value['text'];
      }
      return $response;
    }
    return [];
  }

  protected function getDescriptionTypes($reverse = false){
    $res = $this->roomDbService->getDescriptionTypes();
    $descriptionTypes = $res['data']['result'];
    $arr = [
      'hotel-long' => 'description_long',
      'hotel-short' => 'description_short',
      'insider-tips' => 'insider_tips'
    ];
    $types = [];
    foreach ($descriptionTypes as $value) {
      if(array_key_exists($value['code'], $arr)){
        if($reverse){
          $types[$arr[$value['code']]] = $value['id'];
        }else{
          $types[$value['id']] = $arr[$value['code']];
        }
      }else{
        if($reverse){
          $types[$value['code']] = $value['id'];
        }else{
          $types[$value['id']] = $value['code'];
        }
      }
    }
    return $types;
  }

  private function parseDescriptions(XmlElement $data): ?array
  {
    if (!$data) {
      return null;
    }

    $result = [];
    foreach (Cultuzz::DESCRIPTION_CODE_NAMES as $code => $name) {
      $languages = collect($data->xpath(sprintf('//Descriptions/Description[@ContentCode="%s"]/Text', $code)))
        ->mapWithKeys(fn ($lang) => [(string)$lang['Language'] => trim((string)$lang)])
        ->toArray();
      $result[strtolower($name)]['langs'] = $languages ?: null;
    }

    return $result;
  }

  public function systemsData(): array
  {
    try {
      $software = $this->getSoftware()->groupBy('creator');
      $active = $this->getActivePMS();
      $activeSystem = Arr::get($active, 'system');

      $systems = $this->getSystems()->map(function ($sys) use (&$software, $activeSystem, $active) {
        $sys['software'] = Arr::get($software, $sys['id'], []);
        if ($activeSystem === $sys['id']) {
          $sys['active'] = Arr::get($active, 'software');
          $sys['dt'] = Arr::get($active, 'dt');
        }
        return $sys;
      });
      return $systems->toArray();
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, 'Failed to get data.');
    }
  }

  public function getSystems(): Collection
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) {
      $this->addPOS($el, ['auth' => $this->user->id, 'noRequester' => true]);
      $el->add('UniqueID', ['ID' => 'All', 'ID_Context' => 'PMS_Company', 'Type' => '21']);
    });

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      return collect($res->xpath('.//ProfileInfo'))
        ->map(fn (XmlElement $system) => $this->parseSystem($system));
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get systems list.");
    }
  }

  public function getSoftware()
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) {
      $this->addPOS($el, ['auth' => $this->user->id, 'noRequester' => true]);
      $el->add('UniqueID', ['ID' => 'All', 'ID_Context' => 'PMS_SoftwareList', 'Type' => '21']);
    });

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      return collect($res->xpath('.//ProfileInfo'))
        ->map(fn (XmlElement $software) => $this->parseSoftware($software));
    } catch(Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get systems list.");
    }
  }

  private function parseSystem(XmlElement $info): array
  {
    $ret = [
      'id'          => (int)$info->first('.//POS/Source/@AgentSine'),
      'name'        => (string)$info->first('.//CompanyName'),
      'description' => (string)$info->first('.//Text'),
      'enabled'     => (int)$info->first('.//UniqueID[@ID_Context="Status"]/@ID'),
    ];
    $url = (string)$info->first('.//URL');
    if (!empty($url) && !Str::startsWith($url, ['http://', 'https://'])) {
      $url = "https://{$url}";
    }
    $ret['url'] = $url;

    return $ret;
  }

  private function parseSoftware(XmlElement $info): array
  {
    $ret = [
      'id'            => (int)$info->first('./UniqueID/@ID'),
      'creator'       => (int)$info->first('.//Profile/@CreatorID'),
      'name'          => (string)$info->first('.//CompanyName'),
      'certificate'   => (int)$info->first('.//Certification[@ID="CertificationType"]'),
    ];
    $url = (string)$info->first('.//TPA_Extensions/UniqueID/@URL');
    if (!empty($url) && !Str::startsWith($url, ['http://', 'https://'])) {
      $url = "https://{$url}";
    }
    $ret['url'] = $url;

    return $ret;
  }

  public function getActivePMS(): ?array
  {
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveInfoRQ', function (XmlElement $el) {
      $this->addPOS($el);
      $el->add('HotelDescriptiveInfos')
        ->add('HotelDescriptiveInfo', $this->hotelCodeContext(), function (XmlElement $el) {
          $el->add('HotelInfo', ['SendData' => 1]);
        });
    });

    try {
      $res = $this->sendRequest($xml);
//      return $res->asXML();
      $hotel = $res->first('.//HotelDescriptiveContent');
      $info = $hotel->first('./HotelInfo/Descriptions/Description[@Name="data:PMS/CRS_Software"]');
      if (!isset($info)) {
        return null;
      }
      return [
        'system'   => (int)$info->stringAttr('CreatorID'),
        'software' => (int)$info->stringAttr('RecordID'),
        'dt'       => (string)$info->stringAttr('LastModifyDateTime'),
        'name'     => (string)$info->stringAttr('Description'),
        'lang'     => (string)$hotel->stringAttr('LanguageCode'),
        'currency' => (string)$hotel->stringAttr('CurrencyCode'),
      ];
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get active PMS system.");
    }
  }

  public function setActivePMS($payload = null): bool
  {
    if (!$payload) {
      // fetch current system first
      $payload = $this->getActivePMS();
      if (!$payload) {
        return true;
      }
      Arr::set($payload, '_delete', true);
    }
    $xml = XmlElement::createRoot('OTA_HotelDescriptiveContentNotifRQ', function (XmlElement $el) use ($payload) {
      $this->addPOS($el, ['auth' => $this->user->id]);
      $hotelAttrs = [
          'Overwrite'    => 'True',
          'LanguageCode' => Arr::get($payload, 'lang', 'EN'),
          'CurrencyCode' => Arr::get($payload, 'currency', $this->hotel->currency_code ?? 'EUR'),
        ] + $this->hotelCodeContext();
      $attrs = [
        'Name'      => 'data:PMS/CRS_Software',
        'CreatorID' => Arr::get($payload, 'system'),
        'RecordID'  => Arr::get($payload, 'software'),
        'Removal'   => Arr::get($payload, '_delete') ? 'True' : 'False',
      ];
      $el->add('HotelDescriptiveContents')
        ->add('HotelDescriptiveContent', $hotelAttrs)
        ->add('HotelInfo')
        ->add('Descriptions')
        ->add('Description', $attrs);
    });

    try {
      $this->sendRequest($xml);
      return true;
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get [de]activate PMS system.", $payload);
    }
  }

  public function getAutoContractors($channelId)
  {
    [$promo, $contract] = $this->getDefaultContractors($channelId);
    if (!$promo) {
      $this->createDefaultContractor($channelId, 'promo');
    }
    if (!$contract) {
      $this->createDefaultContractor($channelId, 'contract');
    }
    if (!$promo || !$contract) {
      [$promo, $contract] = $this->getDefaultContractors($channelId);
    }
    $codes = collect($promo['codes'])->merge(collect($contract['codes']));
    Arr::forget($promo, 'codes');
    Arr::forget($contract, 'codes');
    return compact('promo', 'contract', 'codes');
  }

  private function parseContractor($mode, XmlElement $profile = null)
  {
    if (!$profile) {
      return null;
    }
    return [
      'id'    => $profile->first('.//UniqueID[@ID_Context="ContractorID"]')->intAttr('ID'),
      'name'  => $profile->first('.//CompanyName')->stringAttr('CompanyShortName'),
      'codes' => collect($profile->xpath('.//CustLoyalty'))
        ->map(fn (XmlElement $el) => $this->parseContract($mode, $el))
        ->filter()
        ->values(),
    ];
  }

  private function parseContract($mode, XmlElement $contract = null)
  {
    if (!$contract) {
      return null;
    }
    $now = Carbon::now()->format('Y-m-d');
    return [
      'id'       => $contract->intAttr('MembershipID'),
      'name'     => $contract->stringAttr('CustomerType'),
      'mode'     => $mode,
      'code'     => $contract->stringAttr('VendorCode'),
      'active'   => strtolower($contract->stringAttr('LoyalLevel')) === 'active',
      'signup'   => $contract->stringAttr('SignupDate'),
      'from'     => $contract->stringAttr('EffectiveDate'),
      'until'    => $contract->stringAttr('ExpireDate'),
      'outdated' => $now > $contract->stringAttr('ExpireDate'),
    ];
  }

  private function getDefaultContractors($channelId)
  {
    $xml = XmlElement::createRoot('OTA_ReadRQ', function (XmlElement $el) use ($channelId) {
      $this->addPOS($el, ['contracts' => true, 'auth' => $this->user->id]);
      $el->add('UniqueID', [
        'Type'       => 1,
        'ID_Context' => 'ContractorID',
        'ID'         => 'All',
      ]);
    });

    try {
      $res = $this->sendRequest($xml);
      return collect(self::DEFAULT_CONTRACTORS)
        ->map(fn ($name, $mode) => $this->parseContractor($mode, $res->first('//ProfileInfo/Profile/Customer/Address/CompanyName[@CompanyShortName="'.$name.'" and @Code="'.$channelId.'"]/../../../..')))
        ->values();
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to get contracts.", compact('channelId'));
    }
  }

  private function createDefaultContractor($channelId, $mode)
  {
    $xml = XmlElement::createRoot('OTA_ProfileCreateRQ', function (XmlElement $el) use ($channelId, $mode) {
      $el->add('Profile', ['ProfileType' => 1], function (XmlElement $el) use ($channelId, $mode) {
        $el->add('Accesses')
           ->add('Access', ['ActionType' => 'Create', 'ID' => 'new']);
        $el->add('Customer')
           ->add('Address', function (XmlElement $el) use ($channelId, $mode) {
             $el->add('CityName', 'Unknown');
             $el->add('PostalCode', '00000');
             $el->add('CountryName', ['Code' => 1]); // CultSwitch server has bug with country codes here, so we're using Germany
             $el->add('CompanyName', [
               'CompanyShortName' => self::DEFAULT_CONTRACTORS[$mode],
               'Code'             => $channelId,
               'CodeContext'      => 'Distributor',
             ]);
           });
        $el->add('TPA_Extensions', function (XmlElement $el) {
          $this->addPOS($el, ['contracts' => true]);
        });
      });
    });

    try {
//      return $xml->asXML();
      $res = $this->sendRequest($xml);
      return $res->first('//UniqueID[@ID_Context="CLTZContractor"]/@ID');
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to create default contractor.", compact('channelId'));
    }
  }

  public function getContract($channelId, $contractId)
  {
    $_ = $this->getAutoContractors($channelId);
    return $_['codes']->firstWhere('id', $contractId);
  }

  public function modifyContract($payload)
  {
    $channelId = Arr::get($payload, 'cid');
    $_ = $this->getAutoContractors($channelId);
    $mode = Arr::get($payload, 'mode');
    $contractor = $_[$mode];
    $cid = Arr::get($contractor, 'id');
    $xml = XmlElement::createRoot('OTA_ProfileCreateRQ', function (XmlElement $el) use ($payload, $cid) {
      $el->add('Profile', ['ProfileType' => 1], function (XmlElement $el) use ($payload, $cid) {
        $isDelete = Arr::exists($payload, '_delete');
        $isUpdate = Arr::exists($payload, 'id') && !$isDelete;
        $isCreate = !$isUpdate && !$isDelete;
        $id = $isUpdate || $isDelete ? Arr::get($payload, 'id') : null;
        $attrs = [];
        if ($isCreate) {
          $attrs += [
            'SignupDate' => $el::xmlDate(),
            'VendorCode' => Arr::get($payload, 'code'),
          ];
        }
        if ($isCreate || $isUpdate) {
          $attrs += [
            'CustomerType' => Arr::get($payload, 'name'),
            'LoyalLevel' => 'Active',
            'EffectiveDate' => $el::xmlDate($this->carbonDate(Arr::get($payload, 'from'))),
            'ExpireDate' => $el::xmlDate($this->carbonDate(Arr::get($payload, 'until'))),
          ];
        }
        if ($isUpdate) {
          $attrs += ['MembershipID' => "Update:$id"];
        }
        if ($isDelete) {
          $attrs += ['MembershipID' => "Deleted:$id"];
        }
        $el->add('Accesses')
           ->add('Access', ['ID' => $cid]);
        $el->add('Customer')
           ->add('CustLoyalty', $attrs);
        $el->add('TPA_Extensions', function (XmlElement $el) {
          $this->addPOS($el, ['contracts' => true]);
        });
      });
    });

    try {
      $res = $this->sendRequest($xml);
      $id = $res->first('//UniqueID[@ID_Context="CLTZContract"]/@ID');
      return $this->getContract($channelId, $id);
    } catch (Throwable $e) {
      $this->throwError(__FUNCTION__, $e, "Failed to modify contract.", $payload);
    }
  }

  public function getContractRatePlans($code)
  {
    $plans = $this->getRoomTypesAndRatePlans(true)['plans'];
    return $plans->where('promo', $code)->values();
  }

  /**
   * Get countries or states list
   *
   * @param string $iso - Country ISOCode
   * @return array
   */
  public function getCountriesOrStates($iso = null)
  {
      $list = [];
      try {
        if ($iso) {
          // process states list
          $response = $this->roomDbService->getStatesFromCountry($iso);
          $list = $this->parseStatesRequestResponse($response['state_list']['result']);
          return $list;
          if ($response['status'] === 200) {
            // process countries list
            $list = $response['country_list']['result'];
          } else {
            Log::error("Failed to fetch countries/states list.", ['response' => $response]);
          }
        } else {
          $response = $this->roomDbService->getCountries();
          if ($response['status'] === 200) {
            // process countries list
            $list = $response['country_list']['result'];
          } else {
            Log::error("Failed to fetch countries/states list.", ['response' => $response]);
          }
        }
        return $list;
      } catch(Throwable $e) {
        $this->throwError(__FUNCTION__, $e, "Failed to fetch countries/states list.", compact('iso'));
      }
  }

  /**
   * Parse states list
   * from API response
   *
   * @param XmlElement|string $response
   * @return array
   */
  private function parseStatesRequestResponse($states)
  {
    $list = [];
    foreach ($states as $state) {
      $list[] = [
        'name' => (string) $state['name'],
        'code' => ((string) $state['code']),
        'country_id' => (string) $state['countryCode'],
        'country_iso' => (string) $state['countryCode']
      ];
    }
    return $list;
  }

  /**
   * Parse countries list
   * from API response
   *
   * @param XmlElement|string $response
   * @return array
   */
  private function parseCountriesRequestResponse($response)
  {
    $countries = $response->xpath('//StateProvisionsInfo/StateProvisions/Info/CountryCode');
    $list = [];

    foreach ($countries as $country) {
      $list[] = [
        'name' => (string)$country['Name'],
        'id' => intval((string)$country['ID']),
        'code' => (string)$country['ISOCode']
      ];
    }

    return $list;
  }

  public function purifyLangs($input)
  {
    foreach (self::$langs as $lang) {
      $key = "langs.$lang.desc";
      if ($_ = Arr::get($input, $key)) {
        Arr::set($input, $key, $this->htmlPurifier->purify($_));
      }
    }
    return $input;
  }

  /**
   * Update Description for Property
   * from API response
   *
   * @param Payload|mixed $response
   * @return array
   */
  public function updateDescription($payload, $hotel = null)
  {
    $hotelRoomdbId = $hotel->roomdb_id;
    if($hotelRoomdbId){
      $property = $this->roomDbService->getProperty($hotelRoomdbId);
      $descriptions = $property['data']['result']['descriptions'];
      $available_descriptions = [];
      foreach ($descriptions as $value) {
        $available_descriptions[$value['descriptionType']['id'].'_'.$value['language']['id']] = $value['id'];
      }
      $reqs = $this->createDescriptionRequest($payload, $available_descriptions);
      foreach ($reqs as $req) {
          $this->roomDbService->updatePropertyDescription($hotelRoomdbId, $req);
      }
      return ['success' => true];
    }
    return ['success' => false];
  }

  /**
   * protected Method to create requests array for Update Description to roomdb
   * New Desction req Post Request
   * Update Description Patch Request
   *
   * @param Payload|mixed $response
   * @return array
   */
  protected function createDescriptionRequest($payload, $available_descriptions)
  {
    $descriptions = $payload['descriptions'];

    $descriptionTypes = $this->getDescriptionTypes(true);
    $languages = $this->getRoomDbLanguages();

    $requests = [];
    foreach ($descriptions as $key => $description) {
      foreach ($languages as $id => $lang_key) {
        if(array_key_exists($lang_key,$description['langs'])){
          if(array_key_exists($descriptionTypes[$key].'_'.$id, $available_descriptions)){
            $requestType = 'PATCH';
            $decriptionId = $available_descriptions[$descriptionTypes[$key].'_'.$id];
          }else{
            $requestType = 'POST';
            $decriptionId = null;
          }

          $req = [
            'descriptionTypeId' => $descriptionTypes[$key],
            'languageId' => $id,
            'text' => $description['langs'][$lang_key],
            'decriptionId' => $decriptionId,
            'requestType' => $requestType
          ];
          array_push($requests, $req);
        }
      }
    }
    return $requests;
  }

  /**
   * protected Method to get Languages code and id pairs in Array from roomdb
   * @return array
   */
  public function getRoomDbLanguages()
  {
    $roomDbLanguages = $this->roomDbService->getLanguages();
    $languages = [];
    foreach ($roomDbLanguages['result'] as $value) {
      $languages[$value['id']] = $value['code'];
    }
    return $languages;
  }


  public function getCurrencyCodeBasedOnPayload($payload) {
    if (Arr::has($payload, 'currency_code')) {
      return Arr::get($payload, 'currency_code');
    }
    if (Arr::has($payload, 'currency')) {
      return Arr::get($payload, 'currency');
    }
    return 'EUR';
  }
}
