<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectionNotification extends Notification
{
    use Queueable;

    protected $approval; // Properti untuk menyimpan data approval
    protected $action;   // Properti untuk menyimpan jenis aksi (reject/reject_update)
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

        // Pesan untuk Bidgar Wakaf
        if ($this->recipient === 'bidgar') {
            if ($this->action === 'reject') {
                $message = "Permintaan persetujuan data {$this->approval->type} oleh {$namaPimpinanJamaah}.";
            } elseif ($this->action === 'reject_update') {
                $message = "Permintaan pembaharuan data {$this->approval->type} oleh {$namaPimpinanJamaah}.";
            } else {
                $message = "Permintaan data {$this->approval->type} oleh {$namaPimpinanJamaah} telah diproses.";
            }
        }
        // Pesan untuk Pimpinan Jamaah
        elseif ($this->recipient === 'pimpinan_jamaah') {
            if ($this->action === 'reject') {
                $message = "Bidgar Wakaf telah menolak pembuatan data {$this->approval->type}.";
            } elseif ($this->action === 'reject_update') {
                $message = "Bidgar Wakaf telah menolak pembaharuan data {$this->approval->type}.";
            } else {
                $message = "Permintaan data {$this->approval->type} telah diproses.";
            }
        }

        return [
            'message' => $message,
            'type' => $this->approval->type,
            'status' => $this->approval->status,
            'details' => $data, // Sertakan data yang relevan
		    'id_approval' => $this->approval->id,
        ];
    }
}