<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuStock;
use App\Models\penjualan\Penjualan;
use App\Models\penjualan\TotPenjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturController extends Controller
{
    public function getFaktur(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cKodeSesi = $vaRequestData['KODESESI'];
            $vaData = DB::table('totpenjualan as tp')
                ->select(
                    'tp.FAKTUR',
                    'tp.TGL',
                    'tp.DISCOUNT',
                    'tp.PAJAK',
                    'tp.TOTAL',
                    'tp.CARABAYAR',
                    'tp.TUNAI',
                    'tp.EPAYMENT',
                    'tp.BAYARKARTU',
                    'u.email AS USERNAME'
                )
                ->leftJoin('users as u', 'u.email', '=', 'tp.UserName')
                ->where('tp.KodeSesi', '=', $cKodeSesi)
                ->where('tp.KODESESI_RETUR', '=', '');
            // Jika terdapat filters dalam request
            if (!empty($request->filters)) {
                foreach ($request->filters as $k => $v) {
                    // Menambahkan kondisi WHERE dengan menggunakan RIGHT dan LIKE pada kolom-kolom yang sesuai
                    $vaData->whereRaw("$k LIKE ?", ['%' . $v . '%']);
                }
            }

            $vaData = $vaData->get();
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaData,
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

    public function cariFaktur(Request $request)   // --------------------< Detail by FAKTUR >
    {
        try {
            // Cari FAKTUR yang sama dalam model TotPenjualan dan KartuStock
            $faktur = $request->FAKTUR;
            $totPenjualan = TotPenjualan::where('FAKTUR', $faktur)->first();
            $penjualan = Penjualan::where('FAKTUR', $faktur)->get();
            $kartuStock = KartuStock::where('FAKTUR', $faktur)->first();
            // ------ Ambil NAMA dari Penjualan
            $NAMA = [];
            foreach ($penjualan as $item) {
                $NAMA[] = $item->stock->NAMA;
            }
            foreach ($penjualan as $key => $item) {
                $item->NAMA = $NAMA[$key];
            }
            // dd($penjualan);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'totPenjualan' => $totPenjualan,
                'penjualan' => $penjualan,
                'kartuStock' => $kartuStock,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        try {
            $cFaktur = $vaRequestData['Faktur'];
            $cKodeSesiRetur = $vaRequestData['KodeSesiRetur'];
            $vaExistsTotPenjualan = DB::table('totpenjualan')
                ->where('Faktur', '=', $cFaktur)
                ->exists();
            if ($vaExistsTotPenjualan) {
                $vaArray = [
                    'KODESESI_RETUR' => $cKodeSesiRetur,
                    'STATUS_RETUR' => '1'
                ];
                TotPenjualan::where('Faktur', '=', $cFaktur)->update($vaArray);
            }
            Upd::updKartuStockReturPenjualan($cFaktur);
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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
}
