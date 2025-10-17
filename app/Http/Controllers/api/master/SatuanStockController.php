<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\SatuanStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SatuanStockController extends Controller
{

    function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            // dd($vaRequestData);
            $nLimit = 10;
            $vaData = DB::table('satuanstock as r')
                ->select(
                    'r.KODE',
                    'r.KETERANGAN'
                );
            // if (!empty($vaRequestData['filters'])) {
            //     foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
            //         if (!empty($filterValue)) {
            //             $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
            //         }
            //     }
            // }
            $vaData = $vaData->orderBy('KODE', 'ASC');
            $vaData = $vaData->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
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
    function store(Request $request)
    {
        $messages = config('validate.validation');
        $vaValidator = Validator::make($request->all(), [
            'KODE' => 'required|max:4|unique:satuanstock,KODE',
            'KETERANGAN' => 'required|max:50',
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
        $kode = $request->KODE;
        $keterangan = $request->KETERANGAN;
        try {
            $satuanStock = SatuanStock::create([
                'KODE' => $kode,
                'KETERANGAN' => $keterangan
            ]);
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

    function update(Request $request)
    {
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [
                'KODE' => 'required|max:4',
                'KETERANGAN' => 'required|max:50',
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
            $KODE = $request->KODE;
            $satuanStock = SatuanStock::where('KODE', $KODE)->update([
                'KETERANGAN' => $request->KETERANGAN
            ]);

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

    function delete(Request $request)
    {
        try {
            $satuanStock = SatuanStock::findOrFail($request->KODE);
            $satuanStock->delete();
            // return response()->json(['status' => 'success']);
            if ($satuanStock) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Data berhasil dihapus',
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data gagal Dihapus',
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
