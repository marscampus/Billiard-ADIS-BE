<!DOCTYPE html>
<html>

<head>
    <title>Pricetag</title>
    <style>
        .pricetag {
            width: 200px;
            height: 100px;
            border: 1px solid #000;
        }

        .pricetag h5 {
            margin: 0;
            padding-left: 5px;
            background-color: #2BBBAD;
            color: white;
        }

        p.harga {
            margin: 0px;
            font-size: 30px;
            font-weight: bold;
            text-align: CENTER;
        }


        p.detail {
            margin: 0;
            padding-left: 5px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="d-flex flex-wrap-reverse">
        @foreach ($pricetags as $p)
            @php
                $juml = 0;
                $harga = $p->HJ;
                $formatter = new NumberFormatter('id_ID', NumberFormatter::CURRENCY);
                $hargaFormat = $formatter->formatCurrency($harga, 'IDR');
                $hargaFormat = str_replace('Rp', '', $hargaFormat);
                $hargaFormat = str_replace(',00', '', $hargaFormat);
                $juml = $juml + $p->jumlah;
            @endphp
            <table>
                <tr>
                    @for ($x = 1; $x <= $p->jumlah; $x++)
                        @if (fmod($x, 3) != 0)
                            <td>
                                <div class="pricetag">
                                    <h5>{{ substr($p->nama, 0, 25) }}</h5>
                                    <p class="detail">Rp.</p>
                                    <p class="harga">{{ $hargaFormat }}</p>
                                    <p class="detail">Update :
                                        {{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('LL') }}
                                    </p>
                                    <p class="detail">Barcode: {{ $p->barcode }}</p>
                                </div>
                            </td>
                        @else
                            <td>
                                <div class="pricetag">
                                    <h5>{{ substr($p->nama, 0, 25) }}</h5>
                                    <p class="detail">Rp.</p>
                                    <p class="harga">{{ $hargaFormat }}</p>
                                    <p class="detail">Update :
                                        {{ \Carbon\Carbon::parse($p->tanggal)->locale('id')->isoFormat('LL') }}
                                    </p>
                                    <p class="detail">Barcode: {{ $p->barcode }}</p>
                                </div>
                            </td>
                </tr>
                <tr>
        @endif
        @endfor
        </table>
        @endforeach
    </div>
</body>

</html>
