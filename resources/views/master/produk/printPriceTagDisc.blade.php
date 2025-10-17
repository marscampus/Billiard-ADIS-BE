<!DOCTYPE html>
<html>

<head>
    <title>Pricetag</title>
    <style>
        .pricetag {
            width: 200px;
            height: 100px;
            border: 1px solid #000;
            background-color: yellow
        }

        .pricetag h5 {
            font-size: 1vw;
            margin: 0;
            padding-left: 5px;
            background-color: red;
            color: yellow;
        }

        p.harga {
            margin: 0px;
            font-size: 30px;
            font-weight: bold;
            text-align: CENTER;
        }


        p.detail {
            font-size: 1vw;
            margin: 0;
            padding-left: 5px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="d-flex flex-wrap-reverse">
        @foreach ($discPriceTag as $index => $d)
            @php
                $juml = 1;
                $harga = $d['H_DISKON'];
                $formatter = new NumberFormatter('id_ID', NumberFormatter::CURRENCY);
                $hargaFormat = $formatter->formatCurrency($harga, 'IDR');
                $hargaFormat = str_replace('Rp', '', $hargaFormat);
                $hargaFormat = str_replace(',00', '', $hargaFormat);
            @endphp
            <table>
                <tr>
                    @for ($x = 1; $x <= $juml; $x++)
                        @if (fmod($x, 3) != 0)
                            <td>
                                <div class="pricetag">
                                    <h5>{{ substr($namaProduk[$index], 0, 25) }}</h5>
                                    <p class="detail">Rp. (PROMO)</p>
                                    <p class="harga">{{ $hargaFormat }}</p>
                                    <p class="detail">Update :
                                        {{ \Carbon\Carbon::parse($d['TGL_BERAKHIR'])->locale('id')->isoFormat('LL') }}
                                    </p>
                                    <p class="detail">Barcode: {{ $d['BARCODE'] }}</p>
                                </div>
                            </td>
                        @else
                            <td>
                                <div class="pricetag">
                                    <h5>{{ substr($namaProduk[$index], 0, 25) }}</h5>
                                    <p class="detail">Rp. (PROMO)</p>
                                    <p class="harga">{{ $hargaFormat }}</p>
                                    <p class="detail">Update :
                                        {{ \Carbon\Carbon::parse($d['TGL_BERAKHIR'])->locale('id')->isoFormat('LL') }}
                                    </p>
                                    <p class="detail">Barcode: {{ $d['BARCODE'] }}</p>
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
