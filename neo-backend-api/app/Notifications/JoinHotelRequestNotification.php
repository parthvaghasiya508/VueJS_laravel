<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Models\Hotel;
use App\Support\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class JoinHotelRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Hotel $hotel)
    {
      $this->locale = app()->getLocale();
      $this->hotel  = $hotel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
      $url = $this->getUrl($notifiable);
      return (new MailMessage)
        ->subject(__('mail.user_joining.subject'))
        ->line(__('mail.user_joining.message'))
        ->action(__('mail.user_joining.button'), $url)
        ->line(__('mail.contact', ['email' => '[info@cultbooking.com](mailto:info@cultbooking.com)']));
    }

    public function getUrl($notifiable)
  {
    Domain::setEffectiveExtranetDomain($this->hotel->group);
    $url = $this->transformApiRouteInWebRoute(URL::temporarySignedRoute(
      'users.hotel.join',
      Carbon::now()->addMinutes(Config::get('cultuzz.signed_link_expire', 60)),
      [
        'user'   => $notifiable->getKey(),
        'hash' => sha1($notifiable->getEmailForVerification()),
        'hotel' => $this->hotel->id,
      ],
      false
    ));

    return Domain::frontendUrl($url);
  }

  /**
   * Transform an api route
   * into a web one
   *
   * @param string $url
   *
   * @return string
   */
  public function transformApiRouteInWebRoute($url): string
  {
    return str_replace("/api/", "/", $url);
  }
}
