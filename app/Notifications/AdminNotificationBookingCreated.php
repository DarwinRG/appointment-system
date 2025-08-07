<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminNotificationBookingCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;

    public function __construct($appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        try {
            // Determine if appointment is an array or object
            $isArray = is_array($this->appointment);
            
            $name = $isArray ? $this->appointment['name'] : $this->appointment->name;
            $email = $isArray ? $this->appointment['email'] : $this->appointment->email;
            $phone = $isArray ? $this->appointment['phone'] : $this->appointment->phone;
            $service = $isArray ? 
                ($this->appointment['service']['title'] ?? 'N/A') : 
                ($this->appointment->service->title ?? 'N/A');
            $bookingDate = $isArray ? 
                $this->appointment['booking_date'] : 
                $this->appointment->booking_date;
            $bookingTime = $isArray ? 
                $this->appointment['booking_time'] : 
                $this->appointment->booking_time;

            return (new MailMessage)
                ->greeting('Hello Admin')
                ->subject('New Booking Created: ' . $name)
                ->line('**Appointment Details:**')
                ->line('Name: ' . $name)
                ->line('Email: ' . $email)
                ->line('Phone: ' . $phone)
                ->line('Service: ' . $service)
                ->line('Appointment Date: ' . Carbon::parse($bookingDate)->format('d M Y'))
                ->line('Slot Time: ' . $bookingTime)
                ->line('Thank you for using our application!');
        } catch (\Exception $e) {
            Log::error('Error sending admin booking creation notification: ' . $e->getMessage(), [
                'appointment' => $this->appointment,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a simplified email in case of error
            return (new MailMessage)
                ->subject('New Booking Created')
                ->line('A new booking has been created.')
                ->line('There was an issue processing some of the booking details.')
                ->line('Please check the booking system for complete information.');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
