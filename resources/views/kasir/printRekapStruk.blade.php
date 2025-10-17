@php
    use App\Helpers\Func;
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>Membuat Laporan PDF Dengan DOMPDF Laravel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body>
    <style type="text/css">
        table tr td,
        table tr th {
            font-size: 9pt;
        }

        th {
            text-align: center;
        }
    </style>

    <div class="container">
        <center>
            <h4>LAPORAN KASIR</h4>
        </center>
        <br />
        <table>
            <thead>
                <tr>
                    <td>FAKTUR</td>
                    <td>:</td>
                    <td>{{ $faktur }}</td>

                </tr>
                <tr>
                    <td>TANGGAL</td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::now() }}</td>
                </tr>
                <tr>
                    <td>USER KASIR</td>
                    <td>:</td>
                    <td>{{ $result['USERNAME'] }}</td>
                </tr>
            </thead>
        </table>

        <center>
            <h4>RINCIAN UANG</h4>
        </center>
        <table class="table table-bordered">
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Kas Awal</td>
                <td>{{ Func::getZFormatWithDecimal(150000, 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Total Penjualan</td>
                <td>{{ Func::getZFormatWithDecimal($result['TOTALPENJUALAN'], 0) }}</td>
            </tr>
            <tr>
                <td>- Uang Tunai</td>
                <td>{{ Func::getZFormatWithDecimal($result['TUNAI'], 0) }}</td>
            </tr>
            <tr>
                <td>- Voucher</td>
                <td>{{ Func::getZFormatWithDecimal($result['VOUCHER'], 0) }}</td>
            </tr>
            <tr>
                <td>- Debet Bank</td>
                <td>{{ Func::getZFormatWithDecimal($result['BAYARKARTU'], 0) }}</td>
            </tr>
            <tr>
                <td>Total Pembatalan</td>
                <td>{{ Func::getZFormatWithDecimal($result['TOTALPEMBATALAN'], 0) }}</td>
            </tr>
            <tr>
                <td>Total Point</td>
                <td>{{ Func::getZFormatWithDecimal(0, 0) }}</td>
            </tr>
            <tr>
                <td>Total Infaq</td>
                <td>{{ Func::getZFormatWithDecimal($result['INFAQ'], 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Total Uang</td>
                <td>{{ Func::getZFormatWithDecimal($result['TOTALUANG'], 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Total Disetor</td>
                <td>{{ Func::getZFormatWithDecimal($result['TOTALUANG'], 0) }}</td>
            </tr>
            {{-- @endforeach --}}
        </table>
    </div>

</body>

</html>
