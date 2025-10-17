@php
    use App\Helpers\Func;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota Kecil</title>

    <style>
        * {
            font-family: "consolas", sans-serif;
        }

        p {
            display: block;
            margin: 3px;
            font-size: 10pt;
        }

        table td {
            font-size: 9pt;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        @media print {
            @page {
                margin: 0;
                size: 75mm;
            }

            html,
            body {
                width: 70mm;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <button class="btn-print" style="position: absolute; right: 1rem; top: 1rem;" onclick="window.print()">Print</button>
    <div class="text-center">
        <h3>TESTER</h3>
        <p>By Andromeda</p>
    </div>
    <br>
    <div>
        <p style="float: left;">{{ date('d-m-Y') }}</p>
        <p style="float: right">ARADHEA</p>
    </div>
    <div class="clear-both" style="clear: both;"></div>
    <p>No. Nota: {{ $faktur }}</p>
    <p class="text-center">===================================</p>

    <br>
    <table width="100%" style="border: 0;">
        @foreach ($kasir as $item)
            <tr>
                <td colspan="3">{{ $item->stock->NAMA }}</td>
            </tr>
            <tr>
                <td>{{ Func::getZFormat($item->QTY) }} x {{ Func::getZFormat($item->HARGA) }}</td>
                <td></td>
                <td class="text-right">{{ Func::getZFormat($item->QTY * $item->HARGA) }}</td>
            </tr>
        @endforeach
    </table>
    <p class="text-center">-----------------------------------</p>

    <table width="100%" style="border: 0;">
        <tr>
            <td>Total Harga:</td>
            <td class="text-right">{{ Func::getZFormat($totkasir->SUBTOTAL) }}</td>
        </tr>
        <tr>
            <td>Diskon:</td>
            <td class="text-right">{{ Func::getZFormat($totkasir->DISCOUNT) }}</td>
        </tr>
        <tr>
            <td>Total Bayar:</td>
            <td class="text-right">{{ Func::getZFormat($totkasir->TOTAL) }}</td>
        </tr>
        <tr>
            <td>Diterima:</td>
            <td class="text-right">{{ Func::getZFormat($totkasir->TUNAI) }}</td>
        </tr>
    </table>

    <p class="text-center">===================================</p>
    <p class="text-center">-- TERIMA KASIH --</p>
</body>

</html>
