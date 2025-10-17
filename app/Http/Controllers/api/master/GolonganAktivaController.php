<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GolonganAktivaController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaData = DB::table('golonganaktiva as g')
                ->select(
                    'g.Kode',
                    'g.Keterangan',
                    'g.RekeningDebet',
                    'g.RekeningKredit',
                    'r1.Keterangan as KetRekeningDebet',
                    'r2.Keterangan as KetRekeningKredit'
                )
                ->leftJoin('rekening as r1', 'r1.Kode', '=', 'g.RekeningDebet')
                ->leftJoin('rekening as r2', 'r2.Kode', '=', 'g.RekeningKredit')
                ->orderBy('g.Kode', 'ASC')
                ->groupBy('g.Kode')
                ->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaData,
                'total_data' => count($vaData),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $cUser = Func::dataAuth($request);
            $vaValidator = validator::make($request->all(), [
                'Kode' => 'required|max:4|unique:golonganaktiva,kode',
                'Keterangan' => 'required|max:50',
                'RekeningDebet' => 'max:20',
                'RekeningKredit' => 'max:20'
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
            $vaArray = [
                'Kode' => $request['Kode'],
                'Keterangan' => $request['Keterangan'],
                'RekeningDebet' => $request['RekeningDebet'],
                'RekeningKredit' => $request['RekeningKredit']
            ];
            DB::table('golonganaktiva')->insert($vaArray);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data Berhasil Ditambahkan',
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
            $cUser = Func::dataAuth($request);
            $vaValidator = Validator::make($request->all(), [
                'Kode' => 'required|max:4',
                'Keterangan' => 'required|max:50',
                'RekeningDebet' => 'max:20',
                'RekeningKredit' => 'max:20'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $cKode = $request['Kode'];
            $vaData = DB::table('golonganaktiva')
                ->where('Kode', '=', $cKode)
                ->exists();
            if ($vaData) {
                $vaArray = [
                    'Keterangan' => $request->Keterangan,
                    'RekeningDebet' => $request->RekeningDebet,
                    'RekeningKredit' => $request->RekeningKredit
                ];
                DB::table('golonganaktiva')->where('Kode', '=', $cKode)->update($vaArray);
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Data Berhasil Ditambahkan',
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }
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
            $vaValidator = Validator::make($request->all(), [
                'Kode' => 'required'
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
            DB::table('golonganaktiva')->where('Kode', '=', $request['Kode'])->delete();
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
            ], 500);
        }
    }
}
