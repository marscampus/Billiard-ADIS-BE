<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FasilitasKamarController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaData = DB::table('fasilitas_kamar')
                ->select('id', 'kode', 'keterangan', 'deskripsi', 'foto')
                ->orderByDesc('id')
                ->get();

            $vaData->map(function ($item) {
                if (!empty($item->foto)) {
                    $fileKey = 'images/fasilitas-meja/' . $item->foto;
                    $foto = Storage::disk('minio')->get($fileKey);
                    $base64 = base64_encode($foto);
                    $item->foto = 'data:image/jpeg;base64,' . $base64;
                } else {
                    $item->foto = null;
                }
            });

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
                'kode' => 'required|string|unique:fasilitas_kamar,kode|max:6',
                'keterangan' => 'required|max:50',
                'foto' => 'nullable|string'
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

            $fotoUrl = null;
            if (!empty($request->foto)) {
                $fotoData = $request->foto;

                if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
                    $fotoData = substr($fotoData, strpos($fotoData, ',') + 1);
                    $ext = strtolower($type[1]);
                } else {
                    $ext = 'jpg';
                }

                $fotoData = base64_decode($fotoData);
                if ($fotoData === false) {
                    throw new \Exception('Invalid base64 image data');
                }

                $fileName =  $request->kode . '.' . $ext;

                Storage::disk('minio')->put('images/fasilitas-meja/' . $fileName, $fotoData, 'public');
            }

            $vaData = DB::table('fasilitas_kamar')->insert([
                'kode' => $request->kode,
                'keterangan' => $request->keterangan,
                'deskripsi' => $request->deskripsi,
                'foto' => $fileName ?? null
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

            $fotoUrl = null;
            if (!empty($request->foto)) {
                $fotoData = $request->foto;

                if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
                    $fotoData = substr($fotoData, strpos($fotoData, ',') + 1);
                    $ext = strtolower($type[1]);
                } else {
                    $ext = 'jpg';
                }

                $fotoData = base64_decode($fotoData);
                if ($fotoData === false) {
                    throw new \Exception('Invalid base64 image data');
                }

                $fileName =  $request->kode . '.' . $ext;

                Storage::disk('minio')->put('images/fasilitas-meja/' . $fileName, $fotoData, 'public');
            }

            $vaData = DB::table('fasilitas_kamar')->where('kode', '=', $request->kode)->update([
                'keterangan' => $request->keterangan,
                'deskripsi' => $request->deskripsi,
                'foto' => $fileName ?? null
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
            $vaData = DB::table('fasilitas_kamar')->where('id', $request->id)->delete();

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
