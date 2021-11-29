<?php

namespace App\Notifications;

use App\Models\Group;
use App\Support\Domain;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue {

  use Queueable;

  public ?Group $group;

  public function __construct(?Group $group)
  {
    $this->onQueue('mail');
    $this->locale = app()->getLocale();
    $this->group = $group;
  }

  /**
   * Get the mail representation of the notification.
   *
   * @param mixed $notifiable
   *
   * @return MailMessage
   */
  public function toMail($notifiable)
  {
    $url = $this->getUrl($notifiable);
    return (new MailMessage)
      ->subject(__('mail.verify.subject'))
      ->line(__('mail.welcome'))
      ->line(__('mail.verify.heading'))
      ->action(__('mail.verify.button'), $url)
      ->line(__('mail.contact', ['email' => '[info@cultbooking.com](mailto:info@cultbooking.com)']));
  }

  public function getUrl($notifiable)
  {
    Domain::setEffectiveExtranetDomain($this->group);
    return Domain::frontendUrl(URL::temporarySignedRoute(
      'verification.verify',
      Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
      [
        'id'   => $notifiable->getKey(),
        'hash' => sha1($notifiable->getEmailForVerification()),
      ],
      false
    ));
  }

}
