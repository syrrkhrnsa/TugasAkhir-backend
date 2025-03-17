<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return ['database']; // Notifikasi disimpan di database
    }

    public function toArray($notifiable)
    {
        $data = json_decode($this->approval->data, true);

        return [
            'message' => 'Permintaan pembaruan data tanah telah dikirim.',
            'type' => $this->approval->type,
            'status' => $this->approval->status,
            'previous_data' => $data['previous_data'] ?? null, // Data sebelum diubah
            'updated_data' => $data['updated_data'] ?? null,   // Data setelah diubah
        ];
    }
}