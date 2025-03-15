<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ApprovalNotification extends Notification
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

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Permintaan persetujuan baru untuk {$this->approval->type}.",
            'approval_id' => $this->approval->id
        ];
    }
}