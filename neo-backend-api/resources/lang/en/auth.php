<?php

return [
  'failed'                => 'These credentials do not match our records.',
  'throttle'              => 'Too many login attempts. :try_again_in',
  'throttle_try_again_in' => 'Please try again in 1 second. | Please try again in :seconds seconds.',
  'agents'                => [
    'failed'    => 'Login failed. Please, try again later.',
    'none'      => 'Invalid login link.',
    'used'      => 'Login link has already been used.',
    'expired'   => 'Login link has expired.',
    'forbidden' => [
      'login' => 'Direct login is not allowed',
      'reset' => 'Password reset is not allowed',
    ],
  ],
];
