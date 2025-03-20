<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalNotification extends Notification
{
    use Queueable;

    protected $approval; // Properti untuk menyimpan data approval
    protected $action;   // Properti untuk menyimpan jenis aksi (create/update)
    protected $recipient; // Properti untuk menyimpan penerima notifikasi (bidgar/pimpinan_jamaah)

    public function __construct($approval, $action, $recipient)
    {
        $this->approval = $approval; // Inisialisasi properti approval
        $this->action = $action;     // Inisialisasi properti action
        $this->recipient = $recipient; // Inisialisasi properti recipient
    }

    public function via($notifiable)
    {
        return ['database']; // Notifikasi disimpan di database
    }

    public function toArray($notifiable)
    {
        $data = json_decode($this->approval->data, true);
        $namaPimpinanJamaah = $data['NamaPimpinanJamaah'] ?? 'Unknown';
        $currentTime = now()->format('H:i:s');

        // Pesan untuk Bidgar Wakaf
        if ($this->recipient === 'bidgar') {
            if ($this->action === 'create') {
                $message = "Penambahan data {$this->approval->type} oleh {$namaPimpinanJamaah} jam {$currentTime}.";
                $details = $data; // Data yang dicreate
            } else {
                $message = "Pembaruan data {$this->approval->type} oleh {$namaPimpinanJamaah} jam {$currentTime}.";
                $details = [
                    'previous_data' => $data['previous_data'] ?? null,
                    'updated_data' => $data['updated_data'] ?? null,
                ];
            }
        }
        // Pesan untuk Pimpinan Jamaah
        elseif ($this->recipient === 'pimpinan_jamaah') {
            if ($this->action === 'create') {
                $message = "Bidgar Wakaf telah menyetujui pembuatan data {$this->approval->type} jam {$currentTime}.";
            } else {
                $message = "Bidgar Wakaf telah menyetujui pembaruan data {$this->approval->type} jam {$currentTime}.";
            }
            $details = $data; // Sertakan data yang relevan
        }

        return [
            'message' => $message,
            'type' => $this->approval->type,
            'status' => $this->approval->status,
            'details' => $details, // Sertakan data yang relevan
            'id_approval' => $this->approval->id,
        ];
    }
}