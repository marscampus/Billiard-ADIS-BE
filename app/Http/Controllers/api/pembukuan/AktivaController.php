<?php

namespace App\Http\Controllers\api\pembukuan;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AktivaController extends Controller
{
    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        unset($vaRequestData['auth']);
        $nLimit = 10;
        try {
            $vaData = DB::table('aktiva')
                ->select(
                    'Kode',
                    'Nama',
                    'TglPerolehan',
                    'TglPenyusutan',
                    'Unit',
                    'Golongan',
                    'JenisPenyusutan',
                    'Lama',
                    'TarifPenyusutan',
                    'HargaPerolehan',
                    'Residu',
                    'JenisPenyusutan'
                );
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", "%" . $filterValue . "%");
                }
            }
            $vaData = $vaData->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $vaData,
                'total' => count($vaData),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function store(Request $request)
    {
        $Kode = $request->Kode;
        $Nama = $request->Nama;
        $TglPerolehan = $request->TglPerolehan;
        $TglPenyusutan = $request->TglPenyusutan;
        $TarifPenyusutan = $request->TarifPenyusutan;
        $HargaPerolehan = $request->HargaPerolehan;
        $Unit = $request->Unit;
        $Golongan = $request->Golongan;
        $JenisPenyusutan = $request->JenisPenyusutan;
        $Lama = $request->Lama;
        $Residu = $request->Residu;
        try {

            $vaValidator = Validator::make($request->all(), [
                'Kode' => 'required|max:4|unique:aktiva,kode',
                'Golongan' => 'required|max:50',
                'Nama' => 'required|max:100',
                'JenisPenyusutan' => 'required',
                'Unit' => 'required|min:1'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }


            $Aktiva = DB::table('aktiva')->insert([
                'Kode' => $Kode,
                'Nama' => $Nama,
                'TglPerolehan' => $TglPerolehan,
                'TglPenyusutan' => $TglPenyusutan,
                'TarifPenyusutan' => $TarifPenyusutan ?? 0,
                'HargaPerolehan' => $HargaPerolehan ?? 0,
                'Unit' => $Unit,
                'Golongan' => $Golongan,
                'JenisPenyusutan' => $JenisPenyusutan,
                'Lama' => $Lama ?? 0,
                'Residu' => $Residu ?? 0
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Simpan Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function update(Request $request)
    {
        try {

            $vaValidator = Validator::make($request->all(), [
                'Kode' => [
                    'required',
                    'max:4',
                    Rule::unique('aktiva', 'Kode')->ignore($request->Kode, 'Kode'),
                ],
                'Golongan' => 'required|max:50',
                'Nama' => 'required|max:100',
                'JenisPenyusutan' => 'required',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $Aktiva = DB::table('aktiva')
                ->where('Kode', $request->Kode)
                ->update([
                    //'Keterangan' => $request->Keterangan,
                    'Nama' => $request->Nama,
                    'TglPerolehan' => $request->TglPerolehan,
                    'TglPenyusutan' => $request->TglPenyusutan,
                    'TarifPenyusutan' => $request->TarifPenyusutan,
                    'HargaPerolehan' => $request->HargaPerolehan,
                    'Unit' => $request->Unit,
                    'Golongan' => $request->Golongan,
                    'JenisPenyusutan' => $request->JenisPenyusutan,
                    'Lama' => $request->Lama,
                    'Residu' => $request->Residu,
                ]);
                
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Simpan Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    function delete(Request $request)
    {
        try {
            $Aktiva = DB::table('aktiva')->where('Kode', '=', $request->Kode)->delete();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Hapus Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
    public function getDataEdit(Request $request)
    {
        $Kode = $request->Kode;
        try {
            $vaData = DB::table('aktiva as a')
                ->select('a.*', 'g.keterangan as GolKeterangan')
                ->where('a.Kode', '=', $Kode)
                ->leftJoin('golonganaktiva as g', 'a.golongan', 'g.kode')
                ->first();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $vaData,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
}
