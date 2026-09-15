# Code Review — Finish Oil System

Review manual atas `app/` (Services, Controllers, Models, Repositories), `routes/web.php`, Form Requests, Notifications, dan scheduler (`app/Console/Kernel.php`). Fokus: bug fungsional dan fungsi yang tidak lagi dipakai (dead code). Referensi path relatif terhadap root project.

> Catatan cakupan: seluruh layer inti (Service, Controller, Repository Eloquent, Model utama, routes) dibaca penuh baris-per-baris. Blade views, migration lama, dan test dibaca sebagian/spot-check saja — bukan audit menyeluruh.

---

## Ringkasan

| # | Severity | Temuan | Lokasi |
|---|----------|--------|--------|
| 1 | **Kritis** | Scheduler alert jalan tiap menit + `checkMinimumStock()` tanpa dedup → spam notifikasi tanpa henti | `app/Console/Kernel.php:15`, `app/Services/StockAlertService.php:24-87` |
| 2 | Tinggi | Department dengan ambang minimum stock tapi belum pernah punya lot tidak pernah dicek/diberi tahu | `app/Services/StockAlertService.php:39-46` |
| 3 | Tinggi | Tabel "Rekap per item" (staff) pakai kolom `items.min_stock` lama, tidak sinkron dengan sistem `minimum_stocks` per department | `app/Repositories/Eloquents/ItemLocationRepository.php:302-351`, `app/Http/Controllers/ItemLocationController.php:158-166` |
| 4 | Tinggi | Race condition bisa membuat `receiving_lot` duplikat — tidak ada unique constraint di DB | `app/Services/ItemLocationService.php:200-218` |
| 5 | Rendah | Urutan pemrosesan level near-expiry berlawanan dengan komentarnya sendiri | `app/Services/StockAlertService.php:98-137` |
| 6 | — | 2 fungsi repository tidak pernah dipanggil (dead code) | lihat bagian [Fungsi Tidak Digunakan](#fungsi-tidak-digunakan) |
| 7 | Info | Duplikasi logic antara `getMonthlyStockCard()` dan `buildStockCard()`/`getStaffMonthlyStockCard()` | `app/Services/StockLedgerService.php` |

---

## 1. [KRITIS] Scheduler alert jalan tiap menit, `checkMinimumStock()` tanpa dedup

**`app/Console/Kernel.php:15-20`**
```php
$schedule->command('stock:check-alerts')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/stock-alerts.log'));
```

Command ini memanggil `StockAlertService::checkMinimumStock()` dan `checkNearExpiry()` (`app/Services/StockAlertService.php`). Masalahnya ada di dua tempat sekaligus:

- **`checkMinimumStock()` (baris 24-87) sama sekali tidak punya mekanisme dedup.** Berbeda dari `checkNearExpiry()` yang menandai lot dengan `expiry_alerted_level` supaya tidak mengirim ulang, `checkMinimumStock()` akan mengirim notifikasi baru ke SEMUA user department setiap kali method ini dipanggil, selama stok masih di bawah ambang.
- **Scheduler mendaftarkannya `everyMinute()`**, bukan harian. Komentar di kode sendiri (`checkNearExpiry()` docblock, baris 89-97) menyebut ini sebagai "scheduler harian" — jadi intent aslinya memang daily, tapi implementasinya `everyMinute()`.

**Dampak:** selama ada satu saja item yang stoknya di bawah minimum untuk suatu department, SETIAP user di department itu akan menerima notifikasi database baru (dan email, jika `notification.mail_enabled` aktif) **setiap menit, tanpa henti**, sampai stok direstock. Dalam sehari itu bisa 1.440 notifikasi per user per item. Ini akan membanjiri tabel `notifications`, mailbox user, dan dropdown lonceng notifikasi.

**Rekomendasi:**
- Ubah `->everyMinute()` menjadi `->daily()` (atau jadwal yang sesuai kebutuhan bisnis).
- Tambahkan dedup pada `checkMinimumStock()` yang serupa dengan `expiry_alerted_level` — misalnya kolom "last alerted at/level" per (item, department), atau throttle berbasis tanggal, supaya tidak mengirim ulang notifikasi yang sama dalam periode singkat.

---

## 2. [TINGGI] Department dengan ambang minimum tapi belum pernah punya lot tidak pernah dicek

**`app/Services/StockAlertService.php:39-46`**
```php
$owners = ItemLocation::where('item_id', $item->id)
    ->whereNull('deleted_at')
    ->select('demander_id')
    ->distinct()
    ->pluck('demander_id');

foreach ($owners as $demanderId) {
    ...
}
```

Daftar department yang dicek diambil dari `demander_id` yang **benar-benar punya baris `item_locations`** untuk item tersebut. Padahal `MinimumStock` (`app/Models/MinimumStock.php`) sudah punya `department_id` langsung, dan `Item::minStockFor()` (`app/Models/Item.php:49-55`) memang dirancang untuk dicek per department dari tabel `minimum_stocks`.

**Skenario gagal:** Department X mengatur ambang minimum untuk Item Y lewat menu Minimum Stock, tapi department X belum pernah menerima/memiliki lot Item Y sama sekali (stok = 0, jelas di bawah ambang berapa pun). Karena department X tidak muncul di `$owners` (tidak ada baris `item_locations` sama sekali), department itu **tidak pernah dicek dan tidak pernah diberi tahu**, padahal ini justru kasus paling kritis (stok 0).

**Rekomendasi:** iterasi dari `$item->minimumStocks` (atau `MinimumStock::where('is_active', true)->pluck('department_id')`) sebagai sumber daftar department yang harus dicek, bukan dari keberadaan baris `item_locations`.

---

## 3. [TINGGI] Tabel "Rekap per item" staff pakai sumber ambang minimum yang sudah usang

Ada dua tempat di aplikasi yang menampilkan badge "Di bawah minimum" untuk staff, dan keduanya **tidak konsisten**:

- **`ItemController::index()`** (`app/Http/Controllers/ItemController.php:101`) — benar, memakai `$row->minStockFor((int) $user->department_id)`, yaitu resolusi two-tier lewat tabel `minimum_stocks` yang baru.
- **`ItemLocationController::summaryTable()`** (`app/Http/Controllers/ItemLocationController.php:130-168`, khususnya baris 158 & 164) — memakai `$row->min_stock` yang berasal dari **`ItemLocationRepository::getDemanderStockSummary()`** (`app/Repositories/Eloquents/ItemLocationRepository.php:302-320`), yang secara eksplisit men-select `items.min_stock` (kolom global lama di tabel `items`).

Migration `database/migrations/2026_09_01_035414_add_minimum_stock.php` (baris 19-21) dan komentar `Item::minStockFor()` sama-sama menyatakan tabel `minimum_stocks` **menimpa/menggantikan** `items.min_stock` — artinya kolom lama itu sudah tidak lagi jadi sumber kebenaran di sistem baru. Tapi `getDemanderStockSummary()` belum diikutkan migrasi ke sistem baru ini.

**Dampak:** dua halaman ("Item Master" vs "Rekap per item" di menu Stok Gudang) yang seharusnya menampilkan info sama, bisa menunjukkan status "di bawah minimum" yang berbeda untuk item yang sama — satu memakai ambang per-department yang baru, satu memakai kolom global lama (yang mungkin sudah kosong/tidak dirawat sejak fitur baru diluncurkan).

**Rekomendasi:** ubah `getDemanderStockSummary()` untuk mengambil ambang dari `minimum_stocks` (via `Item::minStockFor()` atau join ke tabel `minimum_stocks`), bukan `items.min_stock`.

---

## 4. [TINGGI] Race condition pada `generateReceivingLot()` — bisa menghasilkan `receiving_lot` duplikat

**`app/Services/ItemLocationService.php:200-218`**
```php
public function generateReceivingLot($receivingDate)
{
    ...
    return DB::transaction(function () use ($prefix, $date) {
        $lastRecord = ItemLocation::withTrashed()
            ->where('receiving_lot', 'like', $prefix . $date . '%')
            ->orderBy('receiving_lot', 'desc')
            ->lockForUpdate()
            ->first();

        $newNumber = $lastRecord ? ... + 1 : 1;

        return $prefix . $date . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    });
}
```

Pola "kunci baris terakhir lalu +1" ini rapuh saat **belum ada baris yang cocok** (PORC pertama pada hari itu) — tidak ada row untuk dikunci, sehingga dua request PORC yang datang nyaris bersamaan bisa sama-sama membaca "belum ada data" dan menghasilkan nomor urut yang sama.

Yang membuat ini nyata berisiko: kolom `receiving_lot` pada tabel `item_locations` **tidak punya unique constraint** (hanya `->index()`, lihat `database/migrations/2026_07_23_055732_create_table_item_locations.php:25`). Jadi kalau race ini terjadi, insert kedua tidak akan gagal — dua lot berbeda akan tersimpan dengan `receiving_lot` yang identik, tanpa error apa pun.

Ini melanggar asumsi yang dipakai eksplisit di `TransactionService::findLotOfPorc()` (`app/Services/TransactionService.php:451-463`), yang komentarnya sendiri bilang *"receiving_lot unik per penerimaan, jadi pasti tepat"* lalu mengambil `->first()` dari `ItemLocation::where('receiving_lot', ...)`. Kalau invariant itu dilanggar, edit/hapus PORC bisa mengenai lot yang salah.

**Catatan pembanding:** `TransferRequest::generateTransferCode()` (`app/Models/TransferRequest.php:167-185`) dan `TransferRequestService::generateLetterNumber()` (`app/Services/TransferRequestService.php:623-640`) punya pola identik, tapi kolomnya (`transfer_code`, `letter_number`) **sudah** diberi `unique()` di migration — jadi race di sana paling buruk menghasilkan exception duplicate-key (error 500 yang berisik tapi tidak korup data), bukan data ganda yang diam-diam salah.

**Rekomendasi:** tambahkan unique constraint di kolom `receiving_lot` (setidaknya per `item_id + warehouse_id + receiving_lot` kalau memang boleh sama lintas gudang), atau pindah ke tabel counter terpisah yang selalu punya baris untuk dikunci (hindari kasus "tidak ada row untuk di-lock").

---

## 5. [RENDAH] Urutan pemrosesan level near-expiry berlawanan dengan komentarnya

**`app/Services/StockAlertService.php:98-137`**
```php
$levels = config('notification.expiry_alert_months', [3, 2, 1]);
sort($levels); // dari yang paling jauh, supaya level turun bertahap
```

`sort()` pada `[3, 2, 1]` menghasilkan urutan ASCENDING `[1, 2, 3]` — level 1 (paling mendesak/paling dekat) diproses **duluan**, bukan "dari yang paling jauh" seperti kata komentarnya (yang menyiratkan urutan descending `[3, 2, 1]`).

Dengan urutan ascending yang sebenarnya berjalan, dampaknya sebenarnya *masih masuk akal*: lot yang baru pertama kali dicek dan sudah berada dalam ketiga jendela (≤1, ≤2, ≤3 bulan) sekaligus hanya akan menerima **satu** notifikasi level paling mendesak (level 1), bukan tiga notifikasi sekaligus. Kalau urutannya benar-benar dibalik jadi descending sesuai komentar, lot semacam itu justru akan menerima 3 notifikasi berturut-turut dalam satu run (level 3, lalu 2, lalu 1) — kemungkinan besar bukan itu yang diinginkan.

Karena hasil akhirnya kemungkinan sudah "benar" secara kebetulan, ini bukan bug fungsional yang pasti, tapi **komentar dan kode saling bertentangan** — perlu dikonfirmasi ke yang menulis logic ini apakah `sort()` (ascending) memang disengaja, lalu perbaiki komentarnya; atau kalau yang dimaksud memang descending, ganti jadi `rsort()`.

---

## Fungsi Tidak Digunakan

Hasil scan seluruh pemanggilan method (`->nama(`) di `app/`, `resources/`, dan `routes/`. Dua method berikut dideklarasikan di interface, diimplementasikan penuh, tapi **tidak dipanggil dari mana pun** (bukan dari controller, service, view, maupun route):

- **`TransactionRepository::syncBalance()`** — `app/Repositories/Eloquents/TransactionRepository.php:44` (dideklarasikan juga di `app/Repositories/Interfaces/TransactionRepositoryInterface.php:17`).
- **`TransferRequestRepository::getDetails()`** — `app/Repositories/Eloquents/TransferRequestRepository.php:72` (dideklarasikan juga di `app/Repositories/Interfaces/TransferRequestRepositoryInterface.php:21`).

**Rekomendasi:** hapus keduanya (dari implementasi dan interface) kalau memang tidak ada rencana pemakaian, supaya tidak menyesatkan pembaca kode berikutnya.

> Catatan: model relations, accessor (`getXAttribute`), dan query scope (`scopeXxx`) yang awalnya juga muncul "tidak dipanggil" di hasil grep adalah **false positive** — Eloquent memanggilnya secara dinamis lewat magic property (`$row->item`, bukan `$row->item()`) atau lewat nama scope tanpa prefix (`->available()`, bukan `->scopeAvailable()`). Sudah diverifikasi satu per satu dan semuanya memang terpakai.

---

## 7. [INFO] Duplikasi logic stock card

**`app/Services/StockLedgerService.php`**

`getMonthlyStockCard()` (baris 24-72) dan `buildStockCard()` (baris 100-140, dipakai oleh `getStaffMonthlyStockCard()`) berisi loop penyusunan kartu stok harian yang hampir identik persis (baris demi baris sama), padahal helper `buildStockCard()` sudah ada dan seharusnya bisa dipakai ulang oleh `getMonthlyStockCard()` juga. Bukan bug — kedua fungsi tetap dipakai dan menghasilkan output benar — tapi ini peluang cleanup: refactor `getMonthlyStockCard()` supaya delegasi ke `buildStockCard()` seperti versi staff.

---

## Hal yang Sudah Diverifikasi Sebagai BUKAN Bug

Untuk transparansi, beberapa hal berikut sempat dicurigai tapi setelah ditelusuri ternyata by design / tidak berdampak:

- **`RelocationController` tidak punya method `edit`/`destroy`.** Ini konsisten dengan desain: relokasi adalah entri audit trail yang memang tidak boleh diubah/dihapus (sama seperti Disposal dan mayoritas Transaction selain PORC). Tidak ada view atau link manapun yang mengarah ke route `relocations.edit`/`relocations.destroy` yang hilang, jadi tidak ada broken link.
- **`TransferRequestService::guardReceiver()` mem-bypass pengecekan department untuk role admin** (`app/Services/TransferRequestService.php:880-891`) — pola yang sama juga dipakai di beberapa guard lain di file yang sama, tampaknya memang pola akses admin yang disengaja di seluruh modul ini.
