<?php

return [
    /*
     | Kirim notifikasi lewat email selain in-app.
     | Matikan bila SMTP belum dikonfigurasi — notifikasi in-app
     | tetap berjalan normal.
     */
    'mail_enabled' => env('NOTIFICATION_MAIL_ENABLED', false),

    /*
     | Berapa bulan sebelum kedaluwarsa peringatan dikirim.
     | Satu lot dapat memicu masing-masing tingkat sekali saja.
     */
    'expiry_alert_months' => [3, 2, 1],
];
