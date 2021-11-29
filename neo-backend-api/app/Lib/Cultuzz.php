<?php

namespace App\Lib;

use Illuminate\Support\Arr;

class Cultuzz {

  const LANGS = ['en', 'de', 'tr'];
  const FALLBACK_LANG = 'en';

  const BOOKABLE_ANYTIME = 0;
  const BOOKABLE_PERIODS = 1;
  const BOOKABLE_FROMTO = 2;
  const BOOKABLE_UNTIL = 3;
  const BOOKABLE_WITHIN = 4;

  const BOOKABLE = [
    self::BOOKABLE_ANYTIME,
    self::BOOKABLE_PERIODS,
    self::BOOKABLE_FROMTO,
    self::BOOKABLE_UNTIL,
    self::BOOKABLE_WITHIN,
  ];

  const PAYMENT_SEPA = 'sepa_debit';
  const PAYMENT_CARD = 'card';
  const PAYMENT_BANC = 'bancontact';

  const PAYMENT_METHODS = [
    self::PAYMENT_SEPA,
    self::PAYMENT_CARD,
    self::PAYMENT_BANC,
  ];

  const ROOM_TYPE_CODES = [
    ["id" => "1", "occupancy" => 2, "text" => "Apartment"],
    ["id" => "5", "occupancy" => 2, "text" => "Double bedroom"],
    ["id" => "6", "occupancy" => 3, "text" => "Three bedroom"],
    ["id" => "7", "occupancy" => 1, "text" => "Single bedroom"],
    ["id" => "10", "occupancy" => 5, "text" => "Five bedroom"],
    ["id" => "12", "occupancy" => 8, "text" => "Eight bedroom"],
    ["id" => "13", "occupancy" => 2, "text" => "Junior suite"],
    ["id" => "14", "occupancy" => 2, "text" => "Maisonette"],
    ["id" => "15", "occupancy" => 2, "text" => "Penthouse"],
    ["id" => "17", "occupancy" => 2, "text" => "Studio"],
    ["id" => "18", "occupancy" => 2, "text" => "Suite"],
    ["id" => "19", "occupancy" => 4, "text" => "Four Bedroom"],
    ["id" => "21", "occupancy" => 2, "text" => "Twin Room"],
    ["id" => "23", "occupancy" => 6, "text" => "Room with six beds"],
    ["id" => "24", "occupancy" => 7, "text" => "Room with seven beds"],
    ["id" => "25", "occupancy" => 9, "text" => "Nine bedroom"],
    ["id" => "27", "occupancy" => 10, "text" => "Ten bedroom"],
    ["id" => "29", "occupancy" => 12, "text" => "Twelve bedroom"],
    ["id" => "30", "occupancy" => 1, "text" => "Bed in Dormitory"],
    ["id" => "37", "occupancy" => 60, "text" => "Several rooms with half board"],
    ["id" => "38", "occupancy" => 100, "text" => "NonRoomUnlimited"],
    ["id" => "60", "occupancy" => 1, "text" => "Pitch"],
    ["id" => "61", "occupancy" => 40, "text" => "Self-contained cottage (single use)"],
    ["id" => "62", "occupancy" => 25, "text" => "Holiday house Tirol Sonnenfels"],
    ["id" => "63", "occupancy" => 18, "text" => "Rübezahl hut apartments"],
    ["id" => "64", "occupancy" => 70, "text" => "Self-catering apartment Gruppotel"],
    ["id" => "65", "occupancy" => 29, "text" => "Holiday house Alte Mühle"],
    ["id" => "66", "occupancy" => 12, "text" => "Room Sleeps 12"],
    ["id" => "67", "occupancy" => 40, "text" => "Room Sleeps 40"],
    ["id" => "68", "occupancy" => 16, "text" => "Room Sleeps 16"],
    ["id" => "69", "occupancy" => 30, "text" => "Room Sleeps 30"],
    ["id" => "70", "occupancy" => 20, "text" => "Room Sleeps 20"],
    ["id" => "71", "occupancy" => 14, "text" => "14 bed room"],
    ["id" => "99", "occupancy" => 1, "text" => "Default Room Type"],
    ["id" => "110", "occupancy" => 3, "text" => "Safari Tent"],
    ["id" => "112", "occupancy" => 3, "text" => "Cottage"],
    ["id" => "114", "occupancy" => 2, "text" => "Berth"],
    ["id" => "116", "occupancy" => 4, "text" => "Family Room"],
  ];

  const MAX_PRICE = 999999999.99;
  const MIN_PRICE = 0.01;
  const MEAL_TYPES = [
    ["id" => "4", "text" => "Breakfast"],
    ["id" => "5", "text" => "Half Board"],
    ["id" => "6", "text" => "Full Board"],
    ["id" => "7", "text" => "All Inclusive"],
  ];

  const HAC_CODES = [
    ["id" => 326, "text" => "Coffee lounge"],
    ["id" => 24, "text" => "Conference facilities"],
    ["id" => 141, "text" => "Continental breakfast"],
    ["id" => 109, "text" => "Direct dial telephone"],
    ["id" => 35, "text" => "Exercise gym"],
    ["id" => 274, "text" => "Fax service"],
    ["id" => 222, "text" => "Free high speed internet connection"],
    ["id" => 42, "text" => "Free parking"],
    ["id" => 240, "text" => "Hair dryer"],
    ["id" => 294, "text" => "Hotel safe deposit box (not room safe box)"],
    ["id" => 55, "text" => "Jacuzzi"],
    ["id" => 330, "text" => "Outdoor summer bar/café"],
    ["id" => 71, "text" => "Pool"],
    ["id" => 76, "text" => "Restaurant"],
    ["id" => 79, "text" => "Sauna"],
    ["id" => 83, "text" => "Solarium"],
    ["id" => 243, "text" => "Toilet"],
  ];

  public const REF_POINT_CATEGORY_CODES = [
    ['ota_ref' => 1, 'appellation' => 'Airport', 'ota_code' => true],
    ['ota_ref' => 2, 'appellation' => 'Amusement park', 'ota_code' => true,],
    ['ota_ref' => 3, 'appellation' => 'Arena', 'ota_code' => true,],
    ['ota_ref' => 4, 'appellation' => 'Bar', 'ota_code' => true,],
    ['ota_ref' => 5, 'appellation' => 'Bay', 'ota_code' => true,],
    ['ota_ref' => 6, 'appellation' => 'Beach', 'ota_code' => true,],
    ['ota_ref' => 7, 'appellation' => 'Boat dock', 'ota_code' => true,],
    ['ota_ref' => 8, 'appellation' => 'Bus station', 'ota_code' => true,],
    ['ota_ref' => 9, 'appellation' => 'Church', 'ota_code' => true,],
    ['ota_ref' => 10, 'appellation' => 'City center', 'ota_code' => true,],
    ['ota_ref' => 11, 'appellation' => 'Corporation', 'ota_code' => true,],
    ['ota_ref' => 12, 'appellation' => 'Educational institution', 'ota_code' => true,],
    ['ota_ref' => 13, 'appellation' => 'Ferry station', 'ota_code' => true,],
    ['ota_ref' => 14, 'appellation' => 'Financial district', 'ota_code' => true,],
    ['ota_ref' => 15, 'appellation' => 'Financial institution', 'ota_code' => true,],
    ['ota_ref' => 16, 'appellation' => 'Lake', 'ota_code' => true,],
    ['ota_ref' => 17, 'appellation' => 'Landmark', 'ota_code' => true,],
    ['ota_ref' => 18, 'appellation' => 'Library', 'ota_code' => true,],
    ['ota_ref' => 19, 'appellation' => 'Marina', 'ota_code' => true,],
    ['ota_ref' => 20, 'appellation' => 'Market', 'ota_code' => true,],
    ['ota_ref' => 21, 'appellation' => 'Medical facility', 'ota_code' => true,],
    ['ota_ref' => 22, 'appellation' => 'Metro/subway station', 'ota_code' => true,],
    ['ota_ref' => 23, 'appellation' => 'Monument', 'ota_code' => true,],
    ['ota_ref' => 24, 'appellation' => 'Museum', 'ota_code' => true,],
    ['ota_ref' => 25, 'appellation' => 'Park', 'ota_code' => true,],
    ['ota_ref' => 26, 'appellation' => 'Racetrack', 'ota_code' => true,],
    ['ota_ref' => 27, 'appellation' => 'Restaurant', 'ota_code' => true,],
    ['ota_ref' => 28, 'appellation' => 'River', 'ota_code' => true,],
    ['ota_ref' => 29, 'appellation' => 'School', 'ota_code' => true,],
    ['ota_ref' => 30, 'appellation' => 'Shopping center', 'ota_code' => true,],
    ['ota_ref' => 31, 'appellation' => 'Sports facility', 'ota_code' => true,],
    ['ota_ref' => 32, 'appellation' => 'Synagogue', 'ota_code' => true,],
    ['ota_ref' => 33, 'appellation' => 'Town center', 'ota_code' => true,],
    ['ota_ref' => 34, 'appellation' => 'Train station', 'ota_code' => true,],
    ['ota_ref' => 35, 'appellation' => 'University', 'ota_code' => true,],
    ['ota_ref' => 36, 'appellation' => 'Zoo', 'ota_code' => true,],
    ['ota_ref' => 37, 'appellation' => 'Local area', 'ota_code' => true,],
    ['ota_ref' => 1001, 'appellation' => 'Next Highway Exit', 'ota_code' => false,],
    ['ota_ref' => 1002, 'appellation' => 'Car', 'ota_code' => false,],
  ];

  public const REF_POINT_CODES = [
    1    => 'airports',
    10   => 'city_centers',
    34   => 'trains',
    123  => 'publics',
    1001 => 'motors',
  ];

  public const REF_POINT_PUBLIC_TRANS_CODES = [
    8  => 'bus',
    13 => 'ferry',
    22 => 'metro',
  ];

  public const DESCRIPTION_CODE_NAMES = [
    7  => 'description_long',
    8  => 'description_short',
    10 => 'Liability',
    11 => 'Location',
    12 => 'Directions',
    13 => 'Insider_Tips',
  ];

  public static array $distanceUnits = ['m' => 3, 'km' => 2, 'ft' => 7, 'mi' => 1];

  public const FEATURES = [
    1  => 'FSA',
    2  => 'ChannelHotelID',
    3  => 'BoardType',
    4  => 'BoardExtraPrice',
    6  => 'Pricebasis',
    8  => 'Occupancy',
    9  => 'Min',
    10 => 'Base',
    11 => 'Max',
    12 => 'NumerOfAdults',
    14 => 'Children',
    15 => 'bisAlter',
    16 => 'Fee',
    17 => 'Board',
    20 => 'OccupancyValue',
    22 => 'Price',
    23 => 'Period',
    25 => 'DurationType',
    27 => 'ContactDuration',
    29 => 'ContactEndDate',
    39 => 'Price',
    41 => 'Currency',
    43 => 'Token',
    45 => 'MSKU offer',
    47 => 'OfferStatus',
    49 => 'SiteID',
    51 => 'VariationStart',
    53 => 'VariationEnd',
    55 => 'AuctionID',
    76 => 'ChannelUserID',
    77 => 'ChannelPassword',
    78 => 'Multiple Accounts',
    80 => 'PriceModel',
    82 => 'ProductMappingStatus',
    85 => 'Qualifier',
    87 => 'ContractId',
    89 => 'PriceType',
    91 => 'ContractType',
    93 => 'LandLord ID',
    144 => 'Terminal ID',
    145 => 'Set URL',
    146 => 'Res URL',
    147 => 'Map URL'
  ];

  private const FEATURES_DATA = [
    2  => ['title' => 'Hotel ID', 'type' => 'String', 'subtype' => 'text', 'v' => 1],
    76 => ['title' => 'Username', 'type' => 'String', 'subtype' => 'text', 'v' => 1],
    77 => ['title' => 'Password', 'type' => 'String', 'subtype' => 'password', 'v' => 1],
  ];

  public const FEATURES_REGINFO = [2];
  public const FEATURES_IGNORED = [23,25,27,29];


  public static function getRefPointCategory(int $code)
  {
    return collect(self::REF_POINT_CATEGORY_CODES)->firstWhere('ota_ref', '=', $code);
  }

  public static function distanceUnitForXML($unit)
  {
    return Arr::get(self::$distanceUnits, $unit);
  }

  public static function distanceUnitFromXML($code)
  {
    return Arr::get(array_flip(self::$distanceUnits), $code);
  }

  public static function featureCode(string $name)
  {
    return Arr::get(array_flip(self::FEATURES), $name);
  }

  public static function featureData($code)
  {
    return Arr::get(self::FEATURES_DATA, $code);
  }

  public static function isFeatureRegInfo($code)
  {
    return in_array($code, self::FEATURES_REGINFO);
  }

  public static function isValidFeature($code)
  {
    return Arr::exists(self::FEATURES, $code) && !in_array($code, self::FEATURES_IGNORED);
  }

  /**
   * Generate password
   *
   * @return string
   */
  public static function generatePassword(): string
  {
    $faker = \Faker\Factory::create();

    $specials = str_split("[\'^£$%&*()}{@#~?<>,|=_+¬-]\" ");

    $randomPassword = $faker->shuffleString(
        implode($faker->randomElements($specials, 2))
        .$faker->bothify('???###')
        .$faker->toUpper($faker->bothify('??'))
    );

    return $randomPassword;
  }
}
