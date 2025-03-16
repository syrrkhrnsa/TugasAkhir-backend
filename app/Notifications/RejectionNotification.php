<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectionNotification extends Notification
{
    use Queueable;

    protected $approval;

    public function __construct($approval)
    {
        $this->approval = $approval;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Permintaan persetujuan ditolak.',
            'data' => $this->approval->data,
            'type' => $this->approval->type,
            'status' => $this->approval->status,
        ];
    }
}