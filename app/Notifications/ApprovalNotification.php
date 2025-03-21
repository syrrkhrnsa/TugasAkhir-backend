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

        $namaPimpinanJamaah = $data['NamaPimpinanJamaah'] ?? $previousData['NamaPimpinanJamaah'] ?? null;

    // Jika NamaPimpinanJamaah masih kosong, ambil dari tabel users berdasarkan user_id
    if (empty($namaPimpinanJamaah)) {
        $userId = $this->approval->user_id; // Asumsikan user_id tersedia di approval
        $user = \App\Models\User::find($userId); // Query ke tabel users

        if ($user) {
            $namaPimpinanJamaah = $user->name; // Asumsikan kolom nama di tabel users adalah 'name'
        } else {
            $namaPimpinanJamaah = 'Unknown';
        }
    }

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