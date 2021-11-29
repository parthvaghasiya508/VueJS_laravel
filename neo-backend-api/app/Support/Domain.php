<?php

namespace App\Support;

use App\Models\Agent;
use App\Models\Group;
use App\Models\Hotel;
use Illuminate\Support\Arr;

class Domain {

  static function getExtranetDomain(): string
  {
    return config('cultuzz.extranet_domain');
  }

  static function getBookingDomain(): string
  {
    return config('cultuzz.booking_domain');
  }

  static function frontendUrl(string $path): string
  {
    return config('app.frontend_url').$path;
  }

  /**
   * @param mixed $obj
   * @param boolean $schema
   *
   * @return string
   */
  static function getEffectiveExtranetDomain($obj = null, bool $schema = true): string
  {
    /** @var Group $g */
    $g = null;
    if (!isset($obj)) {
      $g = Group::getCurrent();
    } elseif ($obj instanceof Hotel) {
      $g = $obj->group;
    } elseif ($obj instanceof Agent) {
      $g = $obj->group;
    } elseif ($obj instanceof Group) {
      $g = $obj;
    }
    $r = isset($g) ? $g->effectiveExtranetDomain : config('cultuzz.extranet_domain');
    return $schema ? "https://$r" : $r;
  }

  static function setEffectiveExtranetDomain($obj = null)
  {
    config(['app.frontend_url' => self::getEffectiveExtranetDomain($obj)]);
  }

  static function validateDNS(Group $group)
  {
    /*
     * [b_domain]     => [cultuzz.booking_domain]
     * [e_domain]     => [cultuzz.extranet_domain]
     * api.[e_domain] => api.[cultuzz.extranet_domain]
     */
    $checks = [
      idn_to_ascii($group->e_domain)        => config('cultuzz.extranet_domain'),
      'api.'.idn_to_ascii($group->e_domain) => 'api.'.config('cultuzz.extranet_domain'),
    ];
    if ($group->b_domain) {
      $checks += [
        idn_to_ascii($group->b_domain) => config('cultuzz.booking_domain'),
      ];
    }
    $status = Group::DOMAIN_STATUS_OK;
    foreach ($checks as $src => $trg) {
      $recs = dns_get_record($src, DNS_CNAME);
      if (count($recs) !== 1) {
        $status = Group::DOMAIN_STATUS_INVALID;
        break;
      }
      $rec = $recs[0];
      if (Arr::get($rec, 'host') !== $src || Arr::get($rec, 'type') !== 'CNAME' || Arr::get($rec, 'target') !== $trg) {
        $status = Group::DOMAIN_STATUS_INVALID;
        break;
      }
    }
    return $status;
  }
}
