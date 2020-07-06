<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoSeatsAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array  $flight
     * @return void
     */
    public function __construct($flight)
    {
        $this->flight = $flight;
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
        return (new MailMessage)
            ->subject('Vuelo '.$this->flight->number.' | Asientos no cambiados')
            ->line(
                'Debido a ser el único usuario en apuntarse al vuelo '.$this->flight->number.
                ' (ruta: '.$this->flight->origin.'-'.$this->flight->destination.') '.
                'tus asientos originales no han cambiado.'
            )
            ->action('¡Apúntame a otro vuelo!', config('app.url') . '/new-flight')
            ->line('Sentimos que no haya ningún otro PirataFlyer en este vuelo')
            ->line(
                'Por favor, sigue usando la app y recuerda, cuanta más gente nos'.
                ' conozca, más posibilidad de conseguir mejores sitios ;)'
            )
            ->line('Buen viaje y, ¡gracias por usar PirataFly!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
