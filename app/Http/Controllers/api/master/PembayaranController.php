<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PembayaranController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaData = DB::table('pembayaran as p')
                ->select('p.id', 'p.kode', 'p.keterangan', 'p.rekening as kode_rekening', 'r.keterangan as ket_kode_rekening')
                ->leftJoin('rekening as r', 'r.kode', '=', 'p.rekening')
                ->orderByDesc('id')
                ->get();

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

    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'kode' => 'required|string|unique:pembayaran,kode|max:6',
                'keterangan' => 'required|max:50',
                'kode_rekening' => 'required|max:20'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $vaData = DB::table('pembayaran')->insert([
                'kode' => $request->kode,
                'keterangan' => $request->keterangan,
                'rekening' => $request->kode_rekening
            ]);

            if (!$vaData) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Create Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'kode' => 'required|string|max:6',
                'keterangan' => 'required|max:50',
                'kode_rekening' => 'required|max:20'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $vaData = DB::table('pembayaran')->where('kode', '=', $request->kode)->update([
                'keterangan' => $request->keterangan,
                'rekening' => $request->kode_rekening
            ]);


            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Update Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            $vaData = DB::table('pembayaran')->where('id', $request->id)->delete();

            if ($vaData === 0) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Hapus Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Hapus Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
