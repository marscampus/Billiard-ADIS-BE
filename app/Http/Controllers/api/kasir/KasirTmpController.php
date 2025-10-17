<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuStockTmp;
use App\Models\penjualan\PenjualanTmp;
use App\Models\penjualan\TotPenjualanTmp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KasirTmpController extends Controller
{
    // ------------------------------------------------------------------< Emm >
    public function store(Request $request)
    {
        $cUser = Func::dataAuth($request);
        // dd($request->all());
        try {

            $faktur = GetterSetter::getLastFaktur('TMP', 6);
            // ------------------------------------------------------------------------------------< TotPenjualan >
            $dataTotPenjualan = new TotPenjualanTmp([
                'FAKTUR' => $faktur,
                'KODESESI' => $request->KODESESI, //FE dari sesi_jual - FAKTUR
                'CATATAN' => $request->CATATAN, //FE dari sesi_jual - FAKTUR
                // 'KODESESI_RETUR' => '',
                'TGL' => $request->TGL, //FE dari sesi_jual - TGL
                'GUDANG' => null, //FE dari sesi_jual - TOKO
                'DISCOUNT' => $request->DISCOUNT,
                'DISCOUNT2' => $request->DISCOUNT2,
                'PAJAK' => $request->PAJAK,
                'TOTAL' => $request->TOTAL,
                'CARABAYAR' => null,
                'TUNAI' => null, // TUNAI
                'BAYARKARTU' => null, //DEBIT
                'BIAYAKARTU' => null,
                'AMBILKARTU' => null, // Tarik Tunai
                'EPAYMENT' => null, // EPAYMENT
                'NAMAKARTU' => null,
                'NOMORKARTU' => null,
                'NAMAPEMILIK' => null,
                'TIPEEPAYMENT' => null,
                'KEMBALIAN' => null,
                'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
                'USERNAME' => $cUser,
                // 'USERNAME' => $request->USERNAME, // FE dari sesi_jual - TMP
            ]);
            // dd($dataTotPenjualan);
            $dataTotPenjualan->save();
            // ------------------------------------------------------------------------------------< Penjualan >
            $varGudang = $dataTotPenjualan->GUDANG;
            $varTgl = $dataTotPenjualan->TGL;
            foreach ($request->detail_penjualan as $detail) {
                $dataPenjualan = new PenjualanTmp([
                    'FAKTUR' => $faktur,
                    'TGL' => $varTgl, //FE dari sesi_jual - TGL
                    'KODE' => $detail['KODE'],
                    // 'BARCODE' => $detail['BARCODE'],
                    // 'NAMA' => $detail['NAMA'],
                    'QTY' => $detail['QTY'],
                    'TGL' => $dataTotPenjualan->TGL, // Ambil TGL dari dataTotPenjualan
                    'SATUAN' => $detail['SATUAN'],
                    'PPN' => $detail['PPN'],
                    'DISCOUNT' => $detail['DISCOUNT'],
                    'HARGADISC' => $detail['HARGADISC'],
                    'HARGA' => $detail['HARGA'],
                    'JUMLAH' => $detail['JUMLAH'],
                    'KETERANGAN' => $detail['KETERANGAN'],
                ]);
                // dd($dataPenjualan);
                $dataPenjualan->save();
            }
            // GetterSetter::setLastFaktur('TMP');
            GetterSetter::setLastKodeRegister('TMP');
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getFaktur(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $vaValidator = Validator::make($request->all(), [
                'KODESESI' => 'required',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom ::attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $cSesiAktif = $vaRequestData['KODESESI'];
            $vaExists = DB::table('totpenjualan_tmp')
                ->where('KODESESI', '=', $cSesiAktif)
                ->exists();
            if ($vaExists) {
                $vaData = DB::table('totpenjualan_tmp as tp')
                    ->select(
                        'tp.Faktur',
                        'tp.Catatan',
                        'tp.Tgl',
                        'tp.Discount',
                        'tp.Pajak',
                        'tp.Total',
                        'u.FullName'
                    )
                    ->leftJoin('username as u', 'u.UserName', '=', 'tp.UserName')
                    ->where('tp.KODESESI', '=', $cSesiAktif);
                if (!empty($request->filters)) {
                    foreach ($request->filters as $k => $v) {
                        // Menambahkan kondisi WHERE dengan menggunakan RIGHT dan LIKE pada kolom-kolom yang sesuai
                        $vaData->whereRaw("$k LIKE ?", ['%' . $v . '%']);
                    }
                }
                $vaData->groupBy('tp.faktur');
                $vaData->orderByDesc('tp.Faktur');
                $vaData = $vaData->get();

                foreach ($vaData as $d) {
                    $result = [
                        "FAKTUR" => $d->Faktur,
                        "CATATAN" => $d->Catatan,
                        "TGL" => $d->Tgl,
                        "DISCOUNT" => $d->Discount,
                        "PAJAK" => $d->Pajak,
                        "TOTAL" => $d->Total,
                        "USERNAME" => $d->FullName
                    ];
                    $results[] = $result;
                }
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data Tidak Ada',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $results,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);

        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function detailFaktur(Request $request)
    {
        try {
            $cFaktur = $request->FAKTUR;

            // Ambil data header (totpenjualan_tmp)
            $header = DB::table('totpenjualan_tmp')
                ->select('tgl', 'catatan', 'username', 'total')
                ->where('faktur', '=', $cFaktur)
                ->first();

            // Inisialisasi array untuk response
            $vaArray = [
                'header' => null,
                'detail' => []
            ];

            if ($header) {
                // Set data header
                $vaArray['header'] = [
                    'FAKTUR' => $cFaktur,
                    'TGL' => $header->tgl,
                    'CATATAN' => $header->catatan,
                    'USERNAME' => $header->username,
                    'TOTAL' => $header->total,
                ];

                // Ambil data detail dari penjualan_tmp
                $detailData = DB::table('penjualan_tmp as p')
                    ->select(
                        'p.Qty',
                        'st.Status_Stock',
                        'p.Kode',
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
                        // 'sa.Keterangan as Satuan', // jika diperlukan, uncomment
                        'gu.Keterangan as Gudang',
                        'st.Status_Hapus',
                        DB::raw('IFNULL(SUM(ks.Debet - ks.Kredit),0) as SisaStock')
                    )
                    ->leftJoin('stock as st', 'st.kode', '=', 'p.kode')
                    ->leftJoin('golonganstock as gs', 'gs.kode', '=', 'st.GOLONGAN')
                    ->leftJoin('satuanstock as sa', 'sa.kode', '=', 'st.SATUAN')
                    ->leftJoin('gudang as gu', 'gu.kode', '=', 'st.GUDANG')
                    ->leftJoin('kartustock as ks', 'ks.kode', '=', 'st.kode')
                    ->where('st.Status_Hapus', '=', '0')
                    ->where('p.faktur', '=', $cFaktur)
                    ->groupBy('p.Kode')
                    ->get();

                $details = [];

                foreach ($detailData as $d) {
                    $cBarcode = $d->Kode_Toko;
                    $cStatusHapus = $d->Status_Hapus;
                    $nSisaStock = $d->SisaStock;
                    $cStatusStock = $d->Status_Stock;

                    // Jika status stock = 1, berarti unlimited
                    if ($cStatusStock == '1') {
                        $nSisaStock = 'Unlimited';
                    } elseif ($nSisaStock <= 0) {
                        $nSisaStock = 'Habis';
                    }

                    // Hanya proses jika status hapus = 0
                    if ($cStatusHapus == '0') {
                        $cKode = $d->Kode;

                        // Ambil tanggal perubahan harga dari perubahanhargastock
                        $dTglPerubahan = DB::table('perubahanhargastock')
                            ->where('Kode', '=', $cKode)
                            ->orderByDesc('DateTime')
                            ->value('Tanggal_Perubahan');

                        // Cek diskon periode
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
                        $nTotalPenjualan = 0;
                        $nSisaKuotaPeriode = 0;

                        if ($vaDiscPeriode) {
                            $dTglSekarang = Carbon::now()->format('Y-m-d');
                            $dTglAkhir = $vaDiscPeriode->Tgl_Akhir;
                            $nHJDiskon = $vaDiscPeriode->HJ_Diskon;
                            $nKuota = $vaDiscPeriode->Kuota_Qty;

                            if ($dTglAkhir >= $dTglSekarang) {
                                // Ambil faktur dari tabel penjualan dengan diskon harga
                                $vaFakturPenjualan = DB::table('penjualan')
                                    ->where('Barcode', 'LIKE', '%' . $cBarcode . '%')
                                    ->where('Harga', '=', $nHJDiskon)
                                    ->pluck('Faktur');

                                // Filter faktur yang memiliki KodeSesi di totpenjualan
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
                                }
                            }
                        }

                        // Tambahkan data detail ke array detail
                        $details[] = [
                            'KODE' => $cKode,
                            'BKP' => $d->BKP,
                            'KODE_TOKO' => $d->Kode_Toko,
                            'NAMA' => $d->Nama,
                            'GOLONGAN' => $d->Golongan,
                            'GUDANG' => $d->Gudang,
                            'SATUAN' => $d->Satuan,
                            'QTY' => $d->Qty,
                            'HARGABELI' => $d->HB,
                            'HJ' => $nHJ,
                            'JUMLAH' => $nHJ * $d->Qty,
                            'TGLEXP' => $d->Expired,
                            'DISCOUNT' => $d->Discount ?? 0,
                            'PAJAK' => $d->Pajak ?? 0,
                            'TERIMA' => 1, // Misal selalu 1
                            'TGLPERUBAHANHJ' => $dTglPerubahan,
                            'DISKONPERIODE' => $cDiscPeriodeStatus,
                            'KUOTADISKONTERJUAL' => $nTotalPenjualan,
                            'SISAKUOTADISKON' => $nSisaKuotaPeriode,
                            'SISASTOCKBARANG' => $nSisaStock,
                            'FOTO' => $d->Foto
                        ];
                    }
                }

                // Masukkan data detail ke response utama
                $vaArray['detail'] = $details;
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }


    public function deleteFaktur(Request $request)
    {
        try {
            // Cari FAKTUR yang sama dalam model TotPenjualan dan KartuStock
            $vaValidator = Validator::make($request->all(), [
                'FAKTUR' => 'required',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom ::attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $faktur = $request->FAKTUR;
            $totPenjualan = TotPenjualanTmp::where('FAKTUR', $faktur)->first();
            $penjualan = PenjualanTmp::where('FAKTUR', $faktur)->get();
            // $kartuStock = KartuStockTmp::where('FAKTUR', $faktur)->first();
            // Menghapus data dari model jika ditemukan
            if ($totPenjualan) {
                $totPenjualan->delete();
            }
            if ($penjualan) {
                $penjualan->each(function ($item) {
                    $item->delete();
                });
            }
            // if ($kartuStock) {
            //     $kartuStock->delete();
            // }
            // return response()->json([
            //     'status' => 'success'
            // ]);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menghapus Data',
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
