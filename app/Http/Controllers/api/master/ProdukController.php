<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\ApiResponse;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\fun\KartuStock;
use App\Models\fun\StockHP;
use App\Models\master\DiskonStock;
use App\Models\master\GolonganStock;
use App\Models\master\Gudang;
use App\Models\master\Stock;
use App\Models\master\StockAwal;
use App\Models\master\StockKode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    // UNTUK MENDAPATKAN GENERATE KODE
    public function getKode(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        try {
            $response = GetterSetter::getLastKodeRegister($KODE, $LEN);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $response,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function data(Request $request)
    {
        try {
            $vaData = DB::table('stock as st')
                ->select(
                    'st.KODE',
                    'st.KODE_TOKO',
                    'gs.KETERANGAN as GOLONGAN',
                    'st.NAMA',
                    'sa.KETERANGAN as SATUAN',
                    'gu.KETERANGAN as GUDANG',
                    'st.STATUS_HAPUS',
                    'st.HB',
                    'st.HJ',
                    'st.FOTO'
                )
                ->leftJoin('golonganstock as gs', 'gs.KODE', '=', 'st.GOLONGAN')
                ->leftJoin('satuanstock as sa', 'sa.KODE', '=', 'st.SATUAN')
                ->leftJoin('gudang as gu', 'gu.KODE', '=', 'st.GUDANG')
                ->orderBy('ID', 'DESC')
                ->get();
            // JIKA REQUEST SUKSES
            if ($vaData) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $vaData,
                    'total_data' => count($vaData),
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }


    function dataFilter(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'] ?? 'Unknown User';
        unset($vaRequestData['auth']);

        try {
            $nLimit = 10;
            $vaData = DB::table('stock as st')
                ->select(
                    'st.Status_Stock',
                    'st.Kode',
                    'st.BKP',
                    'st.Kode_Toko',
                    'st.Nama',
                    'st.Satuan',
                    'st.HB',
                    'st.HJ',
                    'st.Expired',
                    'st.Discount',
                    'st.Satuan',
                    'gs.Keterangan as Golongan',
                    'st.Foto',
                    // 'sa.Keterangan as Satuan',
                    'gu.Keterangan as Gudang',
                    'st.Status_Hapus',
                    DB::raw('IFNULL(SUM(ks.Debet - ks.Kredit),0) as SisaStock')
                )
                ->leftJoin('golonganstock as gs', 'gs.KODE', '=', 'st.GOLONGAN')
                ->leftJoin('satuanstock as sa', 'sa.KODE', '=', 'st.SATUAN')
                ->leftJoin('gudang as gu', 'gu.KODE', '=', 'st.GUDANG')
                ->leftJoin('kartustock as ks', 'ks.KODE', '=', 'st.KODE')
                ->where('st.Status_Hapus', '=', '0');

            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, 'LIKE', '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->orderBy('st.ID', 'DESC')->groupBy('st.Kode')->get();
            $vaArray = []; // Initialize the array for result data
            foreach ($vaData as $d) {
                $cBarcode = $d->Kode_Toko;
                $cStatusHapus = $d->Status_Hapus;
                $nSisaStock = $d->SisaStock;
                $cStatusStock = $d->Status_Stock;

                if ($cStatusStock == '1') {
                    $nSisaStock = 'Unlimited';
                } elseif ($nSisaStock <= 0) {
                    $nSisaStock = 'Habis';
                }

                if ($cStatusHapus == '0') {
                    $cKode = $d->Kode;

                    // Mengambil TANGGAL PERUBAHAN 
                    $dTglPerubahan = DB::table('perubahanhargastock')
                        ->where('Kode', '=', $cKode)
                        ->orderByDesc('DateTime')
                        ->value('Tanggal_Perubahan');

                    // Mengecek DISKON PERIODE
                    $vaDiscPeriode = DB::table('diskon_periode')
                        ->select('HJ_Awal', 'Tgl_Akhir', 'HJ_Diskon', 'Kuota_Qty')
                        ->where('Barcode', $cBarcode) // Gunakan '=' untuk pencarian tepat
                        ->orderByDesc('Tgl_Akhir')
                        ->first();

                    $cDiscPeriodeStatus = "TIDAK ADA";
                    $nHJ = $d->HJ;

                    if ($vaDiscPeriode) {
                        $dTglSekarang = Carbon::now()->format('Y-m-d');
                        $dTglAkhir = $vaDiscPeriode->Tgl_Akhir;
                        $nHJDiskon = $vaDiscPeriode->HJ_Diskon;
                        $nKuota = $vaDiscPeriode->Kuota_Qty;

                        if ($dTglAkhir >= $dTglSekarang) {
                            // Proses mencari total penjualan untuk diskon ini
                            $vaFakturPenjualan = DB::table('penjualan')
                                ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                                ->where('Harga', '=', $nHJDiskon)
                                ->pluck('Faktur');

                            $cFakturDgnKodeSesi = $vaFakturPenjualan->filter(function ($cFaktur) {
                                $vaTotPenjualan = DB::table('totpenjualan')
                                    ->select('KodeSesi')
                                    ->where('Faktur', '=', $cFaktur)
                                    ->first();
                                return $vaTotPenjualan && !empty($vaTotPenjualan->KodeSesi);
                            })->toArray();

                            $nTotalPenjualan = DB::table('penjualan')
                                ->whereIn('Faktur', $cFakturDgnKodeSesi)
                                ->where('Harga', '=', $nHJDiskon)
                                ->sum('QTY');
                            $nTotalPenjualan = $nTotalPenjualan ?? 0;
                            $nSisaKuotaPeriode = abs(intval($nTotalPenjualan) - intval($nKuota));

                            if ($nTotalPenjualan < $nKuota) {
                                $nHJ = $nHJDiskon;
                                $cDiscPeriodeStatus = 'ADA';
                                // Perhitungan diskon
                                $nDisc = round((($vaDiscPeriode->HJ_Awal - $vaDiscPeriode->HJ_Diskon) / $vaDiscPeriode->HJ_Awal) * 100);
                            }
                        }
                    }

                    $vaArray[] = [
                        'KODE' => $cKode,
                        'BKP' => $d->BKP,
                        'KODE_TOKO' => $d->Kode_Toko,
                        'NAMA' => $d->Nama,
                        'GOLONGAN' => $d->Golongan,
                        'GUDANG' => $d->Gudang,
                        'SATUAN' => $d->Satuan,
                        'HARGABELI' => $d->HB,
                        'HJ' => $nHJ,
                        'TGLEXP' => $d->Expired,
                        'DISCOUNT' => $nDisc ?? 0,
                        'PAJAK' => $d->Pajak ?? 0,
                        'TERIMA' => 1, // Assuming TERIMA always 1 for each item
                        'TGLPERUBAHANHJ' => $dTglPerubahan,
                        'DISKONPERIODE' => $cDiscPeriodeStatus,
                        'KUOTADISKONTERJUAL' => $nTotalPenjualan ?? 0,
                        'SISAKUOTADISKON' => $nSisaKuotaPeriode ?? 0,
                        'SISASTOCKBARANG' => $nSisaStock ?? 0,
                        'FOTO' => $d->Foto
                    ];
                }
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function getBarcodeKasir(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cBarcode = $vaRequestData['Barcode'];
            $nJml = 1;
            if (strpos($cBarcode, '*') !== false) {
                $nJml = substr($cBarcode, 0, strpos($cBarcode, '*'));
                $cBarcode = substr($cBarcode, strpos($cBarcode, '*') + 1);
            }

            $nCount = Stock::where('Kode_Toko', 'LIKE', '%' . $cBarcode . '%')
                ->where('Kode_Toko', '<>', 'Kode')
                ->count();
            if ($nCount >= 1) {
                $vaData = DB::table('stock as s')
                    ->select(
                        's.Status_Stock',
                        's.Kode',
                        's.BKP',
                        's.Kode_Toko',
                        's.Nama',
                        's.Satuan',
                        's.HB',
                        's.HJ',
                        's.Expired',
                        's.Discount',
                        's.Pajak',
                        's.Status_Hapus',
                        DB::raw('IFNULL(SUM(ks.Debet - ks.Kredit),0) as SisaStock')
                    )
                    ->leftJoin('kartustock as ks', 'ks.Kode', '=', 's.Kode')
                    ->where('s.Kode_Toko', 'LIKE', '%' . $cBarcode . '%')
                    ->groupBy('s.Kode')
                    ->get();
                foreach ($vaData as $d) {
                    $cStatusHapus = $d->Status_Hapus;
                    $nSisaStock = $d->SisaStock;
                    $cStatusStock = $d->Status_Stock;
                    if ($cStatusStock == '1') {
                        $nSisaStock = 'Unlimited';
                    }
                    if ($nSisaStock <= 0 && $cStatusStock != '1') {
                        return response()->json(['status' => 'STOCK BARANG SUDAH HABIS']);
                    }
                    if ($cStatusHapus == 0) {
                        $cKode = $d->Kode;
                        // Mengambil TANGGAL PERUBAHAN 
                        $dTglPerubahan = DB::table('perubahanhargastock')
                            ->where('Kode', '=', $cKode)
                            ->orderByDesc('DateTime')
                            ->value('Tanggal_Perubahan');

                        // Mengecek DISKON PERIODE
                        $vaDiscPeriode = DB::table('diskon_periode')
                            ->select(
                                'HJ_Awal',
                                'Tgl_Akhir',
                                'HJ_Diskon',
                                'Kuota_Qty'
                            )
                            ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                            ->orderByDesc('Tgl_Akhir')
                            ->first();
                        $cDiscPeriodeStatus = "TIDAK ADA";
                        $nHJ = $d->HJ;
                        if ($vaDiscPeriode) {
                            $dTglSekarang = Carbon::now()->format('Y-m-d');
                            $dTglAkhir = $vaDiscPeriode->Tgl_Akhir;
                            $nHJDiskon = $vaDiscPeriode->HJ_Diskon;
                            $nKuota = $vaDiscPeriode->Kuota_Qty;
                            if ($dTglAkhir >= $dTglSekarang) {
                                $vaFakturPenjualan = DB::table('penjualan')
                                    ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                                    ->where('Harga', '=', $nHJDiskon)
                                    ->pluck('Faktur');

                                // Menyarirng FAKTUR yang memiliki KODESESI !== '' pada TOTPENJUALAN
                                $cFakturDgnKodeSesi = $vaFakturPenjualan->filter(function ($cFaktur) {
                                    $vaTotPenjualan = DB::table('totpenjualan')
                                        ->select(
                                            'KodeSesi'
                                        )
                                        ->where('Faktur', '=', $cFaktur)
                                        ->first();
                                    return $vaTotPenjualan && !empty($vaTotPenjualan->KodeSesi);
                                });
                                // Mengubah collection mejadi array
                                $vaFakturDgnKodeSesi = $cFakturDgnKodeSesi->toArray();

                                // Menghitung jumlah item yang telah dijual dengan HARGA DISKON berdasarkan FAKTUR yang telah difilter
                                $nTotalPenjualan = DB::table('penjualan')
                                    ->whereIn('Faktur', $vaFakturDgnKodeSesi)
                                    ->where('Harga', '=', $nHJDiskon)
                                    ->sum('QTY');
                                $nTotalPenjualan = $nTotalPenjualan ?? 0;
                                $nSisaKuotaPeriode = abs(intval($nTotalPenjualan) - intval($nKuota));

                                // Kondisi menghitung jika belum mencapai KUOTA 
                                if ($nTotalPenjualan < $nKuota) {
                                    $nHJ = $nHJDiskon;
                                    $cDiscPeriodeStatus = 'ADA';
                                }
                            }
                        }
                        $vaArray[] = [
                            'KODE' => $cKode,
                            'BKP' => $d->BKP,
                            'BARCODE' => $d->Kode_Toko,
                            'NAMA' => $d->Nama,
                            'SATUAN' => $d->Satuan,
                            'HARGABELI' => $d->HB,
                            'HJ' => $nHJ,
                            'TGLEXP' => $d->Expired,
                            'DISCOUNT' => empty($d->Discount) ? 0 : $d->Discount,
                            'PAJAK' => empty($d->Pajak) ? 0 : $d->Pajak,
                            'TERIMA' => $nJml,
                            'TGLPERUBAHANHJ' => $dTglPerubahan,
                            'DISKONPERIODE' => $cDiscPeriodeStatus,
                            'KUOTADISKONTERJUAL' => $nTotalPenjualan ?? 0,
                            'SISAKUOTADISKON' => $nSisaKuotaPeriode ?? 0,
                            'SISASTOCKBARANG' => $nSisaStock ?? 0
                        ];
                    } else {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => 'Barang Tidak Ditemukan',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }
            }
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaArray,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function getBarcode(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cBarcode = $vaRequestData['Barcode'];
            $nJml = 1;
            if (strpos($cBarcode, '*') !== false) {
                $nJml = substr($cBarcode, 0, strpos($cBarcode, '*'));
                $cBarcode = substr($cBarcode, strpos($cBarcode, '*') + 1);
            }

            $nCount = Stock::where('Kode_Toko', 'LIKE', '%' . $cBarcode . '%')
                ->where('Kode_Toko', '<>', 'Kode')
                ->count();
            if ($nCount >= 1) {
                $vaData = DB::table('stock as s')
                    ->select(
                        's.Kode',
                        's.BKP',
                        's.Kode_Toko',
                        's.Nama',
                        's.Satuan',
                        's.HB',
                        's.HJ',
                        's.Expired',
                        's.Discount',
                        's.Pajak',
                        's.Status_Hapus',
                        DB::raw('IFNULL(SUM(ks.Debet - ks.Kredit),0) as SisaStock')
                    )
                    ->leftJoin('kartustock as ks', 'ks.Kode', '=', 's.Kode')
                    ->where('s.Kode_Toko', 'LIKE', '%' . $cBarcode . '%')
                    ->groupBy('s.Kode')
                    ->first();
                if ($vaData) {
                    $cStatusHapus = $vaData->Status_Hapus;
                    $nSisaStock = $vaData->SisaStock;
                    $vaArray = [];
                    if ($cStatusHapus == 0) {
                        $cKode = $vaData->Kode;
                        // Mengambil TANGGAL PERUBAHAN 
                        $dTglPerubahan = DB::table('perubahanhargastock')
                            ->where(
                                'Kode',
                                '=',
                                $cKode
                            )
                            ->orderByDesc('DateTime')
                            ->value('Tanggal_Perubahan');

                        // Mengecek DISKON PERIODE
                        $vaDiscPeriode = DB::table('diskon_periode')
                            ->select(
                                'HJ_Awal',
                                'Tgl_Akhir',
                                'HJ_Diskon',
                                'Kuota_Qty'
                            )
                            ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                            ->orderByDesc('Tgl_Akhir')
                            ->first();
                        $cDiscPeriodeStatus = "TIDAK ADA";
                        $nHJ = $vaData->HJ;
                        if ($vaDiscPeriode) {
                            $dTglSekarang = Carbon::now()->format('Y-m-d');
                            $dTglAkhir = $vaDiscPeriode->Tgl_Akhir;
                            $nHJDiskon = $vaDiscPeriode->HJ_Diskon;
                            $nKuota = $vaDiscPeriode->Kuota_Qty;
                            if ($dTglAkhir >= $dTglSekarang) {
                                $vaFakturPenjualan = DB::table('penjualan')
                                    ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                                    ->where('Harga', '=', $nHJDiskon)
                                    ->pluck('Faktur');

                                // Menyarirng FAKTUR yang memiliki KODESESI !== '' pada TOTPENJUALAN
                                $cFakturDgnKodeSesi = $vaFakturPenjualan->filter(function ($cFaktur) {
                                    $vaTotPenjualan = DB::table('totpenjualan')
                                        ->select(
                                            'KodeSesi'
                                        )
                                        ->where('Faktur', '=', $cFaktur)
                                        ->first();
                                    return $vaTotPenjualan && !empty($vaTotPenjualan->KodeSesi);
                                });
                                // Mengubah collection mejadi array
                                $vaFakturDgnKodeSesi = $cFakturDgnKodeSesi->toArray();

                                // Menghitung jumlah item yang telah dijual dengan HARGA DISKON berdasarkan FAKTUR yang telah difilter
                                $nTotalPenjualan = DB::table('penjualan')
                                    ->whereIn('Faktur', $vaFakturDgnKodeSesi)
                                    ->where('Harga', '=', $nHJDiskon)
                                    ->sum('QTY');
                                $nTotalPenjualan = $nTotalPenjualan ?? 0;
                                $nSisaKuotaPeriode = abs(intval($nTotalPenjualan) - intval($nKuota));

                                // Kondisi menghitung jika belum mencapai KUOTA 
                                if ($nTotalPenjualan < $nKuota) {
                                    $nHJ = $nHJDiskon;
                                    $cDiscPeriodeStatus = 'ADA';
                                }
                            }
                        }
                        $vaArray[] = [
                            'KODE' => $cKode,
                            'BKP' => $vaData->BKP,
                            'BARCODE' => $vaData->Kode_Toko,
                            'NAMA' => $vaData->Nama,
                            'SATUAN' => $vaData->Satuan,
                            'HARGABELI' => $vaData->HB,
                            'HJ' => $nHJ,
                            'TGLEXP' => $vaData->Expired,
                            'DISCOUNT' => empty($vaData->Discount) ? 0 : $vaData->Discount,
                            'PAJAK' => empty($vaData->Pajak) ? 0 : $vaData->Pajak,
                            'TERIMA' => $nJml,
                            'TGLPERUBAHANHJ' => $dTglPerubahan,
                            'DISKONPERIODE' => $cDiscPeriodeStatus,
                            'KUOTADISKONTERJUAL' => $nTotalPenjualan ?? 0,
                            'SISAKUOTADISKON' => $nSisaKuotaPeriode ?? 0,
                            'SISASTOCKBARANG' => $nSisaStock ?? 0
                        ];

                        // JIKA REQUEST SUKSES
                        return response()->json([
                            'status' => self::$status['SUKSES'],
                            'message' => 'Sukses',
                            'data' => $vaArray,
                            'datetime' => date('Y-m-d H:i:s')
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => 'Barang Sudah Dihapus',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Barang Tidak Ditemukan!',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function dataGrid(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['page']);
        unset($vaRequestData['auth']);
        try {
            $vaData = DB::table('stock as s')
                ->select(
                    's.FOTO',
                    's.NAMA',
                    's.KODE_TOKO',
                    's.DISCOUNT',
                    's.HJ'
                )
                ->leftJoin('satuanstock as s1', 's1.Kode', '=', 's.SATUAN')
                ->leftJoin('satuanstock as s2', 's2.Kode', '=', 's.SATUAN2')
                ->leftJoin('satuanstock as s3', 's3.Kode', '=', 's.SATUAN3')
                ->where('Status_Hapus', '=', '0')
                ->orderByDesc('s.Kode');

            // Menambahkan kondisi WHERE dengan menggunakan RIGHT dan LIKE pada kolom-kolom yang sesuai
            if (!empty($request->filters)) {
                foreach ($request->filters as $k => $v) {
                    $vaData->whereRaw("RIGHT($k, 6) LIKE ?", ['%' . $v . '%']);
                }
            }

            // Mendapatkan parameter halaman dari request atau menggunakan halaman 1 sebagai default
            $page = $request->has('page') ? max(1, intval($request->input('page'))) : 1;

            // Melakukan paginasi dengan batasan 24 baris per halaman
            $limit = 24;
            $offset = ($page - 1) * $limit; // Menghitung offset untuk halaman yang diberikan

            // Menghitung total data sebelum paginasi
            $total = $vaData->count();

            // Mengambil data dengan paginasi
            $produkPaginator = $vaData->skip($offset)->take($limit)->paginate($limit);

            // Mengembalikan data dalam bentuk paginasi manual
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $produkPaginator,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $messages = config('validate.validation');
        $vaValidator = Validator::make($request->all(), [
            'KODE' => 'required|max:20',
            'KODETOKO' => 'required|max:10',
            'NAMA' => 'required',
            'JENIS' => 'required|max:1',
            'GOLONGAN' => 'required|max:4',
            'GUDANG' => 'required|max:4',
            'SUPPLIER' => 'max:6',
            'EXPIRED' => 'date',
            'DOS' => 'max:1',
            'SATUAN' => 'required|max:4',
            'HJ' => 'required|numeric|min:0'
        ], [
            'required' => 'Kolom :attribute harus diisi.',
            'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
            'unique' => 'Kolom :attribute sudah ada di database.',
            'date' => 'Kolom :attribute harus tanggal',
            'numeric' => 'Kolom :attribute harus angka',
        ]);

        if ($vaValidator->fails()) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => $vaValidator->errors()->first(),
                'datetime' => now()->toDateTimeString()
            ], 422);
        }

        $cUser = Func::dataAuth($request);

        try {
            DB::beginTransaction(); // Mulai transaksi

            $HJ = $request->HJ ?? 0;
            $kode = $request->KODE;
            $barcode = $request->KODETOKO;
            $keterangan = "Barcode Awal " . strtoupper(trim($request->NAMA));

            $stockKode = StockKode::updateOrCreate(
                ['KODE' => $kode, 'BARCODE' => $barcode],
                ['KETERANGAN' => $keterangan]
            );

            $existingData = Stock::where('KODE', $kode)->exists();
            if ($existingData) {
                DB::rollBack(); // Rollback jika data sudah ada
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data Tidak Ada',
                    'datetime' => now()->toDateTimeString()
                ], 400);
            }

            Stock::create([
                'BKP' => $request->BKP,
                'GUDANG' => $request->GUDANG,
                'KODE' => $request->KODE,
                'KODE_TOKO' => $request->KODETOKO,
                'NAMA' => $request->NAMA,
                'JENIS' => $request->JENIS,
                'GOLONGAN' => $request->GOLONGAN,
                'RAK' => $request->RAK,
                'DOS' => $request->DOS ?? 1,
                'SATUAN' => $request->SATUAN,
                'SATUAN2' => $request->SATUAN2 ?? '',
                'SATUAN3' => $request->SATUAN3 ?? '',
                'SUPPLIER' => $request->SUPPLIER ?? '',
                'ISI' => $request->ISI ?? 0,
                'ISI2' => $request->ISI2 ?? 0,
                'DISCOUNT' => $request->DISCOUNT ?? 0,
                'PAJAK' => $request->PAJAK ?? 0,
                'MIN' => $request->MIN ?? 0,
                'MAX' => $request->MAX ?? 0,
                'HB' => $request->HB ?? 0,
                'HJ' => $HJ,
                'EXPIRED' => $request->EXPIRED,
                'TGL_MASUK' => now(),
                'FOTO' => $request->FOTO ?? '',
                'BERAT' => $request->BERAT ?? 0
            ]);

            StockHP::create([
                'Kode' => $request->KODE,
                'Tgl' => now(),
                'HP' => $request->HB ?? 0,
                'HargaBeliAwal' => $request->HB ?? 0,
                'HargaBeliAkhir' => $request->HB ?? 0,
                'HargaJualAwal' => $HJ,
                'HargaJualAkhir' => $HJ
            ]);

            if (isset($request->VARIANT)) {
                $variants = collect($request->VARIANT)->map(function ($variant) {
                    return [
                        'TIPE_VARIANT' => $variant['TIPE_VARIANT'],
                        'VARIANT' => $variant['VARIANT'],
                        'BARCODE_VARIANT' => $variant['BARCODE_VARIANT'] ?? '',
                        'HARGA_BELI_VARIANT' => $variant['HARGA_BELI_VARIANT'] ?? 0,
                        'HARGA_JUAL_VARIANT' => $variant['HARGA_JUAL_VARIANT'] ?? 0,
                        'KODE_PRODUK' => $variant['KODE_PRODUK'],
                    ];
                })->toArray();

                DB::table('stock_variant')->insert($variants);
            }

            if ($request->STOCKAWAL > 0) {
                Upd::updKartuStockProduk($request->KODE, $request->STOCKAWAL);
            }

            GetterSetter::setLastKodeRegisterStock('STK');

            DB::commit(); // Commit transaksi jika semua berhasil

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil disimpan',
                'datetime' => now()->toDateTimeString()
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback semua perubahan jika ada error
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->toDateTimeString()
            ], 500);
        }
    }

    public function getDataEdit(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $mandatoryKey = [
                'Kode'
            ];
            $vaRequestData = Func::filterArrayClean($vaRequestData, $mandatoryKey);
            if (Func::filterArrayValue($vaRequestData, $mandatoryKey) === false)
                return [];
            foreach ($mandatoryKey as $val) {
                $$val = $vaRequestData[$val];
            }
            $cKode = $vaRequestData['Kode'];
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $produkData = DB::table('stock as s')
                ->select(
                    's.Kode_Toko',
                    's.Kode',
                    's.Status_Stock',
                    's.Nama',
                    's.Jenis',
                    's.Golongan',
                    'g.Keterangan as KetGolongan',
                    's.Rak',
                    'r.Keterangan as KetRak',
                    's.Gudang',
                    'gd.Keterangan as KetGudang',
                    's.Supplier',
                    'su.Nama as NamaSupplier',
                    's.Expired',
                    's.Berat',
                    's.Satuan',
                    's1.Keterangan as KetSatuan',
                    's.Satuan2',
                    's2.Keterangan as KetSatuan2',
                    's.Satuan3',
                    's3.Keterangan as KetSatuan3',
                    's.Isi',
                    's.Isi2',
                    's.Min',
                    's.Max',
                    's.Discount',
                    's.BKP',
                    's.HB',
                    's.HB2',
                    's.HB3',
                    's.HJ',
                    's.HJ2',
                    's.HJ3',
                    's.Dos',
                    'ks.Qty',
                    's.Foto',
                    'v.ID as ID_Variant',
                    'v.Variant',
                    'v.Tipe_Variant',
                    'v.Harga_Beli_Variant',
                    'v.Harga_Jual_Variant',
                    'v.Barcode_Variant',
                    'v.Kode_Produk',
                )
                ->leftJoin('golonganstock as g', 'g.Kode', '=', 's.Golongan')
                ->leftJoin('stock_variant as v', 's.Kode', '=', 'v.KODE_PRODUK')
                ->leftJoin('supplier as su', 'su.Kode', '=', 's.Supplier')
                ->leftJoin('satuanstock as s1', 's1.Kode', '=', 's.Satuan')
                ->leftJoin('satuanstock as s2', 's2.Kode', '=', 's.Satuan2')
                ->leftJoin('satuanstock as s3', 's3.Kode', '=', 's.Satuan3')
                ->leftJoin('rak as r', 'r.Kode', '=', 's.Rak')
                ->leftJoin('gudang as gd', 'gd.Kode', '=', 's.Gudang')
                ->leftJoin('kartustock as ks', function ($join) {
                    $join->on('ks.Kode', '=', 's.Kode')
                        ->where('ks.Status', '=', 'SA');
                })
                ->where('s.Kode', '=', $cKode)
                ->get();

            // pisah data produk dan data varian
            $vaData = $produkData->first();
            $variants = $produkData->map(function ($item) {
                if (!isset($item->ID_Variant)) {
                    return null;
                }
                return [
                    'ID' => $item->ID_Variant,
                    'VARIANT' => $item->Variant,
                    'TIPE_VARIANT' => $item->Tipe_Variant,
                    'HARGA_BELI_VARIANT' => $item->Harga_Beli_Variant,
                    'HARGA_JUAL_VARIANT' => $item->Harga_Jual_Variant,
                    'BARCODE_VARIANT' => $item->Barcode_Variant,
                    'KODE_PRODUK' => $item->Kode_Produk,
                ];
            })->filter();

            if ($vaData) {
                $vaArray = [
                    'KODETOKO' => $vaData->Kode_Toko,
                    'KODE' => $vaData->Kode,
                    'NAMA' => $vaData->Nama,
                    'JENIS' => $vaData->Jenis,
                    'GOLONGAN' => $vaData->Golongan,
                    'KETGOLONGAN' => $vaData->KetGolongan,
                    'RAK' => $vaData->Rak,
                    'KETRAK' => $vaData->KetRak,
                    'GUDANG' => $vaData->Gudang,
                    'KETGUDANG' => $vaData->KetGudang,
                    'SUPPLIER' => $vaData->Supplier,
                    'NAMASUPPLIER' => $vaData->NamaSupplier,
                    'EXPIRED' => $vaData->Expired,
                    'BERAT' => $vaData->Berat,
                    'SATUAN' => $vaData->Satuan,
                    'KETSATUAN1' => $vaData->KetSatuan,
                    'SATUAN2' => $vaData->Satuan2,
                    'KETSATUAN2' => $vaData->KetSatuan2,
                    'SATUAN3' => $vaData->Satuan3,
                    'KETSATUAN3' => $vaData->KetSatuan3,
                    'ISI' => $vaData->Isi,
                    'ISI2' => $vaData->Isi2,
                    'MIN' => $vaData->Min,
                    'MAX' => $vaData->Max,
                    'DISCOUNT' => $vaData->Discount,
                    'BKP' => $vaData->BKP,
                    'HB' => $vaData->HB,
                    'HB2' => $vaData->HB2,
                    'HB3' => $vaData->HB3,
                    'HJ' => $vaData->HJ,
                    'HJ2' => $vaData->HJ2,
                    'HJ3' => $vaData->HJ3,
                    'DOS' => $vaData->Dos,
                    'STATUS_STOCK' => $vaData->Status_Stock,
                    'STOCKAWAL' => intval($vaData->Qty ?? 0),
                    'FOTO' => $vaData->Foto,
                    'VARIANT' => $variants,
                ];
            } // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaArray
            ];
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaArray,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $KODE = $request->KODE;
            $messages = config('validate.validation');
            $validator = validator::make($request->all(), [
                'KODE' => 'required|max:20',
                'KODE_TOKO' => 'max:255',
                'NAMA' => 'required',
                'JENIS' => 'max:1',
                'GOLONGAN' => 'max:4',
                'RAK' => 'max:4',
                'GUDANG' => 'max:4',
                'SUPPLIER' => 'max:6',
                'EXPIRED' => 'date',
                'TGL_MASUK' => 'date',
                'DOS' => 'max:1',
                'SATUAN' => 'max:4',
                'SATUAN2' => 'max:4',
                'SATUAN3' => 'max:4',
                'ISI' => 'numeric|min:0',
                'ISI2' => 'numeric|min:0',
                'DISCOUNT' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                'MIN' => 'numeric|min:0',
                'MAX' => 'numeric|min:0',
                'HB' => 'numeric|min:0',
                'HB2' => 'numeric|min:0',
                'HB3' => 'numeric|min:0',
                'HJ' => 'numeric|min:0',
                'HJ2' => 'numeric|min:0',
                'HJ3' => 'numeric|min:0',
                'HJ_TINGKAT1' => 'numeric|min:0',
                'HJ_TINGKAT2' => 'numeric|min:0',
                'HJ_TINGKAT3' => 'numeric|min:0',
                'HJ_TINGKAT4' => 'numeric|min:0',
                'HJ_TINGKAT5' => 'numeric|min:0',
                'HJ_TINGKAT6' => 'numeric|min:0',
                'HJ_TINGKAT7' => 'numeric|min:0',
                'MIN_TINGKAT1' => 'numeric|min:0',
                'MIN_TINGKAT2' => 'numeric|min:0',
                'MIN_TINGKAT3' => 'numeric|min:0',
                'MIN_TINGKAT4' => 'numeric|min:0',
                'MIN_TINGKAT5' => 'numeric|min:0',
                'MIN_TINGKAT6' => 'numeric|min:0',
                'MIN_TINGKAT7' => 'numeric|digits_between:1,16'
            ], $messages);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $message = implode(' ', $errors);
                return response()->json(array_merge(ApiResponse::INVALID_REQUEST, ['messageValidator' => $message]));
            }

            $barcode = $request->KODETOKO;
            $keterangan = "Barcode Awal  " . strtoupper(trim($request->NAMA));
            $exists = StockKode::where('Kode', $KODE)
                ->where('Barcode', $barcode)
                ->exists();
            if ($exists) {
                $stockKode = StockKode::where('Kode', $KODE)
                    ->where('Barcode', $barcode)
                    ->first();
                $stockKode->KETERANGAN = $keterangan;
                $stockKode->save();
            } else {
                // Update StockKode jika ada perubahan
                $stockKode = StockKode::updateOrCreate(
                    ['KODE' => $KODE, 'BARCODE' => $barcode],
                    ['KETERANGAN' => $keterangan]
                );
            }

            $stock = Stock::findOrFail($KODE);
            $stock->BKP = $request->BKP;
            $stock->KODE_TOKO = $request->KODETOKO;
            $stock->NAMA = $request->NAMA;
            $stock->JENIS = $request->JENIS;
            $stock->GOLONGAN = $request->GOLONGAN;
            $stock->RAK = $request->RAK;
            $stock->GUDANG = $request->GUDANG;
            $stock->SUPPLIER = $request->SUPPLIER ?? '';
            $stock->EXPIRED = $request->EXPIRED;
            $stock->BERAT = $request->BERAT ?? 0;
            $stock->DOS = $request->DOS ?? 1;
            $stock->SATUAN = $request->SATUAN;
            $stock->SATUAN2 = $request->SATUAN2 ?? '';
            $stock->SATUAN3 = $request->SATUAN3 ?? '';
            $stock->ISI = $request->ISI ?? 0;
            $stock->ISI2 = $request->ISI2 ?? 0;
            $stock->DISCOUNT = $request->DISCOUNT ?? 0;
            $stock->PAJAK = $request->PAJAK ?? 0;
            $stock->MIN = $request->MIN ?? 0;
            $stock->MAX = $request->MAX ?? 0;
            $stock->HB = $request->HB ?? 0;
            $stock->HB2 = $request->HB2 ?? 0;
            $stock->HB3 = $request->HB3 ?? 0;
            $stock->HJ = $request->HJ ?? 0;
            $stock->HJ2 = $request->HJ2 ?? 0;
            $stock->HJ3 = $request->HJ3 ?? 0;
            $stock->HJ_TINGKAT1 = $request->HJ_TINGKAT1 ?? 0;
            $stock->HJ_TINGKAT2 = $request->HJ_TINGKAT2 ?? 0;
            $stock->HJ_TINGKAT3 = $request->HJ_TINGKAT3 ?? 0;
            $stock->HJ_TINGKAT4 = $request->HJ_TINGKAT4 ?? 0;
            $stock->HJ_TINGKAT5 = $request->HJ_TINGKAT5 ?? 0;
            $stock->HJ_TINGKAT6 = $request->HJ_TINGKAT6 ?? 0;
            $stock->HJ_TINGKAT7 = $request->HJ_TINGKAT7 ?? 0;
            $stock->MIN_TINGKAT1 = $request->MIN_TINGKAT1 ?? 0;
            $stock->MIN_TINGKAT2 = $request->MIN_TINGKAT2 ?? 0;
            $stock->MIN_TINGKAT3 = $request->MIN_TINGKAT3 ?? 0;
            $stock->MIN_TINGKAT4 = $request->MIN_TINGKAT4 ?? 0;
            $stock->MIN_TINGKAT5 = $request->MIN_TINGKAT5 ?? 0;
            $stock->MIN_TINGKAT6 = $request->MIN_TINGKAT6 ?? 0;
            $stock->MIN_TINGKAT7 = $request->MIN_TINGKAT7 ?? 0;
            $stock->FOTO = $request->FOTO ?? '';
            $stock->save();

            $stockHP = StockHP::where('Kode', $request->KODE)->first();
            if ($stockHP) {
                // Update StockHP
                $stockHP->Tgl = Carbon::now();
                $stockHP->HP = $request->HB ?? 0;
                $stockHP->HargaBeliAwal = $request->HB ?? 0;
                $stockHP->HargaBeliAkhir = $request->HB ?? 0;
                $stockHP->HargaJualAwal = $request->HJ ?? 0;
                $stockHP->HargaJualAkhir = $request->HJ ?? 0;
                $stockHP->save();
            } else {
                StockHP::create([
                    'Kode' => $request->KODE,
                    'Tgl' => Carbon::now(),
                    'HP' => $request->HB ?? 0,
                    'HargaBeliAwal' => $request->HB ?? 0,
                    'HargaBeliAkhir' => $request->HB ?? 0,
                    'HargaJualAwal' => $request->HJ ?? 0,
                    'HargaJualAkhir' => $request->HJ ?? 0
                ]);
            }


            if (isset($request->VARIANT)) {
                //update insert delete batch :)
                $variants = $request->VARIANT;
                DB::table('stock_variant')->upsert(
                    $variants,
                    ['ID'],
                    [
                        'TIPE_VARIANT',
                        'VARIANT',
                        'BARCODE_VARIANT',
                        'HARGA_BELI_VARIANT',
                        'HARGA_JUAL_VARIANT',
                        'KODE_PRODUK'
                    ]
                );

                // di filter agar membedakan variant yang insert dan update
                $variantId = collect($variants)->pluck('ID')->filter()->toArray();

                if (!empty($variantId)) {
                    DB::table('stock_variant')->whereNotIn('ID', $variantId)->delete();
                }
            }

            if ($request->STOCKAWAL > 0) {
                if ($request->STOCKAWAL == 'Unlimited') {
                    $vaArray = [
                        'STATUS_STOCK' => '1'
                    ];
                    Stock::where('Kode', '=', $request->KODE)->update($vaArray);
                }

                if ($request->STOCKAWAL > 0) {
                    Upd::updKartuStockProduk($request->KODE, $request->STOCKAWAL);
                }
            }
            // GetterSetter::setLastKodeRegisterStock('STK', 10);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil disimpan',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }


    public function delete(Request $request)
    {
        try {
            $cKode = $request->KODE;
            $vaArray = [
                'STATUS_HAPUS' => '1'
            ];
            Stock::where('Kode', '=', $cKode)
                ->update($vaArray);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil dihapus',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function priceTag(Request $request)
    {
        try {
            $pricetagArray = $request->json('pricetag');
            $pricetags = collect($pricetagArray)->map(function ($item) {
                $pricetag = new Stock;
                $pricetag->nama = $item['NAMA'];
                $pricetag->HJ = $item['HJ'];
                $pricetag->barcode = $item['KODE_TOKO'];
                $pricetag->jumlah = $item['JUMLAH'];
                return $pricetag;
            });

            $pdf = PDF::loadView('master\produk\printPriceTag', compact('pricetags'));

            return $pdf->download('pricetag.pdf');

        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function insertDiscStok(Request $request)
    {
        try {

            $validator = validator::make($request->all(), [
                'KODE_STOCK' => 'max:50',
                'BARCODE' => 'max:50',
                'HJ1' => 'numeric|min:0',
                'H_DISKON' => 'numeric|min:0',
                'TGL_BERAKHIR' => 'date',
                'TGL_BERMULA' => 'date',
                'QTY' => 'numeric|min:0',
                'STATUS' => 'max:1'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            $pricetagDiskon = $request->json('pricetagDiskon');
            $discPriceTag = [];

            foreach ($pricetagDiskon as $row) {
                $barcode = $row['BARCODE'];
                $hj1 = $row['HJ1'];
                $hDiskon = $row['HDISKON'];
                $tglBerakhir = $row['TGLBERAKHIR'];
                $qty = $row['QTY'];
                $kodeStock = '';
                $status = $row['STATUS'];

                $stock = DB::table('stock')
                    ->select('KODE', 'NAMA')
                    ->where('KODE_TOKO', '=', $barcode)
                    ->first();

                if ($stock) {
                    $kodeStock = $stock->KODE;
                    $nama = $stock->NAMA;
                    $namaProduk[] = $nama;
                }

                $discPriceTag[] = [
                    'KODE_STOCK' => $kodeStock,
                    'BARCODE' => $barcode,
                    'HJ1' => $hj1,
                    'H_DISKON' => $hDiskon,
                    'TGL_BERMULA' => Carbon::now()->toDateString(),
                    'TGL_BERAKHIR' => $tglBerakhir,
                    'QTY' => $qty,
                    'STATUS' => $status
                ];
            }
            // dd($discPriceTag->$hDiskon);
            DiskonStock::insert($discPriceTag);
            $pdf = PDF::loadView('master\produk\printPriceTagDisc', compact('discPriceTag', 'namaProduk'));
            return $pdf->download('pricetag_disc.pdf');

        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function updStatusHapus(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cKode = $vaRequestData['Kode'];
            $vaData = DB::table('stock')
                ->select('STATUS_HAPUS')
                ->where('KODE', '=', $cKode)
                ->first();
            if ($vaData) {
                $cStatusHapus = $vaData->STATUS_HAPUS;
                if ($cStatusHapus === '0') {
                    Stock::where('Kode', '=', $cKode)
                        ->update(
                            ['STATUS_HAPUS' => '1']
                        );
                }
                if ($cStatusHapus === '1') {
                    Stock::where('Kode', '=', $cKode)
                        ->update(
                            ['STATUS_HAPUS' => '0']
                        );
                }
            }  // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil dihapus',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
}
