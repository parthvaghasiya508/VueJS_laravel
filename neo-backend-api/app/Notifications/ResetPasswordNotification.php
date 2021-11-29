<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use App\Support\Domain;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword implements ShouldQueue {

  use Queueable;

  public ?Group $group;

  public function __construct($token, ?Group $group = null)
  {
    parent::__construct($token);
    $this->group = $group;
    $this->onQueue('mail');
    $this->locale = app()->getLocale();
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
    /** @var User $notifiable */
    $name = $notifiable->fullName;
    $greeting = 'mail.reset.greeting';
    if (!is_null($name)) {
      $greeting .= '_name';
    }
    Domain::setEffectiveExtranetDomain($this->group);
    $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
    $url = Domain::frontendUrl(route('password.reset', [
      'token' => $this->token,
      'email' => $notifiable->getEmailForPasswordReset(),
    ], false));
    $contact = __('mail.contact', ['email' => '[info@cultbooking.com](mailto:info@cultbooking.com)']);
    $mail = (new MailMessage)
      ->subject(__('mail.reset.subject'))
      ->line(__($greeting, compact('name')))
      ->line(__('mail.reset.heading'))
      ->action(__('mail.reset.button'), $url)
      ->line(trans_choice('mail.reset.link_expiration', $count, compact('count')))
      ->line(__('mail.reset.tip', compact('contact')));

    return $mail;
  }

}
