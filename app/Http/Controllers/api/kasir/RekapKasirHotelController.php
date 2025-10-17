<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\Jurnal;
use App\Models\kasir\JurnalUangPecahan;
use App\Models\kasir\SesiJual;
use App\Models\master\UangPecahan;
use App\Models\penjualan\TotPenjualan;
use Carbon\Carbon;
use GuzzleHttp\Cookie\SessionCookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RekapKasirHotelController extends Controller
{

    public function getUangPecahan(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'SESIJUAL' => 'required|max:20',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'numeric' => 'Kolom :attribute harus angka'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $sesiJual = $request->input('SESIJUAL');
            $uangPecahanData = JurnalUangPecahan::where('FAKTUR', $sesiJual)->get();
            // dd($uangPecahanData);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $uangPecahanData,
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

    public function getTotal(Request $request)
    {
        try {
            $SESIJUAL = $request->SESIJUAL;
            $KASAWAL = 0;
            $TOTALJUAL = 0;
            $SUBTOTAL1 = 0;
            $DEBIT = 0;
            $PENGAMBILANTUNAI = 0;
            $EPAYMENT = 0;
            $VOUCHER = 0;
            $SUBTOTAL2 = 0;
            $JUMLAHUANG = 0;
            $DISCOUNT = 0;
            $PPN = 0;
            $KASAWAL = 0;
            $DONASI = 0;
            $vaSesiJual = DB::table('sesi_jual')
                ->select('KASAWAL')
                ->where('SesiJual', '=', $SESIJUAL)
                ->first();
            if ($vaSesiJual) {
                $KASAWAL = $vaSesiJual->KASAWAL;


                $totPenjualan = DB::table('invoice')
                    ->selectRaw('
                            IFNULL(
                                    SUM(
                                        total_kamar
                                        + (total_kamar * ppn / 100)
                                        - (total_kamar * disc / 100)
                                        - sisa_bayar
                                    ),
                                0
                            ) AS TOTALJUAL,
                            IFNULL(SUM(total_kamar * disc / 100), 0) as DISCOUNT,
                            IFNULL(SUM(total_kamar * ppn / 100), 0) as PPN                 
                    ')
                    ->where('sesi_jual', $SESIJUAL)
                    ->first();


                if ($totPenjualan) {
                    $TOTALJUAL = $totPenjualan->TOTALJUAL;
                    $SUBTOTAL1 = $KASAWAL + $TOTALJUAL;
                    $DISCOUNT = $totPenjualan->DISCOUNT;
                    $PPN = $totPenjualan->PPN;
                    $JUMLAHUANG = $SUBTOTAL1;
                }
            }
            $result = [
                "KASAWAL" => $KASAWAL,
                "TOTALJUAL" => $TOTALJUAL,
                "SUBTOTAL1" => $SUBTOTAL1,
                "DISCOUNT" => $DISCOUNT,
                "PPN" => $PPN,
                "JUMLAHUANG" => $JUMLAHUANG
            ];
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $result,
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
    // ----------------------------------------------< emm >

    public function save(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $SESIJUAL = $request->KODESESI;
            $USERNAME = $cUser; // GET CONFIG
            // $CABANG = GetterSetter::getGudang($USERNAME); // GET CONFIG
            // JURNAL UANG PECAHAN
            $KETERANGAN = '';
            $reqKeterangan = $request->KETERANGAN;
            $sesiJual = SesiJual::where('SESIJUAL', $SESIJUAL)
                ->limit(1)
                ->first();
            if ($sesiJual) {
                $existsJurnal = Jurnal::where('FAKTUR', $SESIJUAL)
                    ->exists();
                if ($existsJurnal) {
                    Jurnal::where('FAKTUR', $SESIJUAL)
                        ->where('TGL', $sesiJual->TGL)
                        ->delete();
                }


                $KETERANGAN = "PENJUALAN SESI " . $SESIJUAL . ' - ' . $sesiJual->KASIR . ' SPV-' . $sesiJual->SUPERVISOR . ' TOKO-' . $sesiJual->TOKO . ' ' . $reqKeterangan;


            }
            // PENJUALAN
            $totPenjualan = DB::table('invoice')->selectRaw('
                    IFNULL(
                        SUM(
                                total_kamar
                                + (total_kamar * ppn / 100)
                                - (total_kamar * disc / 100)
                                - sisa_bayar
                            ),
                            0
                        ) AS TOTALJUAL,
                    IFNULL(SUM(total_kamar * disc / 100), 0) as DISCOUNT,
                    IFNULL(SUM(total_kamar * ppn / 100), 0) as PPN')
                ->where('sesi_jual', $SESIJUAL)

                ->first();
            if ($totPenjualan) {
                $sesiJual = SesiJual::where('SESIJUAL', $SESIJUAL)
                    ->limit(1)
                    ->first();
                if ($sesiJual) {
                    if ($totPenjualan->TOTALJUAL > 0) {
                        $data = [
                            "TOTALJUAL" => round($totPenjualan->TOTALJUAL),
                            "TOTALTUNAI" => round($request->JUMLAHUANGFISIK),
                            "KARTU" => round(0),
                            "KREDIT" => round(0),
                            "EPAYMENT" => round(0),
                            "VOUCHER" => round(0),
                            "DISCOUNT" => round($totPenjualan->DISCOUNT),
                            "PPN" => round($totPenjualan->PPN),
                            "PROSES" => "P",
                            "NOMORGL" => '',
                            "KETERANGAN" => $KETERANGAN,
                            "DATETIME" => Carbon::now()
                        ];
                        SesiJual::where('SESIJUAL', $SESIJUAL)->update($data);
                    } else {
                        $data = [
                            "PROSES" => 'P',
                            "KETERANGAN" => $KETERANGAN,
                            "DATETIME" => Carbon::now()
                        ];
                        SesiJual::where('SESIJUAL', $SESIJUAL)->update($data);
                    }
                }
            }

        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function get_penjualanBySesi(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'SESIJUAL' => 'required',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'numeric' => 'Kolom :attribute harus angka'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            // Mendapatkan data berdasarkan FAKTUR
            $sesiJual = $request->SESIJUAL;
            $totPenjualan = DB::table('invoice as i')
                ->select(
                    'i.kode_invoice',
                    'i.nama_tamu',
                    'i.nik',
                    'i.no_telepon',
                    'i.bayar',
                    'i.total_harga',
                    'p.keterangan as cara_bayar',
                    'i.tgl',
                    'i.total_kamar',
                    'i.status_bayar',
                    'i.kembalian',
                    'i.sisa_bayar',
                    'i.dp',
                    'i.sesi_jual',
                    'i.ppn',
                    's.kasir',
                    'i.disc'
                )
                ->leftJoin('pembayaran as p', 'i.cara_bayar', 'p.kode')
                ->leftJoin('sesi_jual as s', 'i.sesi_jual', 's.sesijual')
                ->where('sesi_jual', $sesiJual)
                ->orderByDesc('i.tgl')
                ->get();
            // $totPenjualan = DB::table('penjualan')->where('KODESESI', $sesiJual)->get();

            // Inisialisasi variabel total
            $totTunai = 0;
            $totTotal = 0;
            $totEpayment = 0;
            $totDebit = 0;
            $totDisc = 0;
            $totPPN = 0;
            $totDonasi = 0;
            $kasir = '';

            // Iterasi pada data $totPenjualan
            foreach ($totPenjualan as $penjualan) {
                $totTotal += $penjualan->TOTAL ?? 0;

                $totDisc += $penjualan->DISCOUNT1 ?? 0;
                $totPPN += $penjualan->PAJAK2 ?? 0;
                $kasir = $penjualan->USERNAME ?? 0;
                // Tambahkan logika untuk menghitung total lainnya sesuai kebutuhan
            }

            // Buat array total
            $total = [
                'totTunai' => $totTunai,
                'totTotal' => $totTotal,
                'totDisc' => $totDisc,
                'totPPN' => $totPPN,
                'kasir' => $kasir,
            ];

            // Check jika data $totPenjualan tidak kosong
            if ($totPenjualan->isNotEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Sukses',
                    'totPenjualan' => $totPenjualan,
                    'total' => $total,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            } else {
                // return response()->json(['status' => 'error']);
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Sukses',
                    'totPenjualan' => [],
                    'total' => $total,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }

        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
        // Validasi request jika diperlukan

    }
}
