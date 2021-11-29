<?php

return [
  'invite_user' => [
    'subject'  => 'Benutzereinladung',
    'password' => 'Dies ist Ihr Passwort. Verwenden Sie es, um sich anzumelden: :password',
    'button'   => 'Daten eingeben',
  ],
  'footer_tip'  => 'Viel Spaß mit Ihrem neuen CultBooking-Konto. Ihr CultBooking Team.',
  'welcome'     => 'Willkommen bei CultBooking',
  'contact'     => 'Wenn Sie Fragen haben, kontaktieren Sie uns. Oder melden Sie sich bei: :email',
  'verify'      => [
    'subject' => 'E-Mail-Adresse bestätigen',
    'heading' => 'Sie haben es fast geschafft! Bevor Sie anfangen, geben Sie bitte Ihre Daten ein. Dazu klicken Sie bitte auf folgenden Link:',
    'button'  => 'Daten eingeben',
  ],
  'reset'       => [
    'subject'         => 'Mitteilung zum Zurücksetzen des Passworts',
    'greeting_name'   => 'Hallo :name,',
    'greeting'        => 'Hallo,',
    'heading'         => 'Möchten Sie Ihr CultBooking Passwort zurücksetzen?',
    'button'          => 'Passwort zurücksetzen',
    'link_expiration' => 'Dieser Link zum Zurücksetzen Ihres Passworts läuft in 1 Minute ab. | Dieser Link zum Zurücksetzen des Passworts läuft in :count Minuten ab.',
    'tip'             => 'Sollten Sie diese E-Mail irrtümlich erhalten haben, ignorieren Sie sie. :contact',
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
