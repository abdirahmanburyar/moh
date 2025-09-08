<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegistered extends Notification implements ShouldQueue
{
    use Queueable;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    protected $password;
    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $password = null)
    {
        $this->user = $user;
        $this->password = $password;
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
        $mailMessage = (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . ' - Your Account Details')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('We are pleased to inform you that an account has been created for you on the ' . config('app.name') . ' platform.')
            ->line('Here are your account details:')
            ->line('Username: ' . $this->user->username)
            ->line('Email: ' . $this->user->email);

        // Only include password if it was provided (for new users)
        if ($this->password) {
            $mailMessage->line('Password: ' . $this->password . ' (Please change this upon first login)');
        }

        // Add permission information if available
        if ($this->user->permissions->count() > 0) {
            $permissionNames = $this->user->permissions->pluck('name')->implode(', ');
            $mailMessage->line('Assigned Permission(s): ' . $permissionNames);
        }

        // Add warehouse information if available
        if ($this->user->warehouse) {
            $mailMessage->line('Assigned Warehouse: ' . $this->user->warehouse->name);
        }

        // Add facility information if available
        if ($this->user->facility) {
            $mailMessage->line('Assigned Facility: ' . $this->user->facility->name);
        }

        $mailMessage
            ->action('Login Now', url('/login'))
            ->line('Thank you for using our application!');

        return $mailMessage;
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
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }
}
