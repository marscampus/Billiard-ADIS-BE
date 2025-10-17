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
            <h4>DAFTAR MEMBER</h4>
            {{-- <h5><a target="_blank"
                    href="https://www.malasngoding.com/membuat-laporan-…n-dompdf-laravel/">www.malasngoding.com</a></h5> --}}
        </center>
        <br />
        {{-- <a href="/pegawai/cetak_pdf" class="btn btn-primary" target="_blank">CETAK PDF</a> --}}
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>KODE</th>
                    <th>NAMA</th>
                    <th>ALAMAT</th>
                    <th>TELEPON</th>
                </tr>
            </thead>
            <tbody>
                @php $i=1 @endphp
                @foreach ($members as $p)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $p->KODE }}</td>
                        <td>{{ $p->NAMA }}</td>
                        <td>{{ $p->ALAMAT }}</td>
                        <td>{{ $p->TELEPON }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</body>

</html>
