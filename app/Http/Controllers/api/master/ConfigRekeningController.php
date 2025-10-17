<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\GetterSetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ConfigRekeningController extends Controller
{

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $vaValidator = Validator::make($request->all(), [
                'kode' => 'required|array',
                'keterangan' => 'required|array',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kode sudah ada di database.',
                'array' => ':attribute harus berupa array'

            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            if (count($request->kode) !== count($request->keterangan)) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => 'Jumlah data kode dan keterangan tidak sama.',
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            foreach ($request->kode as $index => $kode) {
                $keterangan = $request->keterangan[$index] ?? null;

                $existingData = DB::table('config')->where('kode', $kode)->first();

                if ($existingData && $existingData->keterangan === $keterangan) {
                    continue;
                }

                $result = DB::table('config')->updateOrInsert(
                    ['kode' => $kode],
                    [
                        'kode' => $kode,
                        'keterangan' => $keterangan
                    ]
                );

                if (!$result) {
                    throw new \Exception('Insert/Update gagal untuk kode ' . $kode);
                }
            }

            DB::commit();
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

    public function data(Request $request)
    {
        try {
            $vaData = DB::table('config as c')
                ->select('c.kode', 'c.keterangan as rekening', 'r.keterangan')
                ->leftJoin('rekening as r', 'r.kode', '=', 'c.keterangan')
                ->whereIn('c.kode', ['rek_sewa', 'rek_booking', 'rek_diskon', 'rek_ppn', 'rek_aset', 'rek_kewajiban', 'rek_modal', 'rek_pendapatan', 'rek_biaya'])
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->kode => [
                        'rekening' => $item->rekening,
                        'keterangan' => $item->keterangan
                    ]];
                });

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'data' => $vaData,
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
