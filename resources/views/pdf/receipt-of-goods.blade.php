<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: letter portrait;
            /* Margin 0 supaya posisi blok dihitung dari tepi fisik
               kertas — batas potong harus tepat di tengah lembar. */
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Setengah lembar = 396pt (letter 792pt / 2 = 13,97cm).
           Sengaja pakai pt, bukan cm — 14cm x 2 melebihi tinggi
           letter dan membuat blok kedua pindah halaman. */
        .half {
            /* 380pt x 2 = 760pt, menyisakan 32pt sebagai cadangan.
               DomPDF mengabaikan box-sizing: border-box, jadi padding
               dan border ditambahkan DI LUAR tinggi ini — karena itu
               padding dipindah ke .ttb-box. */
            height: 380pt;
            width: 100%;
            overflow: hidden;
            page-break-inside: avoid;
        }

        /* Garis potong dibuat dengan div terpisah, bukan border pada
           .half, supaya tidak menambah tinggi blok. */
        .cut-line {
            border-top: 1px dashed #bbb;
            height: 0;
            font-size: 0;
            line-height: 0;
        }



        table.grid-wrapper {
            width: 100%;
            border-collapse: collapse;
            /* Pengganti padding .half yang dibuang */
            margin: 0.25cm;
        }

        table.grid-wrapper td.grid-cell {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .ttb-box {
            /* Lebar terpakai 20,99cm / 2 = 10,49cm per kolom.
               9,5cm memberi ruang aman untuk border & pembulatan DomPDF. */
            width: 9.5cm;
            height: 12.8cm;
            box-sizing: border-box;
            border: 1px solid #000;
            padding: 0.2cm;
            overflow: hidden;
        }

        .title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
            text-align: center;
        }

        .no-line {
            font-size: 8px;
            text-align: center;
        }

        .no-line .no-value {
            font-weight: bold;
            font-size: 8.5px;
        }

        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.header-table td {
            vertical-align: top;
            padding: 0;
        }

        .code-box {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            font-size: 6px;
        }

        .tgl-line {
            font-size: 7.5px;
            margin: 4px 0;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.items-table th,
        table.items-table td {
            border: 1px solid #000;
            padding: 0.5px 1px;
            font-size: 6.5px;
            text-align: center;
            line-height: 1.15;
        }

        table.items-table td {
            height: 9px;
        }

        table.items-table td.text-left {
            text-align: left;
        }

        table.items-table td {
            height: 10px;
        }


        table.footer-info {
            width: 100%;
            margin-top: 3px;
            font-size: 7px;
        }

        table.footer-info td {
            padding: 3px 0;
        }

        table.signature-table {
            width: 100%;
            margin-top: 8px;
            font-size: 6.5px;
            text-align: center;
        }

        table.signature-table td {
            width: 33.33%;
            vertical-align: bottom;
        }

        .sig-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 0 auto 3px auto;
        }

        .footnote {
            margin-top: 5px;
            font-size: 6px;
            font-style: italic;
        }
    </style>
</head>

<body>

    @php
        // 2 TTB per blok setengah lembar, 2 blok per lembar = 4 TTB/halaman
        $halves = $transferRequests->chunk(2);
        $pages = $halves->chunk(2);
    @endphp

    @foreach ($pages as $page)
        @foreach ($page as $halfIndex => $half)
            @if ($halfIndex === 1)
                <div class="cut-line"></div>
            @endif
            <div class="half">
                <table class="grid-wrapper">
                    <tr>
                        @foreach ($half as $transferRequest)
                            @php
                                $rog = $transferRequest->receiptOfGoods;
                                $details = $transferRequest->details;
                                $itemName = $transferRequest->item->item_desc ?? '-';
                                $totalRows = 12;
                            @endphp
                            <td class="grid-cell">
                                <div class="ttb-box">
                                    <table class="header-table">
                                        <tr>
                                            <td style="width: 68%;">
                                                <div class="title">TANDA TERIMA BARANG</div>
                                                <div class="no-line">
                                                    No. : <span class="no-value">
                                                        {{ $rog->letter_number ?? '.......' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td style="width: 32%;">
                                                <div class="code-box">FO-IMC-PUR-11-06/00</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <div class="tgl-line">
                                        TGL : {{ $rog?->letter_date?->format('d-m-Y') ?? '...........' }}
                                    </div>

                                    <table class="items-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 6%;">NO</th>
                                                <th style="width: 30%;">NAMA BARANG</th>
                                                <th style="width: 17%;">NO.LOT</th>
                                                <th style="width: 12%;">PKG</th>
                                                <th style="width: 16%;">SISA STOCK</th>
                                                <th style="width: 19%;">KET</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($details as $i => $detail)
                                                @if ($i < $totalRows)
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td class="text-left">
                                                            {{ \Illuminate\Support\Str::limit($itemName, 22) }}
                                                        </td>
                                                        <td>{{ $detail->vendor_lot ?? ($detail->receiving_lot ?? '-') }}
                                                        </td>
                                                        <td>{{ (int) $detail->package_taken }}
                                                            {{ $detail->package ?? '-' }}
                                                        </td>
                                                        {{-- Sisa lot asal di gudang IMC setelah barang diambil --}}
                                                        <td>
                                                            {{ number_format((float) $detail->remaining_weight, 1, ',', '.') }}
                                                            kg
                                                        </td>
                                                        <td class="text-left">
                                                            @
                                                            {{ number_format((float) $detail->qty_perpackage, 2, ',', '.') }}
                                                            kg
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach

                                            @for ($i = min($details->count(), $totalRows); $i < $totalRows; $i++)
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            @endfor

                                            <tr>
                                                <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL
                                                </td>
                                                <td style="font-weight: bold;">
                                                    {{ (int) $details->sum('package_taken') }}</td>
                                                <td></td>
                                                <td class="text-left" style="font-weight: bold;">
                                                    {{ number_format($details->sum('qty_taken'), 2, ',', '.') }} kg
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <table class="footer-info">
                                        <tr>
                                            <td style="text-align: left;">Yang menerima</td>
                                            <td style="text-align: right;">Yang menyerahkan</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left;">
                                                Sub Dept. : {{ $transferRequest->department->code ?? '-' }}
                                            </td>
                                            <td style="text-align: right;">
                                                {{ $rog->responsibility->name ?? '-' }}
                                            </td>
                                        </tr>
                                    </table>

                                    <table class="signature-table">
                                        <tr>
                                            <td>
                                                <div class="sig-line"></div>
                                                *Penerima
                                            </td>
                                            <td>
                                                <div class="sig-line"></div>
                                                *Pengawas
                                            </td>
                                            <td>
                                                <div class="sig-line"></div>
                                                *Petugas
                                            </td>
                                        </tr>
                                    </table>

                                    <div class="footnote">
                                        *Harap tulis nama jelas
                                    </div>
                                </div>
                            </td>
                        @endforeach

                        @if ($half->count() < 2)
                            <td class="grid-cell"></td>
                        @endif
                    </tr>
                </table>
            </div>
        @endforeach

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>
