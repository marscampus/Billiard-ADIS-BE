<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\JenisSupplier;
use App\Models\master\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DaftarSupplierController extends Controller
{

    function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $nLimit = 10;
            $vaData = DB::table('supplier')
                ->select(
                    'KODE',
                    'NAMA',
                    'ALAMAT',
                    'TELEPON'
                );
            // if (!empty($vaRequestData['filters'])) {
            //     foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
            //         $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
            //     }
            // }
            $vaData = $vaData->orderBy('KODE', 'ASC');
            $vaData = $vaData->get();
            // if ($vaRequestData['page'] === null) {
            // } else {
            //     $vaData = $vaData->paginate($nLimit);
            // }
            // JIKA REQUEST SUKSES
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
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [
                'KODE' => 'required|max:4|unique:supplier,KODE',
                'NAMA' => 'max:40',
                'ALAMAT' => 'max:255',
                'TELEPON' => 'max:30',
                'KOTA' => 'max:20',
                'JENIS_USAHA' => 'max:4',
                'REKENING' => 'max:11',
                'NAMA_CP_1' => 'max:40',
                'ALAMAT_CP_1' => 'max:50',
                'TELEPON_CP_1' => 'max:30',
                'HP_CP_1' => 'max:30',
                'EMAIL_CP_1' => 'email|max:50',
                'PLAFOND_1' => 'numeric|min:0',
                'PLAFOND_2' => 'numeric|digits_between:1,16'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh lebih dari :min karakter.',
                'email' => 'Kolom :attribute harus email yang valid.',
                'numeric' => 'Kolom :attribute harus angka yang valid.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'digits_between' => 'Kolom :attribute harus berada di antar 1 sampai 16.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $kode = $request->KODE;
            $nama = $request->NAMA;
            $alamat = $request->ALAMAT;
            $telepon = $request->TELEPON;
            $kota = $request->KOTA;
            $jenisUsaha = $request->JENIS_USAHA;
            $rekening = $request->REKENING;
            $namaCP1 = $request->NAMA_CP_1;
            $alamatCP1 = $request->ALAMAT_CP_1;
            $teleponCP1 = $request->TELEPON_CP_1;
            $hpCP1 = $request->HP_CP_1;
            $emailCP1 = $request->EMAIL_CP_1;
            $namaCP2 = $request->NAMA_CP_2;
            $alamatCP2 = $request->ALAMAT_CP_2;
            $teleponCP2 = $request->TELEPON_CP_2;
            $hpCP2 = $request->HP_CP_2;
            $emailCP2 = $request->EMAIL_CP_2;
            $plafond1 = $request->PLAFOND_1;
            $plafond2 = $request->PLAFOND_2;
            $supplier = Supplier::create([
                'KODE' => $kode,
                'NAMA' => $nama,
                'ALAMAT' => $alamat,
                'TELEPON' => $telepon,
                'KOTA' => $kota,
                'JENIS_USAHA' => $jenisUsaha,
                'REKENING' => $rekening,
                'NAMA_CP_1' => $namaCP1,
                'ALAMAT_CP_1' => $alamatCP1,
                'TELEPON_CP_1' => $teleponCP1,
                'HP_CP_1' => $hpCP1,
                'EMAIL_CP_1' => $emailCP1,
                // 'NAMA_CP_2' => $namaCP2,
                // 'ALAMAT_CP_2' => $alamatCP2,
                // 'TELEPON_CP_2' => $teleponCP2,
                // 'HP_CP_2' => $hpCP2,
                // 'EMAIL_CP_2' => $emailCP2,
                'PLAFOND_1' => $plafond1,
                'PLAFOND_2' => $plafond2
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
                'NAMA' => 'max:40',
                'ALAMAT' => 'max:255',
                'TELEPON' => 'max:30',
                'KOTA' => 'max:20',
                'JENIS_USAHA' => 'max:4',
                'REKENING' => 'max:11',
                'NAMA_CP_1' => 'max:40',
                'ALAMAT_CP_1' => 'max:50',
                'TELEPON_CP_1' => 'max:30',
                'HP_CP_1' => 'max:30',
                'EMAIL_CP_1' => 'email|max:50',
                'PLAFOND_1' => 'numeric|min:0',
                'PLAFOND_2' => 'numeric|digits_between:1,16'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh lebih dari :min karakter.',
                'email' => 'Kolom :attribute harus email yang valid.',
                'numeric' => 'Kolom :attribute harus angka yang valid.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'digits_between' => 'Kolom :attribute harus berada di antar 1 sampai 16.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $KODE = $request->KODE;
            $supplier = Supplier::where('KODE', $KODE)->update([
                'NAMA' => $request->NAMA,
                'ALAMAT' => $request->ALAMAT,
                'TELEPON' => $request->TELEPON,
                'KOTA' => $request->KOTA,
                'JENIS_USAHA' => $request->JENIS_USAHA,
                'REKENING' => $request->REKENING,
                'NAMA_CP_1' => $request->NAMA_CP1,
                'ALAMAT_CP_1' => $request->ALAMAT_CP_1,
                'TELEPON_CP_1' => $request->TELEPON_CP_1,
                'HP_CP_1' => $request->HP_CP_1,
                'EMAIL_CP_1' => $request->EMAIL_CP_1,
                'NAMA_CP_2' => $request->NAMA_CP_2,
                'ALAMAT_CP_2' => $request->ALAMAT_CP_2,
                'TELEPON_CP_2' => $request->TELEPON_CP_2,
                'HP_CP_2' => $request->HP_CP_2,
                'EMAIL_CP_2' => $request->EMAIL_CP_2,
                'PLAFOND_1' => $request->PLAFOND_1,
                'PLAFOND_2' => $request->PLAFOND_2
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
            $supplier = Supplier::findOrFail($request->KODE);
            $supplier->delete();
            // return response()->json(['status' => 'success']);
            if ($supplier) {
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
