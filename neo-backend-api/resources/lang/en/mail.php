<?php

return [
  'invite_user' => [
    'subject'  => 'User invitation',
    'password' => 'This is your password, use it to login: :password',
    'button'   => 'Enter your details',
  ],
  'user_joining' => [
    'subject'  => 'Hotel Access',
    'message'  => 'You\'ve been added to a new Property. Please connect to see.',
    'button'   => 'Connect to see',
  ],
  'footer_tip'  => 'Enjoy your new CultBooking account. Your CultBooking team',
  'welcome'     => 'Welcome to CultBooking,',
  'contact'     => 'If you have any question, contact us. Or, say hi at: :email',
  'verify'      => [
    'subject' => 'Verify Email Address',
    'heading' => 'You are nearly there! To get started, please enter your details by clicking on the following link:',
    'button'  => 'Enter your details',
  ],
  'reset'       => [
    'subject'         => 'Reset Password Notification',
    'greeting_name'   => 'Hi :name,',
    'greeting'        => 'Hi,',
    'heading'         => 'Need to reset your CultBooking password? Click here:',
    'button'          => 'Reset my password',
    'link_expiration' => 'This password reset link will expire in 1 minute. | This password reset link will expire in :count minutes.',
    'tip'             => 'If you think you received this email by mistake, feel free to ignore it. :contact',
  ],
  'change'      => [
    'change_email_notification'    => 'Change Email Notification',
    'you_are_receiving_this_email' => 'You are receiving this email because we received an email change request for your account.',
    'change_my_email'              => 'Change my email',
    'link_will_expire'             => 'This email change link will expire in :count minutes.',
    'if_did_not_request'           => 'If you did not request an email change, no further action is required.',
    'token_invalid'                => 'The token is invalid or expired.',
  ],
];
