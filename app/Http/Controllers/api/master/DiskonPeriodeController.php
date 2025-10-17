<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\master\DiskonPeriode;
use App\Models\master\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiskonPeriodeController extends Controller
{
    function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $dTgl = $request->input('Tgl');
            $vaData = DB::table('diskon_periode as d')
                ->select(
                    'd.KODEDISKON',
                    'd.KODE',
                    'd.BARCODE',
                    's.NAMA',
                    'd.TGL_MULAI',
                    'd.TGL_AKHIR',
                    'd.HJ_AWAL',
                    'd.HJ_DISKON',
                    'd.KUOTA_QTY'
                )
                ->leftJoin('stock as s', 's.Kode', '=', 'd.Kode')
                ->where('d.Tgl_Akhir', '<=', $dTgl);
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    if (!empty($filterValue)) {
                        $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                    }
                }
            }
            $vaData->orderByDesc('d.Tgl');
            $vaData = $vaData->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $vaData,
                'total_data' => count($vaData),
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

    function dataCetak(Request $request)
    {
        try {
            // Kondisi untuk 'ACTIVE'
            $today = Carbon::today()->toDateString();
            $diskonPeriode = DiskonPeriode::whereDate('TGL_MULAI', '<=', $today)
                ->whereDate('TGL_AKHIR', '>=', $today)
                ->get();
            $result = $diskonPeriode->map(function ($item) {
                $dataArray = $item->toArray();
                $dataArray['NAMA'] = $item->stock ? $item->stock->NAMA : null;
                return $dataArray;
            });

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $result,
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


    function getDataCetak(Request $request)
    {
        $KODE = $request->KODE;
        try {

            $today = Carbon::today()->toDateString();
            // Mencari detail data berdasarkan KODE dan yang aktif
            $data = DiskonPeriode::where('KODE', $KODE)
                ->whereDate('TGL_MULAI', '<=', $today)
                ->whereDate('TGL_AKHIR', '>=', $today)
                ->first();
            // Jika tidak ditemukan, kembalikan response kosong atau sesuai kebutuhan Anda
            if (!$data) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data Tidak Ditemukan',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);

            }

            // Menyertakan NAMA dari relasi stock
            $dataArray = $data->toArray();
            $dataArray['NAMA'] = $data->stock ? $data->stock->NAMA : null;

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => [$dataArray],
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

    function store(Request $request)
    {
        $kodeDiskon = GetterSetter::getLastFaktur('PROMO', 4);
        // dd($kodeDiskon);
        try {
            $dataDiskonPeriode = DiskonPeriode::create([
                'KODEDISKON' => $kodeDiskon,
                'KODE' => $request->KODE,
                'BARCODE' => $request->BARCODE,
                'TGL' => Carbon::now()->format('Y-m-d'),
                'TGL_MULAI' => $request->TGL_MULAI,
                'TGL_AKHIR' => $request->TGL_AKHIR,
                'KUOTA_QTY' => $request->KUOTA_QTY,
                'HJ_AWAL' => $request->HJ_AWAL,
                'HJ_DISKON' => $request->HJ_DISKON,
            ]);

            GetterSetter::setLastFaktur('PROMO');
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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
    function update(Request $request)
    {
        // dd($request->all());
        try {
            $KODE = $request->KODEDISKON;
            $dataDiskonPeriode = DiskonPeriode::where('KODEDISKON', $KODE)->update([
                'KODEDISKON' => $KODE,
                'KODE' => $request->KODE,
                'BARCODE' => $request->BARCODE,
                'TGL' => Carbon::now()->format('Y-m-d'),
                'TGL_MULAI' => $request->TGL_MULAI,
                'TGL_AKHIR' => $request->TGL_AKHIR,
                'KUOTA_QTY' => $request->KUOTA_QTY,
                'HJ_AWAL' => $request->HJ_AWAL,
                'HJ_DISKON' => $request->HJ_DISKON,

            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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
    function delete(Request $request)
    {
        try {
            $diskonPeriode = DiskonPeriode::where('KODEDISKON', $request->KODEDISKON);
            $diskonPeriode->delete();
            // return response()->json(['status' => 'success']);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menghapus Data',
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
    function print(Request $request)
    {
        $KET = $request->input('KET');
        $KODE = $request->input('KODE');
        try {
            if ($KODE) {
                $query = DiskonPeriode::where('KODE', $KODE);
            } else {
                $query = DiskonPeriode::query();
            }
            if ($KET === 'ALL') {
                $diskonPeriode = $query->get();
            } elseif ($KET === 'ACTIVE') {
                $today = Carbon::today()->toDateString();
                $diskonPeriode = $query->whereDate('TGL_MULAI', '<=', $today)
                    ->whereDate('TGL_AKHIR', '>=', $today)
                    ->get();
            } else {
                // Jika input tidak sesuai, kembalikan response kosong atau sesuai kebutuhan Anda
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data tidak ditemukan',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $diskonPeriode,
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
