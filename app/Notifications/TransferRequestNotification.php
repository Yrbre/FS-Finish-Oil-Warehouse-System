<?php

namespace App\Notifications;

use App\Models\TransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferRequestNotification extends Notification
{
    use Queueable;

    public const CREATED  = 'transfer_created';
    public const APPROVED = 'transfer_approved';
    public const SHIPPED  = 'transfer_shipped';
    public const RECEIVED = 'transfer_received';
    public const REJECTED = 'transfer_rejected';

    /**
     * @param string $event    salah satu konstanta di atas
     * @param string|null $extra keterangan tambahan (mis. nama item, alasan)
     */
    public function __construct(
        protected TransferRequest $request,
        protected string $event,
        protected ?string $extra = null,
    ) {}

    /**
     * In-app saja. Notifikasi alur transfer terjadi berkali-kali
     * sehari — lewat email akan terlalu ramai. Email hanya untuk
     * peringatan stok yang terjadwal harian.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        [$title, $message] = $this->content();

        return [
            'type'          => $this->event,
            'request_id'    => $this->request->id,
            'transfer_code' => $this->request->transfer_code,
            'title'         => $title,
            'message'       => $message,
            'url'           => route('transfer-requests.show', $this->request->id),
        ];
    }

    private function content(): array
    {
        $code  = $this->request->transfer_code;
        $dept  = $this->request->department->code ?? '-';
        $tujuan = $this->request->destinationWarehouse->name ?? '-';

        return match ($this->event) {
            self::CREATED => [
                'Permintaan baru menunggu approval',
                "{$code} dari {$dept} — " . $this->request->items->count() . " item, "
                    . "dibutuhkan " . $this->request->expected_date?->format('d-m-Y') . ".",
            ],
            self::APPROVED => [
                'Permintaan disetujui',
                "{$code} sudah disetujui. Menunggu penerbitan tanda terima barang.",
            ],
            self::SHIPPED => [
                'Barang dalam perjalanan',
                "{$code} sudah dikirim ke {$tujuan}. Konfirmasi setelah barang sampai.",
            ],
            self::RECEIVED => [
                'Barang telah diterima',
                "{$code} dikonfirmasi sampai di {$tujuan}.",
            ],
            self::REJECTED => [
                'Item ditolak',
                "{$code}: " . ($this->extra ?? 'satu item ditolak oleh IMC') . ".",
            ],
            default => ['Notifikasi', $code],
        };
    }
}
