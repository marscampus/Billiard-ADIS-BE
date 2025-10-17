@php
    use App\Helpers\Func;
@endphp


<!DOCTYPE html>
<html>

<head>
    <title>Membuat Laporan PDF Dengan DOMPDF Laravel</title>
    {{-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>

<body>
    <div class="container text-center ">
        <div class="row justify-content-between">
            <div class="col-4 text-left">
                Powered By
            </div>
            <div class="col-4 text-right">
                {{ $totpembelian->supplier->NAMA }}
            </div>
        </div>
        <div class="row justify-content-between">
            <div class="col-4 text-left">
                ANDROMEDA
            </div>
            <div class="col-4 text-right">
                {{ $totpembelian->supplier->ALAMAT }}
            </div>
        </div>
        <div class="text-center">
            <h5>BUKTI TERIMA BARANG</h5>
            <h5>No PO : {{ $totpembelian->PO }} | No Bukti : {{ $totpembelian->FAKTUR }}</h5>
        </div>
        <div class="container">
            <div class="row align-items-start">
                <div class="col text-left">
                    Tanggal : {{ Func::formatDate($totpembelian->TGL) }}
                </div>
                <div class="col">
                    Jatuh Tempo : {{ Func::formatDate($totpembelian->JTHTMP) }}
                </div>
                <div class="col text-right">
                    Gudang : [{{ $totpembelian->GUDANG }}] {{ $totpembelian->gudang->KETERANGAN }}
                </div>
            </div>
        </div>

        <table class='table table-bordered table-sm'>
            <thead class="text-center">
                <tr>
                    <th>NO</th>
                    <th>BARCODE</th>
                    <th>NAMA BARANG</th>
                    <th>HARGA</th>
                    <th>QUANTITY</th>
                    <th>DISC</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php $i=1 @endphp
                @foreach ($pembelian as $p)
                    <tr>
                        <td class="text-center">{{ $i++ }}</td>
                        <td>{{ $p->BARCODE }}</td>
                        <td>{{ $p->stock->NAMA }}</td>
                        <td class="text-end">{{ Func::getZFormatWithDecimal($p->HARGA, 0) }}</td>
                        <td class="text-end">{{ Func::getZFormatWithDecimal($p->QTY, 0) . ' ' . $p->SATUAN }}</td>
                        <td class="text-end">{{ Func::getZFormatWithDecimal($p->DISCOUNT, 0) }}</td>
                        <td class="text-end">{{ Func::getZFormatWithDecimal($p->JUMLAH, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="col md-6">
            <tr>
                <td>Keterangan</td>
                <td>:</td>
                <td>{{ $totpembelian->KETERANGAN }}</td>
            </tr>
            <tr>
                <td>Discount Rp</td>
                <td>:</td>
                <td>{{ $totpembelian->PEMBULATAN }}</td>
            </tr>
        </table>
        <table class="container text-end">
            <tr>
                <td>SUBTOTAL</td>
                <td>:</td>
                <td>{{ Func::getZFormatWithDecimal($totpembelian->SUBTOTAL, 0) }}</td>
            </tr>
            <tr>
                <td>PPN</td>
                <td>:</td>
                <td>{{ Func::getZFormatWithDecimal($totpembelian->PAJAK, 0) }}</td>
            </tr>
            <tr>
                <td>TOTAL</td>
                <td>:</td>
                <td>{{ Func::getZFormatWithDecimal($totpembelian->TOTAL, 0) }}</td>
            </tr>
        </table>
        <br>
        <p class="container text-end">Madiun, {{ date('d-m-Y') }}</p>
        <div class="container text-center">
            <div class="row">
                <div class="col">
                    <tr>
                        <td>Dibuat,</td>
                        <br><br><br><br><br>
                        <td>ANDROMEDA</td>
                    </tr>
                </div>
                <div class="col">
                    <td>Pembuat,</td>
                    <br><br><br><br><br>
                    <td>{{ $totpembelian->supplier->NAMA }}</td>
                </div>
            </div>

        </div>

</body>

</html>
