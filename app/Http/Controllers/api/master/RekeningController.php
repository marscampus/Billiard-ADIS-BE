<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RekeningController extends Controller
{
    public function data(Request $request)
    {
        try {
            // Ambil data dari tabel 'rekening'
            $vaData = DB::table('rekening')
                ->select('id', 'kode', 'jenis as jenis_rekening', 'keterangan')
                ->orderBy('id', 'ASC')
                ->get();

            // Tambahkan field 'tipe_rekening' berdasarkan kode
            $vaData->transform(function ($item) {
                $kodeAwal = substr($item->kode, 0, 2); // Ambil dua karakter pertama dari kode

                // Tentukan tipe rekening berdasarkan kode
                switch ($kodeAwal) {
                    case '1.':
                        $item->tipe_rekening = 'Aset';
                        break;
                    case '2.':
                        $item->tipe_rekening = 'Kewajiban';
                        break;
                    case '3.':
                        $item->tipe_rekening = 'Modal';
                        break;
                    case '4.':
                        $item->tipe_rekening = 'Pendapatan';
                        break;
                    case '5.':
                        $item->tipe_rekening = 'Biaya';
                        break;
                    case '6.':
                        $item->tipe_rekening = 'Administrasi';
                        break;
                    default:
                        $item->tipe_rekening = 'Kategori Tidak Dikenal';
                        break;
                }
                return $item;
            });

            // Kelompokkan data berdasarkan 'tipe_rekening'
            $groupedData = $vaData->groupBy('tipe_rekening')->map(function ($items, $tipeRekening) {
                return [
                    'tipe_rekening' => $tipeRekening,
                    'detail' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'kode' => $item->kode,
                            'jenis_rekening' => $item->jenis_rekening,
                            'keterangan' => $item->keterangan,
                        ];
                    })->values()->toArray()
                ];
            })->values()->toArray();

            // Kembalikan response JSON dengan data yang telah dikelompokkan
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $groupedData,
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

    public function getAll(Request $request)
    {
        try {
            // Ambil data dari tabel 'rekening'
            $vaData = DB::table(table: 'rekening')
                ->select('id as ID', 'kode as KODE', 'jenis as JENISREKENING', 'keterangan as KETERANGAN')
                ->orderBy('id', 'ASC')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaData,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => "01",
                'message' => 'Terjadi Kesalahan Saat Proses Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'rekening' => 'required|string|unique:rekening,kode|max:20',
                'keterangan' => 'required|max:50',
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
            $vaData = DB::table('rekening')->insert([
                'kode' => $request->rekening,
                'keterangan' => $request->keterangan,
                'jenis' => $request->jenis_rekening
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
                'kode' => 'required|string|max:20',
                'keterangan' => 'required|max:50',
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
            $vaData = DB::table('rekening')->where('kode', '=', $request->kode)->update([
                'keterangan' => $request->keterangan,
                'jenis' => $request->jenis_rekening
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
            $vaData = DB::table('rekening')->where('id', $request->id)->delete();

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
