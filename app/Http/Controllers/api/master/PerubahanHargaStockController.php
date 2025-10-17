<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\PerubahanHargaStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PerubahanHargaStockController extends Controller
{
    public function store(Request $request)
    {
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [
                'KODE' => 'required|max:20',
                'TANGGAL_PERUBAHAN' => 'date',
                'DISCOUNT' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                'HB' => 'numeric|min:0',
                // 'HB2' => 'numeric|min:0',
                // 'HB3' => 'numeric|min:0',
                'HJ' => 'numeric|min:0',
                // 'HJ2' => 'numeric|min:0',
                // 'HJ3' => 'numeric|min:0',
                // 'HJ_TINGKAT1' => 'numeric|min:0',
                // 'MIN_TINGKAT1' => 'numeric|min:0',
                // 'HJ_TINGKAT2' => 'numeric|min:0',
                // 'MIN_TINGKAT2' => 'numeric|min:0',
                // 'HJ_TINGKAT3' => 'numeric|min:0',
                // 'MIN_TINGKAT3' => 'numeric|min:0',
                // 'HJ_TINGKAT4' => 'numeric|min:0',
                // 'MIN_TINGKAT4' => 'numeric|min:0',
                // 'HJ_TINGKAT5' => 'numeric|min:0',
                // 'MIN_TINGKAT5' => 'numeric|min:0',
                // 'HJ_TINGKAT6' => 'numeric|min:0',
                // 'MIN_TINGKAT6' => 'numeric|min:0',
                // 'HJ_TINGKAT7' => 'numeric|min:0',
                // 'MIN_TINGKAT7' => 'numeric|digits_between:1,16'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom ::attribute tidak boleh lebih dari :max karakter.',
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

            $perubahanHargaStock = PerubahanHargaStock::create([
                'KODE' => $request->KODE,
                'TANGGAL_PERUBAHAN' => $request->TANGGAL_PERUBAHAN,
                'DISCOUNT' => $request->DISCOUNT,
                'PAJAK' => $request->PAJAK,
                'HBLAMA' => $request->HBLAMA,
                'HJLAMA' => $request->HJLAMA,
                'HB' => $request->HB,
                'HJ' => $request->HJ,
                'KETERANGAN' => "Dari Menu Perubahan Harga",
                'DATETIME' => Carbon::now()
                // 'HB2' => $request->HB2,
                // 'HB3' => $request->HB3,
                // 'HJ2' => $request->HJ2,
                // 'HJ3' => $request->HJ3,
                // 'HJ_TINGKAT1' => $request->HJ_TINGKAT1,
                // 'MIN_TINGKAT1' => $request->MIN_TINGKAT1,
                // 'HJ_TINGKAT2' => $request->HJ_TINGKAT2,
                // 'MIN_TINGKAT2' => $request->MIN_TINGKAT2,
                // 'HJ_TINGKAT3' => $request->HJ_TINGKAT3,
                // 'MIN_TINGKAT3' => $request->MIN_TINGKAT3,
                // 'HJ_TINGKAT4' => $request->HJ_TINGKAT4,
                // 'MIN_TINGKAT4' => $request->MIN_TINGKAT4,
                // 'HJ_TINGKAT5' => $request->HJ_TINGKAT5,
                // 'MIN_TINGKAT5' => $request->MIN_TINGKAT5,
                // 'HJ_TINGKAT6' => $request->HJ_TINGKAT6,
                // 'MIN_TINGKAT6' => $request->MIN_TINGKAT6,
                // 'HJ_TINGKAT7' => $request->HJ_TINGKAT7,
                // 'MIN_TINGKAT7' => $request->MIN_TINGKAT7,
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
}
