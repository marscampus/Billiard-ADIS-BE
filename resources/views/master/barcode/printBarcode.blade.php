<!DOCTYPE html>
<html>

<head>
    <title>Pricetag</title>
    <style>
        .pricetag {
            padding: 5px;
            width: 150px;
            height: 60px;
            border: 1px solid #000;
            text-align: center;
        }

        p.nama {
            margin: 0;
            font-size: 10px;
        }

        p.harga {
            margin: 0px;
            font-size: 30px;
            font-weight: bold;
            text-align: CENTER;
        }


        p.detail {
            font-weight: bold;
            margin: 0;
            font-size: 12px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="d-flex flex-wrap-reverse">
        @foreach ($barcodePrint as $index => $b)
            @php
                $juml = 0;
                $harga = $hargaProduk[$index];
                $formatter = new NumberFormatter('id_ID', NumberFormatter::CURRENCY);
                $hargaFormat = $formatter->formatCurrency($harga, 'IDR');
                $hargaFormat = str_replace('Rp', '', $hargaFormat);
                $hargaFormat = str_replace(',00', '', $hargaFormat);
            @endphp
            <table>
                <tr>
                    {{-- @for ($x = 1; $x <= $b->jumlah; $x++) --}}
                    <td>
                        <div class="pricetag">
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($b['KODE_TOKO'], 'C39') }}"
                                height="30" width="95" /><br />
                            <p class="nama">{{ substr($b['NAMA'], 0, 11) }}</p>
                            <p class="detail">{{ $hargaFormat }}</p>
                        </div>
                    </td>
                    <td>
                        <div class="pricetag">
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($b['KODE_TOKO'], 'C39') }}"
                                height="30" width="95" /><br />
                            <p class="nama">{{ substr($b['NAMA'], 0, 11) }}</p>
                            <p class="detail">{{ $hargaFormat }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    {{-- @endfor --}}
            </table>
        @endforeach
    </div>
</body>

</html>
