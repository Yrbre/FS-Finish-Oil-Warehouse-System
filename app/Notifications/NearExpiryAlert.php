<?php

namespace App\Notifications;

use App\Models\ItemLocation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NearExpiryAlert extends Notification
{
    use Queueable;

    /** @param int $monthsLeft 3, 2, atau 1 bulan menjelang kedaluwarsa */
    public function __construct(
        protected ItemLocation $lot,
        protected int $monthsLeft,
    ) {}

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
            'type'             => 'near_expiry',
            'item_location_id' => $this->lot->id,
            'item_no'          => $this->lot->item->item_no,
            'item_desc'        => $this->lot->item->item_desc,
            'receiving_lot'    => $this->lot->receiving_lot,
            'warehouse'        => $this->lot->warehouse->name . ' - ' . $this->lot->warehouse->tag,
            'exp_date'         => $this->lot->exp_date?->toDateString(),
            'months_left'      => $this->monthsLeft,
            'qty_weight'       => (float) $this->lot->qty_weight,
            'title'            => "Mendekati kedaluwarsa ({$this->monthsLeft} bulan lagi)",
            'message'          => "{$this->lot->item->item_no} lot {$this->lot->receiving_lot} " .
                "(" . number_format((float) $this->lot->qty_weight, 2, ',', '.') . " kg) " .
                "kedaluwarsa " . $this->lot->exp_date?->format('d-m-Y') . ".",
            'url'              => route('item-locations.index'),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Finish Oil] {$this->monthsLeft} Bulan Menjelang Kedaluwarsa — {$this->lot->item->item_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Lot berikut akan kedaluwarsa dalam {$this->monthsLeft} bulan:")
            ->line("Item: {$this->lot->item->item_no} — {$this->lot->item->item_desc}")
            ->line("Lot: {$this->lot->receiving_lot}")
            ->line("Gudang: " . $this->lot->warehouse->name . ' - ' . $this->lot->warehouse->tag)
            ->line("Sisa: " . number_format((float) $this->lot->qty_weight, 2, ',', '.') . ' kg')
            ->line("Kedaluwarsa: " . $this->lot->exp_date?->format('d-m-Y'))
            ->action('Lihat Stok Gudang', route('item-locations.index'))
            ->line('Prioritaskan pemakaian lot ini.');
    }
}
