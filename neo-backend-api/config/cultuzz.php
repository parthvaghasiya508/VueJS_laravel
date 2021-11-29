<?php

return [

  'booking_domain'            => env('CULT_BOOKING_DOMAIN'),
  'extranet_domain'           => env('CULT_EXTRANET_DOMAIN'),

  'endpoint'                  => env('CULT_ENDPOINT'),
  'endpoint_basic'            => env('CULT_ENDPOINT_BASIC'),
  'endpoint_collab'           => env('CULT_ENDPOINT_COLLAB'),
  'endpoint_mappings'         => env('CULT_ENDPOINT_MAPPINGS'),
  'endpoint_setting'          => env('CULT_ENDPOINT_SETTING'),

  'endpoint_roomrates'        => env('CULT_ENDPOINT_ROOMRATES'),
  'endpoint_rest_roomrates'   => env('CULT_ENDPOINT_REST_ROOMRATES'),
  'xapi_key'                  => env('CULT_XAPI_KEY'),
  'endpoint_rest_channelshealth'   => env('CULT_ENDPOINT_REST_CHANNELSHEALTH'),
  'channels_health_api_key'   => env('CULT_CHANNELS_HEALTH_API_KEY'),

  'agent_sine'                => env('CULT_AGENT_SINE'),
  'agent_sine_collab'         => env('CULT_AGENT_SINE_COLLAB'),
  'agent_dutycode'            => env('CULT_AGENT_DUTYCODE'),
  'agent_dutycode_collab'     => env('CULT_AGENT_DUTYCODE_COLLAB'),

  'register_token'            => env('CULT_REGISTER_TOKEN'),
  'auth_token'                => env('CULT_AUTH_TOKEN'),

  'signature_key'             => env('CULT_SIGNATURE_KEY'),

  'notify_email'              => env('CULT_NOTIFY_EMAIL', null),

  'default_pull_channel'      => env('CULT_DEFAULT_PULL_CHANNEL', '55075'),
  'enabled_channels'          => explode(',', env('CULT_ENABLED_CHANNELS', '')),
  'push_channels_login'       => env('CULT_PUSH_CHANNELS_LOGIN'),
  'push_channels_password'    => env('CULT_PUSH_CHANNELS_PASSWORD'),

  'cultdata_api'              => env('CULT_DATA_ENDPOINT'),
  'cultdata_key'              => env('CULT_DATA_API_KEY'),
  'cultdata_payment_key'      => env('CULT_DATA_API_KEY_FOR_PAYMENT'),

  'creditor_id'               => env('CREDITOR_IDENTIFIER'),

  'card_details_iframe_url'   => env('CULT_CARD_DETAILS_IFRAME_URL'),

  'signed_link_expire'        => env('SIGNED_LINK_EXPIRE', 60),

  'stripe_accounts'           => [
    env('STRIPE_SECRET'),
    env('STRIPE_SECRET_MAPPING_MASTER')
  ],

  'numverify_url'             => env('NUMVERIFY_URL'),
  'numverify_access_key'      => env('NUMVERIFY_ACCESS_KEY'),
  'roomdb_extranet_secret'    => env('ROOMDB_EXTRANET_SECRET'),
  'roomdb_user_email'         => env('ROOMDB_USER_EMAIL'),
  'roomdb_user_pwd'           => env('ROOMDB_USER_PWD')
];
