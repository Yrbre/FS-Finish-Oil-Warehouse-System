<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: a4;
            margin: 0.6cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #000;
        }

        table.grid-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid-wrapper td.grid-cell {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .ttb-box {
            width: 9cm;
            height: 10cm;
            box-sizing: border-box;
            border: 1px dashed #999;
            padding: 0.3cm;
            margin: 0.1cm;
            overflow: hidden;
        }

        .title {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
            text-align: center;
        }

        .no-line {
            font-size: 9px;
            text-align: center;
        }

        .no-line .no-value {
            font-weight: bold;
            font-size: 9.5px;
        }

        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.header-table td {
            vertical-align: top;
            padding: 0;
        }

        .code-box {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
            font-size: 7px;
        }

        .unit-box {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
            font-size: 8px;
            margin-top: 3px;
        }

        .tgl-line {
            font-size: 8px;
            margin: 6px 0;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.items-table th,
        table.items-table td {
            border: 1px solid #000;
            padding: 0.3px 1px;
            font-size: 8px;
            text-align: center;
            line-height: 1.2;
        }

        table.items-table th {
            font-weight: bold;
        }

        table.items-table td.text-left {
            text-align: left;
        }

        table.items-table td {
            height: 10px;
        }

        table.footer-info {
            width: 100%;
            margin-top: 2px;
            font-size: 7.5px;
        }

        table.footer-info td {
            padding: 3px 0;
        }

        table.signature-table {
            width: 100%;
            margin-top: 10px;
            font-size: 7px;
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
            margin-top: 8px;
            font-size: 6.5px;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    @php
        $rows = $transferRequests->chunk(2); // 2 TTB per baris
        $rowGroups = $rows->chunk(2); // 2 baris (4 TTB) per halaman A4
    @endphp

    @foreach ($rowGroups as $pageGroup)
        <table class="grid-wrapper">
            @foreach ($pageGroup as $row)
                <tr>
                    @foreach ($row as $transferRequest)
                        <td class="grid-cell">
                            <div class="ttb-box">
                                <table class="header-table">
                                    <tr>
                                        <td style="width: 68%;">
                                            <div class="title">TANDA TERIMA BARANG</div>
                                            <div class="no-line">
                                                No. : <span
                                                    class="no-value">{{ $transferRequest->letter_number ?? '.......' }}</span>
                                            </div>
                                        </td>
                                        <td style="width: 32%;">
                                            <div class="code-box">FO-IMC-PUR-11-06/00</div>

                                    </tr>

                                </table>

                                <div class="tgl-line">
                                    TGL : ...........
                                </div>
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 6%;">NO</th>
                                            <th style="width: 27%;">NAMA BARANG</th>
                                            <th style="width: 15%;">NO.LOT</th>
                                            <th style="width: 15%;">JUMLAH</th>
                                            <th style="width: 15%;">SISA</th>
                                            <th style="width: 22%;">KET</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalRows = 15; @endphp

                                        @foreach ($transferRequest->details as $i => $detail)
                                            @if ($i < $totalRows)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-left">
                                                        {{ $detail->itemLocation->item->item_desc ?? '-' }}</td>
                                                    <td>{{ $detail->vendor_lot ?? '-' }}</td>
                                                    <td>{{ number_format((float) $detail->qty_taken, 1, ',', '.') }}
                                                    </td>
                                                    <td>{{ $detail->itemLocation->qty_available ?? '-' }}</td>
                                                    <td class="text-left"></td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @for ($i = min($transferRequest->details->count(), $totalRows); $i < $totalRows; $i++)
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>

                                <table class="footer-info">
                                    <tr>
                                        <td style="text-align: left;">Yang menerima</td>
                                        <td style="text-align: right;">Yang menyerahkan</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left;">Sub Dept. :</td>
                                        <td></td>
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

                                <div class="footnote">*Harap tulis nama jelas</div>
                            </div>
                        </td>
                    @endforeach

                    @if ($row->count() < 2)
                        <td class="grid-cell"></td>
                    @endif
                </tr>
            @endforeach
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
