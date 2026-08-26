<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ReceiptOfGoods extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'receipt_of_goods';

    protected $fillable = [
        'letter_number',
        'letter_date',
        'transfer_request_id',
        'responsibility_id',
        'photo',
    ];

    protected $casts = [
        'letter_date' => 'date',
    ];

    /** User yang membuat tanda terima ini. */
    public function responsibility()
    {
        return $this->belongsTo(User::class, 'responsibility_id');
    }

    public function transferRequest()
    {
        return $this->belongsTo(TransferRequest::class, 'transfer_request_id', 'id');
    }


    public static function generateNomorSurat(?string $tanggal = null): string
    {
        return DB::transaction(function () use ($tanggal) {
            // pakai tanggal yang dikirim, kalau tidak ada pakai hari ini
            $date = $tanggal ? Carbon::parse($tanggal) : Carbon::now();

            $tahun = $date->format('Y');
            $bulanRomawi = self::bulanKeRomawi((int) $date->format('n'));

            // cari nomor urut terakhir berdasarkan TAHUN dari letter_date
            $lastData = self::whereYear('letter_date', $tahun)
                ->orderBy('letter_date', 'desc')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastData && $lastData->letter_number) {
                $lastNoUrut = (int) explode('/', $lastData->letter_number)[0];
                $noUrut = $lastNoUrut + 1;
            } else {
                $noUrut = 1;
            }

            $noUrutFormatted = str_pad($noUrut, 4, '0', STR_PAD_LEFT);

            return "{$noUrutFormatted}/IMC/{$bulanRomawi}/{$tahun}";
        });
    }

    /**
     * Konversi angka bulan (1-12) ke angka romawi
     */
    public static function bulanKeRomawi(int $bulan): string
    {
        $romawi = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $romawi[$bulan] ?? '';
    }
}
