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
        $currentTime = now()->format('H:i:s');

        // Pesan untuk Bidgar Wakaf
        if ($this->recipient === 'bidgar') {
            if ($this->action === 'reject') {
                $message = "Permintaan persetujuan data {$this->approval->type} oleh {$namaPimpinanJamaah} jam {$currentTime} telah ditolak.";
            } elseif ($this->action === 'reject_update') {
                $message = "Permintaan pembaruan data {$this->approval->type} oleh {$namaPimpinanJamaah} jam {$currentTime} telah ditolak.";
            } else {
                $message = "Permintaan data {$this->approval->type} oleh {$namaPimpinanJamaah} jam {$currentTime} telah diproses.";
            }
        }
        // Pesan untuk Pimpinan Jamaah
        elseif ($this->recipient === 'pimpinan_jamaah') {
            if ($this->action === 'reject') {
                $message = "Bidgar Wakaf telah menolak pembuatan data {$this->approval->type} jam {$currentTime}.";
            } elseif ($this->action === 'reject_update') {
                $message = "Bidgar Wakaf telah menolak pembaruan data {$this->approval->type} jam {$currentTime}.";
            } else {
                $message = "Permintaan data {$this->approval->type} jam {$currentTime} telah diproses.";
            }
        }

        return [
            'message' => $message,
            'type' => $this->approval->type,
            'status' => $this->approval->status,
            'details' => $data, // Sertakan data yang relevan
        ];
    }
}