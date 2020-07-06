<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSeatsAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array  $flight
     * @param  array  $new_seats
     * @return void
     */
    public function __construct($flight, $new_seats)
    {
        $this->flight = $flight;
        $this->new_seats = $new_seats;
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
            ->subject('Vuelo '.$this->flight->number.' | Asientos asignados')
            ->line(
                'Aquí tienes tus asientos para el vuelo número '.$this->flight->number.
                ' (ruta: '.$this->flight->origin.'-'.$this->flight->destination.')'
            )
            ->action(implode(', ', $this->new_seats), config('app.url') . '/my-flights')
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
