<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserNotificationBookingUpdated extends Notification implements ShouldQueue
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
            $phone = $isArray ? $this->appointment['phone'] : $this->appointment->phone;
            $status = $isArray ? $this->appointment['status'] : $this->appointment->status;
            $service = $isArray ? 
                ($this->appointment['service']['title'] ?? 'N/A') : 
                ($this->appointment->service->title ?? 'N/A');
            $staff = $isArray ? 
                ($this->appointment['employee']['user']['name'] ?? 'N/A') : 
                ($this->appointment->employee->user->name ?? 'N/A');
            $bookingDate = $isArray ? 
                $this->appointment['booking_date'] : 
                $this->appointment->booking_date;
            $bookingTime = $isArray ? 
                $this->appointment['booking_time'] : 
                $this->appointment->booking_time;

            return (new MailMessage)
                ->greeting('Hello ' . $name)
                ->line('Your Booking status has been updated to: ' . $status)
                ->subject('Booking Status Updated')
                ->line('**Appointment Details:**')
                ->line('Name: ' . $name)
                ->line('Phone: ' . $phone)
                ->line('Service: ' . $service)
                ->line('Staff: ' . $staff)
                ->line('Appointment Date: ' . Carbon::parse($bookingDate)->format('d M Y'))
                ->line('Slot Time: ' . $bookingTime)
                ->line('Thank you for using our application!');
        } catch (\Exception $e) {
            Log::error('Error sending booking update notification: ' . $e->getMessage(), [
                'appointment' => $this->appointment,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a simplified email in case of error
            return (new MailMessage)
                ->subject('Booking Status Updated')
                ->line('Your booking status has been updated.')
                ->line('There was an issue processing some of your booking details.')
                ->line('Our team will contact you shortly if needed.');
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
