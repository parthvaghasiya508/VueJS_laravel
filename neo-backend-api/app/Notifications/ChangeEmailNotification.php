<?php

namespace App\Notifications;

use App\Models\Group;
use App\Support\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ChangeEmailNotification extends Notification {

  use Queueable;

  /**
   * @var string
   */
  public $email;

  /**
   * @var string
   */
  public $token;

  public ?Group $group;

  /**
   * Create a new notification instance.
   *
   * @param $email
   * @param $token
   *
   * @return void
   */
  public function __construct($email, $token, ?Group $group = null)
  {
    $this->onQueue('mail');
    $this->locale = app()->getLocale();
    $this->email = $email;
    $this->token = $token;
    $this->group = $group;
  }

  /**
   * Get the notification's delivery channels.
   *
   * @param mixed $notifiable
   *
   * @return array
   */
  public function via($notifiable)
  {
    return ['mail'];
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
    Domain::setEffectiveExtranetDomain($this->group);
    $url = Domain::frontendUrl(route('email.update', [
      'token' => $this->token,
      'email' => $this->email,
    ], false));
    return (new MailMessage)
      ->subject(__('mail.change.change_email_notification'))
      ->line(__('mail.change.you_are_receiving_this_email'))
      ->action(__('mail.change.change_my_email'), $url)
      ->line(__('mail.change.link_will_expire',
        ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
      ->line(__('mail.change.if_did_not_request'));
  }
}
