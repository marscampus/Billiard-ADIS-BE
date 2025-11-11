<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KamarController extends Controller
{
    public function data(Request $request)
    {
        try {
            // Ambil data kamar
            $vaData = DB::table('kamar as k')
                ->select(
                    'k.id',
                    'k.kode_kamar',
                    'k.no_kamar',
                    'k.tipe_kamar',
                    'k.harga',
                    'k.fasilitas',
                    'k.foto',
                    't.keterangan as ket_tipe',
                    'k.per_harga'
                )
                ->leftJoin('tipe_kamar as t', 't.kode', '=', 'k.tipe_kamar')
                ->orderByDesc('k.id')
                ->get()
                ->map(function ($item) {
                    // Pecahkan fasilitas menjadi array
                    $fasilitasCodes = explode('|', $item->fasilitas);

                    // Ambil keterangan fasilitas dari database
                    $fasilitasKeterangan = DB::table('fasilitas_kamar')
                        ->whereIn('kode', $fasilitasCodes)
                        ->pluck('keterangan')
                        ->toArray();

                    // Gabungkan keterangan kembali dengan separator '|'
                    $item->ket_fasilitas = implode('|', $fasilitasKeterangan);

                    return $item;
                });

            // Periksa apakah data kosong
            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Tidak Ada Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            // Ubah fasilitas menjadi array untuk setiap item
            $vaData = $vaData->map(function ($item) {
                $item->fasilitas = explode('|', $item->fasilitas);
                return $item;
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
                'kode_kamar' => 'required|string|unique:kamar,kode_kamar|max:6',
                'no_kamar' => 'required|string|unique:kamar,no_kamar',
                'harga' => 'required|numeric|min:1',
                'tipe' => 'required|string',
                'fasilitas' => 'required|array',
                'foto' => 'nullable|string'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'array' => 'Kolom :attribute harus berupa array.',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $cKodeKamar = GetterSetter::getKodeKamar('R', 6);
            $vaKey = $request->fasilitas; // Ambil array fasilitas langsung

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

                $fileName =  $cKodeKamar . '.' . $ext;

                Storage::disk('minio')->put('images/meja' . $fileName, $fotoData);
            }

            $vaData = DB::table('kamar')->insert([
                'kode_kamar' => $cKodeKamar,
                'no_kamar' => $request->no_kamar,
                'harga' => $request->harga,
                'foto' => $fileName,
                'fasilitas' => implode('|', $vaKey), // Gabungkan fasilitas menjadi string
                'tipe_kamar' => $request->tipe,
                'per_harga' => $request->per_harga,
                'status' => '0'
            ]);
            GetterSetter::setKodeKamar('R');

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
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'kode_kamar' => 'required|string|max:6',
                'no_kamar' => 'required|string',
                'harga' => 'required|numeric|min:1',
                'tipe' => 'required|string',
                'fasilitas' => 'required|array',
                'foto' => 'nullable|string'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'array' => 'Kolom :attribute harus berupa array.',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $vaKey = $request->fasilitas; // Ambil array fasilitas langsung

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

                $fileName =  $request->no_kamar . '.' . $ext;

                Storage::disk('minio')->put('images/meja' . $fileName, $fotoData);
            }

            $vaData = DB::table('kamar')->where('kode_kamar', '=', $request->kode_kamar)->update([
                'no_kamar' => $request->no_kamar,
                'harga' => $request->harga,
                'foto' => $fileName,
                'fasilitas' => implode('|', $vaKey),
                'tipe_kamar' => $request->tipe,
                'per_harga' => $request->per_harga,
                'status' => '0'
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Update Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            $vaData = DB::table('kamar')->where('id', $request->id)->delete();

            if ($vaData === 0) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Hapus Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            if (!empty($vaData->foto)) {
                try {
                    if (Storage::disk('minio')->exists('images/meja' . $vaData->foto)) {
                        Storage::disk('minio')->delete('images/meja' . $vaData->foto);
                    }
                } catch (\Throwable $e) {
                    // Log saja jika gagal hapus file, tapi tetap lanjut hapus data
                    \Log::warning('Gagal hapus foto dari MinIO: ' . $e->getMessage());
                }
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

    public function getDataKamar(Request $request)
    {
        $tgl = $request->tgl_checkin;
        try {
            $vaValidator = Validator::make($request->all(), [
                'tgl_checkin' => 'required|date'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'array' => 'Kolom :attribute harus berupa array.',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $vaData = DB::table('kamar as k')
                ->select(
                    'k.id',
                    'k.kode_kamar',
                    'k.no_kamar',
                    'k.tipe_kamar',
                    'k.harga',
                    'k.fasilitas',
                    't.keterangan as ket_tipe',
                    'r.tgl_checkin',
                    'r.tgl_checkout',
                    DB::raw("CASE
                                WHEN '$tgl' BETWEEN r.tgl_checkin AND r.tgl_checkout THEN 'Direservasi'
                                ELSE 'Tersedia'
                             END as status"), // Tambahkan kolom status
                )
                ->leftJoin('tipe_kamar as t', 't.kode', '=', 'k.tipe_kamar')
                ->leftJoin('reservasi as r', 'r.no_kamar', '=', 'k.no_kamar')
                ->orderByDesc('k.id')
                ->get()
                ->map(function ($item) {
                    if (!empty($item->foto)) {
                        $fileKey = 'images/meja/' . $item->foto;
                        $foto = Storage::disk('minio')->get($fileKey);
                        $base64 = base64_encode($foto);
                        $item->foto_url = 'data:image/jpeg;base64,' . $base64;
                    } else {
                        $item->foto_url = null;
                    }

                    // Pecahkan fasilitas menjadi array
                    $fasilitasCodes = explode('|', $item->fasilitas);

                    // Ambil keterangan fasilitas dari database
                    $fasilitasKeterangan = DB::table('fasilitas_kamar')
                        ->whereIn('kode', $fasilitasCodes)
                        ->pluck('keterangan')
                        ->toArray();

                    // Gabungkan keterangan kembali dengan separator '|'
                    $item->ket_fasilitas = implode('|', $fasilitasKeterangan);

                    return $item;
                });
            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Tidak Ada Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            } else {
                return $vaData;
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
