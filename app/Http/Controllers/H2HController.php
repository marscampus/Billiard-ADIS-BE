<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class H2HController extends Controller
{
    public function getPenjualan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tglAwal'  => ['required', 'date_format:Y-m-d'],
            'tglAkhir' => ['required', 'date_format:Y-m-d', 'after_or_equal:tglAwal'],
        ], [
            'tglAwal.required' => 'Tanggal awal wajib diisi',
            'tglAwal.date_format' => 'Format tanggal awal harus Y-m-d',
            'tglAkhir.required' => 'Tanggal akhir wajib diisi',
            'tglAkhir.date_format' => 'Format tanggal akhir harus Y-m-d',
            'tglAkhir.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => $validator->errors()->first(),
                'datetime' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $startDate = $request->tglAwal;
        $endDate   = $request->tglAkhir;

        $vaPenjualan = DB::table('invoice')
            ->select('tgl', 'kode_invoice', 'kode_reservasi', 'nik', 'nama_tamu', 'no_telepon', 'total_kamar', 'total_harga', 'cara_bayar')
            ->whereDate('tgl', '>=', $startDate)
            ->whereDate('tgl', '<=', $endDate)
            ->get();

        return response()->json([
            'status' => self::$status['SUKSES'],
            'message' => 'Berhasil mengambil data',
            'datetime' => now()->format('Y-m-d H:i:s'),
            'data' => $vaPenjualan
        ], 200);
    }
}
