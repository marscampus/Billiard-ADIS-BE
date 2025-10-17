<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AntrianMejaController extends Controller
{
    public function data(Request $request)
    {
        try {
            // Ambil data kamar
            $vaData = DB::table('kamar as k')
                ->select(
                    'k.id',
                    'k.kode_kamar AS kode_meja',
                    'k.no_kamar AS no_meja',
                    'k.harga',
                    'k.foto',
                    'k.status'
                )
                ->get();

            // Periksa apakah data kosong
            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Tidak Ada Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaData,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'kode_meja' => 'required|string|exists:kamar,kode_kamar',
                'status' => 'required|integer|max:20'
            ]);

            $updated = DB::table('kamar')
                ->where('kode_kamar', $request->kode_meja)
                ->update(['status' => $request->status]);

            if (!$updated) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal memperbarui status',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Status berhasil diperbarui',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => $e->errors(),
                'datetime' => date('Y-m-d H:i:s')
            ], 422);
        } catch(\Throwable $th){
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Memperbarui Status',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
