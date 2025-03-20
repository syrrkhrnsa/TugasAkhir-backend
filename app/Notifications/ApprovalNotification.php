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
    protected $action;
    protected $recipient;

    public function __construct($approval, $action, $recipient)
    {
        $this->approval = $approval;
        $this->action = $action;
        $this->recipient = $recipient;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $data = json_decode($this->approval->data, true);
        $previousData = $data['previous_data'] ?? [];

        // Ambil NamaPimpinanJamaah, jika kosong pakai previous_data
        $namaPimpinanJamaah = $data['NamaPimpinanJamaah'] ?? $previousData['NamaPimpinanJamaah'] ?? 'Unknown';

        // Jika penerima adalah Bidgar Wakaf
        if ($this->recipient === 'bidgar') {
            if ($this->action === 'create') {
                $message = "Penambahan data {$this->approval->type} oleh {$namaPimpinanJamaah}.";
                $details = $data;
            } else {
                $type = str_replace("_update", "", $this->approval->type);
                $message = "Pembaharuan data {$type} oleh {$namaPimpinanJamaah}.";
                $details = [
                    'previous_data' => $previousData,
                    'updated_data' => $data['updated_data'] ?? [],
                ];
            }
        }
        // Jika penerima adalah Pimpinan Jamaah
        elseif ($this->recipient === 'pimpinan_jamaah') {
            if ($this->action === 'create') {
                $message = "Bidgar Wakaf telah menyetujui pembuatan data {$this->approval->type}.";
            } else {
                $type = str_replace("_update", "", $this->approval->type);
                $message = "Bidgar Wakaf telah menyetujui pembaharuan data {$type}.";
            }
            $details = $data;
        }

        return [
            'message' => $message,
            'type' => $this->approval->type,
            'status' => $this->approval->status,
            'details' => $details,
	        'id_approval' => $this->approval->id,
        ];
    }
}



