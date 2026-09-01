<?php

namespace App\Notifications;

use App\Models\Department;
use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MinimumStockAlert extends Notification
{
    use Queueable;

    public function __construct(
        protected Item $item,
        protected Department $department,
        protected float $currentStock,
        protected float $localStock,
        protected float $minStock,
    ) {}

    /**
     * In-app selalu. Email hanya kalau diaktifkan di .env —
     * supaya aplikasi tetap jalan meski SMTP belum dikonfigurasi.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        if (config('notification.mail_enabled')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'          => 'min_stock',
            'item_id'       => $this->item->id,
            'item_no'       => $this->item->item_no,
            'item_desc'     => $this->item->item_desc,
            'department'    => $this->department->code,
            'min_stock'     => (float) $this->item->min_stock,
            'current_stock' => $this->currentStock,
            'local_stock'   => $this->localStock,
            'title'         => 'Stok di bawah minimum',
            'message'       => "{$this->item->item_no} tersisa " .
                number_format($this->currentStock, 2, ',', '.') . " kg, di bawah minimum " .
                number_format((float) $this->item->min_stock, 2, ',', '.') . " kg.",
            'url'           => route('items.detail', $this->item->id),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $inTransit = $this->currentStock - $this->localStock;

        return (new MailMessage)
            ->subject("[Finish Oil] Stok Minimum — {$this->item->item_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Stok **{$this->item->item_no} — {$this->item->item_desc}** milik department {$this->department->code} sudah di bawah ambang minimum.")
            ->line('Minimum: ' . number_format((float) $this->item->min_stock, 2, ',', '.') . ' kg')
            ->line('Total saat ini: ' . number_format($this->currentStock, 2, ',', '.') . ' kg')
            ->line('Di gudang sendiri: ' . number_format($this->localStock, 2, ',', '.') . ' kg')
            ->line('Masih di gudang IMC: ' . number_format($inTransit, 2, ',', '.') . ' kg')
            ->action('Lihat Kartu Stok', route('items.detail', $this->item->id))
            ->line('Segera ajukan permintaan pengadaan bila diperlukan.');
    }
}
