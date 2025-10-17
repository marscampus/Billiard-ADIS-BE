<!DOCTYPE html>
<html>

<head>
    <title>Membuat Laporan PDF Dengan DOMPDF Laravel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body>

    <div class="container">
        <center>
            <h4>Membuat Laporan PDF Dengan DOMPDF Laravel</h4>
            <h5><a target="_blank" href="https://www.malasngoding.com/membuat-laporan-…n-dompdf-laravel/">www.malasngoding.com</a></h5>
        </center>
        <br />
        <a href="/laporan/transaksi-stock/nilai-persediaan/cetak" class="btn btn-primary" target="_blank">CETAK PDF</a>
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Barcode</th>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Satuan</th>
                    <th>HargaBeli</th>
                    <th>HargaPokok</th>
                    <th>HargaJual</th>
                    <th>SaldoStock</th>
                    <th>NilaiStock</th>
                </tr>
            </thead>
            <tbody>
                @php $i=1 @endphp
                @foreach($persediaan as $item)
                <tr>
                    <td>{{ $item['Barcode'] }}</td>
                    <td>{{ $item['Kode'] }}</td>
                    <td>{{ $item['Nama'] }}</td>
                    <td>{{ $item['Satuan'] }}</td>
                    <td>{{ $item['HargaBeli'] }}</td>
                    <td>{{ $item['HargaPokok'] }}</td>
                    <td>{{ $item['HargaJual'] }}</td>
                    <td>{{ $item['SaldoStock'] }}</td>
                    <td>{{ $item['NilaiStock'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</body>

</html>