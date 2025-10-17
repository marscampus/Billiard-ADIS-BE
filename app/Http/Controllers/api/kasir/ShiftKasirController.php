<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\ApiResponse;
use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\Shift;
use App\Models\fun\Username;
use App\Models\kasir\SesiJual;
use App\Models\master\Gudang;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShiftKasirController extends Controller
{
    public function getFaktur(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        try {
            $response = GetterSetter::getLastFaktur($KODE, $LEN);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $response,
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

    public function selectKasir()
    {
        try {
            $username = DB::table('users')->select('email as USERNAME', 'name as FULLNAME')->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $username,
                'total' => count($username),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            // return response()->json(['status' => 'error']);
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function selectShift()
    {
        try {
            $shift = Shift::all();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $shift,
                'total' => count($shift),
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

    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        try {
            $vaData = DB::table('sesi_jual as s')
                ->select(
                    's.SESIJUAL',
                    's.STATUS',
                    's.TGL',
                    'u1.name as KASIR',
                    'u1.email as USERNAMEKASIR',
                    'u2.email as SUPERVISOR',
                    's.TOKO as KODETOKO',
                    'g.Keterangan as TOKO',
                    's.KASSA',
                    'sh.Keterangan as SHIFT',
                    's.KASAWAL'
                )
                ->leftJoin('gudang as g', 'g.Kode', '=', 's.Toko')
                ->leftJoin('users as u1', 'u1.email', '=', 's.Kasir')
                ->leftJoin('users as u2', 'u2.email', '=', 's.Supervisor')
                ->leftJoin('shift as sh', 'sh.Kode', '=', 's.Shift')
                ->orderByDesc('s.SESIJUAL')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaData,
                'total_data' => count($vaData),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function selectSesiSYARAT(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'KASSA' => 'required|max:20',
                'TGL' => 'required',
                // 'STATUS' => 'required',
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

            $inputKassa = $request->input('KASSA');
            $inputTgl = $request->input('TGL');
            $inputStatus = $request->input('STATUS');

            // Cek apakah satu kasir bisa handle dua kassa
            $kasirCapacity = 2; // Jumlah maksimum kassa yang bisa di-handle oleh satu kasir
            $kassaHandledCount = SesiJual::where('KASSA', $inputKassa)
                ->where('TGL', $inputTgl)
                ->where('STATUS', $inputStatus)
                ->count();
            // dd($kassaHandledCount);
            if ($kassaHandledCount >= $kasirCapacity) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Satu kasir hanya dapat menangani dua kassa dalam satu hari',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            // Cek apakah satu kassa hanya bisa memiliki satu sesi dalam sehari
            $existingData = SesiJual::where('KASSA', $inputKassa)
                ->where('TGL', $inputTgl)
                ->where('STATUS', $inputStatus)
                ->exists();

            if ($existingData) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Satu kassa hanya dapat memiliki satu sesi dalam satu hari',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
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

    public function selectSesiOld(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $cEmail = $vaRequestData['auth']['email'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $cKassa = $vaRequestData['Kassa'];
            $dTgl = $vaRequestData['Tgl'];
            $cKasir = $vaRequestData['Kasir'];
            $vaExists = DB::table('sesi_jual')
                ->where('Kassa', '=', $cKassa)
                ->where('Tgl', '=', $dTgl)
                ->where('Status', '=', 2)
                ->exists();
            if ($vaExists) {
                // Yang sama tidak bisa masuk
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Sesi sudah ditutup',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            } else {
                $vaData = DB::table('sesi_jual')
                    ->select('Kasir')
                    ->where('Kassa', '=', $cKassa)
                    ->where('Tgl', '=', $dTgl)
                    ->where('Kasir', $cKasir)
                    ->orderByDesc('DateTime')
                    ->first();
                // dd($vaData);
                if ($vaData) {
                    $cKasir = $vaData->Kasir;
                    if ($cKasir == $cEmail) {
                        return response()->json([
                            'status' => self::$status['SUKSES'],
                            'message' => 'Sukses',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => 'Sesi yang Anda pilih tidak sesuai dengan Sesi Login',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function selectSesi(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $cEmail = $vaRequestData['auth']['email'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $cSesiJual = $vaRequestData['SesiJual'];
            $cKassa = $vaRequestData['Kassa'];
            $dTgl = $vaRequestData['Tgl'];
            $cKasir = $vaRequestData['Kasir'];
            $vaExists = DB::table('sesi_jual')
                ->where('Kassa', '=', $cKassa)
                ->where('SesiJual', '=', $cSesiJual)
                ->where('Tgl', '=', $dTgl)
                ->where('Status', '=', 2)
                ->exists();
            if ($vaExists) {
                // Yang sama tidak bisa masuk
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Sesi sudah ditutup',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            } else {
                $vaData = DB::table('sesi_jual')
                    ->select('Kasir')
                    ->where('Kassa', '=', $cKassa)
                    ->where('SesiJual', '=', $cSesiJual)
                    ->where('Tgl', '=', $dTgl)
                    ->where('Kasir', $cKasir)
                    ->orderByDesc('DateTime')
                    ->first();
                if ($vaData) {
                    $cKasir = $vaData->Kasir;
                    if ($cKasir == $cEmail) {
                        return response()->json([
                            'status' => self::$status['SUKSES'],
                            'message' => 'Sukses',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => 'Sesi yang Anda pilih tidak sesuai dengan Sesi Login',
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function sesiFullName(Request $request)
    {
        $USERNAME_SUPERVISOR = $request->USERNAME_SUPERVISOR;
        $USERNAME_KASIR = $request->USERNAME_KASIR;

        try {
            // Mencari supervisor berdasarkan USERNAME_SUPERVISOR
            $supervisor = DB::table('users')->where('email', $USERNAME_SUPERVISOR)->first();
            // Jika supervisor tidak ditemukan berdasarkan USERNAME, coba cari berdasarkan FULLNAME
            if (!$supervisor) {
                $supervisor = DB::table('users')->where('name', $USERNAME_SUPERVISOR)->first();
            }
            $supervisorFullName = $supervisor ? $supervisor->name : $USERNAME_SUPERVISOR;
            // dd($supervisorFullName);
            // Mencari kasir berdasarkan USERNAME_KASIR
            $kasir = DB::table('users')->where('email', $USERNAME_KASIR)->first();
            // Jika kasir tidak ditemukan berdasarkan USERNAME, coba cari berdasarkan FULLNAME
            if (!$kasir) {
                $kasir = DB::table('users')->where('name', $USERNAME_KASIR)->first();
            }
            $kasirFullName = $kasir ? $kasir->name : $USERNAME_KASIR;

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => [
                    'FULLNAME_SUPERVISOR' => $supervisorFullName,
                    'FULLNAME_KASIR' => $kasirFullName,
                ],
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



    public function store(Request $request)
    {
        try {
            $messages = config('validate.validation');
            $validator = validator::make($request->all(), [
                'SUPERVISOR' => 'required',
                'TOKO' => 'max:20',
                'KASSA' => 'max:20',
                'SHIFT' => 'required|max:20',
                'KASAWAL' => 'numeric|min:0'
            ], $messages);

            if ($validator->fails()) {
                $errors = $validator->errors()->first();
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => $errors,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 422);
            }

            $vaExists = DB::table('sesi_jual')
                ->where('Kassa', '=', $request->KASSA)
                ->where('Tgl', '=', $request->TGL)
                // ->where('Toko', '=', $request->TOKO)
                // ->where('Shift', '=', $request->SHIFT)
                ->where('Status', '<>', '2')
                ->exists();

            if ($vaExists) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data sesi jual sudah ada untuk Kassa: ' . $request->KASSA . ', Tanggal: ' . $request->TGL . ', Toko: ' . $request->TOKO,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            } else {
                SesiJual::create([
                    'SESIJUAL' => GetterSetter::getLastFaktur('SJ', 6),
                    'SUPERVISOR' => $request->SUPERVISOR,
                    'TGL' => $request->TGL,
                    'TOKO' => $request->TOKO ?? '',
                    'KASSA' => $request->KASSA,
                    'KASIR' => $request->KASIR,
                    'SHIFT' => $request->SHIFT,
                    'STATUS' => '0',
                    'KASAWAL' => $request->KASAWAL,
                    'DATETIME' => Carbon::now()
                ]);
                // GetterSetter::setLastFaktur('SJ');
                GetterSetter::setLastKodeRegister('SJ');
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Simpan Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
        } catch (\Throwable $th) {
            // return response()->json(['status' => 'error']);
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
        // return response()->json(['status' => 'success']);
    }

    public function closing(Request $request)
    {
        try {
            $SESIJUAL = $request->KODESESI;
            // Memeriksa apakah STATUS sudah 2
            // $sesi = SesiJual::where('SESIJUAL', $SESIJUAL)->first();
            // if ($sesi && $sesi->STATUS == 2) {
            //     return response()->json(['status' => 'exist']);
            // }

            // Jika STATUS belum 2, maka update STATUS menjadi 2
            SesiJual::where('SESIJUAL', $SESIJUAL)->update(['STATUS' => 2]);
            Upd::UpdRekeningKasir($SESIJUAL);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Simpan Data',
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
