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

class RekapKasirController extends Controller
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
                $totPenjualan = TotPenjualan::selectRaw('
                    IFNULL(SUM(total), 0) as TOTALJUAL, 
                    IFNULL(SUM(bayarkartu), 0) as KARTU,
                    IFNULL(SUM(ambilkartu), 0) as KREDIT,
                    IFNULL(SUM(epayment), 0) as EPAYMENT, 
                    IFNULL(SUM(voucher), 0) as VOUCHER, 
                    IFNULL(SUM(discount+discount2), 0) as DISCOUNT,
                    IFNULL(SUM(pajak+pajak2), 0) as PPN,                    
                    IFNULL(SUM(donasi), 0) as DONASI,
                    IFNULL(SUM(biayakartu), 0) as BIAYAKARTU
                    ')
                    ->where('KODESESI', $SESIJUAL)
                    ->first();
                if ($totPenjualan) {
                    $TOTALJUAL = $totPenjualan->TOTALJUAL;
                    $SUBTOTAL1 = $KASAWAL + $TOTALJUAL;
                    $DEBIT = $totPenjualan->KARTU;
                    $PENGAMBILANTUNAI = $totPenjualan->KREDIT;
                    $EPAYMENT = $totPenjualan->EPAYMENT;
                    $VOUCHER = $totPenjualan->VOUCHER;
                    $DISCOUNT = $totPenjualan->DISCOUNT;
                    $PPN = $totPenjualan->PPN;
                    $BIAYAKARTU = $totPenjualan->BIAYAKARTU ?? 0;
                    $DONASI = $totPenjualan->DONASI;
                    $SUBTOTAL2 = $DEBIT + $PENGAMBILANTUNAI + $EPAYMENT + $VOUCHER;
                    $JUMLAHUANG = $SUBTOTAL1 - $SUBTOTAL2;
                }
            }
            $result = [
                "KASAWAL" => $KASAWAL,
                "TOTALJUAL" => $TOTALJUAL,
                "SUBTOTAL1" => $SUBTOTAL1,
                "DEBIT" => $DEBIT,
                "PENGAMBILANTUNAI" => $PENGAMBILANTUNAI,
                "EPAYMENT" => $EPAYMENT,
                "VOUCHER" => $VOUCHER,
                "DISCOUNT" => $DISCOUNT,
                "PPN" => $PPN,
                "DONASI" => $DONASI,
                "SUBTOTAL2" => $SUBTOTAL2,
                "BIAYAKARTU" => $BIAYAKARTU,
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
            $TOTALFISIK = Func::String2Number($request->TOTAL);
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
                ;
                $existsJurnalUangPecahan = JurnalUangPecahan::where('FAKTUR', $SESIJUAL)
                    ->exists();
                if ($existsJurnalUangPecahan) {
                    JurnalUangPecahan::where('FAKTUR', $SESIJUAL)
                        ->where('TGL', $sesiJual->TGL)
                        // ->where('STATUS', $e)
                        ->delete();
                }
                $KETERANGAN = "PENJUALAN SESI " . $SESIJUAL . ' - ' . $sesiJual->KASIR . ' SPV-' . $sesiJual->SUPERVISOR . ' TOKO-' . $sesiJual->TOKO . ' ' . $reqKeterangan;
                // $uangPecahan = UangPecahan::orderBy('STATUS')
                //     ->orderBy('NOMINAL')
                //     ->get();
                foreach ($request->input('uangpecahan') as $u) {
                    $QTY = $u['QTY'];
                    if ($QTY > 0) {
                        $data = [
                            'FAKTUR' => $SESIJUAL,
                            'KODE' => $u['KODE'],
                            'TGL' => $sesiJual['TGL'], //TGL SESI_JUAL KAH?
                            'STATUS' => $u['STATUS'],
                            'NOMINAL' => round($u['NOMINAL']),
                            'QTY' => round($QTY),
                            'KETERANGAN' => $KETERANGAN,
                            'USERNAME' => $cUser,
                            // 'USERNAME' => $USERNAME,
                            'CABANGENTRY' => ''
                        ];
                        JurnalUangPecahan::create($data);
                    }
                }
            }
            // PENJUALAN
            $totPenjualan = TotPenjualan::selectRaw('IFNULL(SUM(total), 0) as TOTALJUAL, IFNULL(SUM(bayarkartu), 0) as KARTU,
                    IFNULL(SUM(ambilkartu), 0) as KREDIT,
                    IFNULL(SUM(epayment), 0) as EPAYMENT,
                    IFNULL(SUM(voucher), 0) as VOUCHER, 
                    IFNULL(SUM(discount+discount2), 0) as DISCOUNT,
                    IFNULL(SUM(pajak+pajak2), 0) as PPN')
                ->where('KODESESI', $SESIJUAL)
                ->first();
            if ($totPenjualan) {
                $sesiJual = SesiJual::where('SESIJUAL', $SESIJUAL)
                    ->limit(1)
                    ->first();
                if ($sesiJual) {
                    if ($totPenjualan->TOTALJUAL > 0) {
                        $FAKTURJURNAL = $sesiJual->NOMORGL;
                        $nTotalTunai = DB::table('jurnal_uangpecahan')
                            ->where('FAKTUR', $SESIJUAL)
                            ->sum(DB::raw('NOMINAL * QTY'));
                        // if (empty($FAKTURJURNAL)) $FAKTURJURNAL = GetterSetter::getLastFakturKasir('KKM', true, Carbon::now(), '101');
                        if (empty($FAKTURJURNAL))
                            $FAKTURJURNAL = GetterSetter::getLastFaktur('KKM', true);
                        $data = [
                            "TOTALJUAL" => round($totPenjualan['TOTALJUAL']),
                            "TOTALTUNAI" => round($request->JUMLAHUANGFISIK),
                            "KARTU" => round($totPenjualan['KARTU']),
                            "KREDIT" => round($totPenjualan['KREDIT']),
                            "EPAYMENT" => round($totPenjualan['EPAYMENT']),
                            "VOUCHER" => round($totPenjualan['VOUCHER']),
                            "DISCOUNT" => round($totPenjualan['DISCOUNT']),
                            "PPN" => round($totPenjualan['PPN']),
                            "PROSES" => "P",
                            "NOMORGL" => $FAKTURJURNAL,
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
            $data = [
                "POSTING" => "1"
            ];
            TotPenjualan::where('KODESESI', $SESIJUAL)->update($data);
            Upd::UpdRekeningKasir($SESIJUAL);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil menyimpan data',
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
            $totPenjualan = TotPenjualan::where('KODESESI', $sesiJual)->get();
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
                $totTunai += $penjualan->TUNAI;
                $totTotal += $penjualan->TOTAL;
                $totEpayment += $penjualan->EPAYMENT;
                $totDebit += $penjualan->BAYARKARTU;
                $totDisc += $penjualan->DISCOUNT1;
                $totPPN += $penjualan->PAJAK2;
                $totDonasi += $penjualan->DONASI;
                $kasir = $penjualan->USERNAME;
                // Tambahkan logika untuk menghitung total lainnya sesuai kebutuhan
            }

            // Buat array total
            $total = [
                'totTunai' => $totTunai,
                'totTotal' => $totTotal,
                'totEpayment' => $totEpayment,
                'totDebit' => $totDebit,
                'totDisc' => $totDisc,
                'totPPN' => $totPPN,
                'totDonasi' => $totDonasi,
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
