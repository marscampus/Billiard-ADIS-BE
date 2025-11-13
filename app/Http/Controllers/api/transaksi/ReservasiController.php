<?php

namespace App\Http\Controllers\api\transaksi;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\api\master\ConfigController;

class ReservasiController extends Controller
{
    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'nama_tamu' => 'required|max:100',
                'no_telepon' => 'required|max:20',
                'total_harga' => 'required',
                // 'sesi_jual' => 'required',
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
            $cKodeReservasi = GetterSetter::getKodeFaktur('RSV', 3);
            // Ambil semua data kamar dan reservasi terlebih dahulu
            $kamarReservasiData = DB::table('detail_reservasi as k')
                ->select(
                    'k.no_kamar',
                    'k.tgl_checkin',
                    'k.tgl_checkout',
                    'k.per_harga'
                )
                ->orderByDesc('k.id')
                ->get()
                ->groupBy('no_kamar');

            $vaArray = [];

            foreach ($request->input('kamar') as $item) {
                $dTglIn = Carbon::parse($item['cek_in']);
                $kode_kamar = $item['kode_kamar'];

                // Validasi dengan data dari database
                if (isset($kamarReservasiData[$kode_kamar])) {
                    $reservasi = $kamarReservasiData[$kode_kamar];

                    // Periksa apakah tanggal check-in bertabrakan dengan data di database
                    $isDateMatchedDB = $reservasi->contains(function ($r) use ($dTglIn) {
                        $tglCheckin = Carbon::parse($r->tgl_checkin);
                        $tglCheckout = Carbon::parse($r->tgl_checkout);

                        return $dTglIn->between($tglCheckin, $tglCheckout);
                    });

                    if ($isDateMatchedDB) {
                        return response()->json([
                            'status' => self::$status['GAGAL'],
                            'message' => "Maaf, kamar $kode_kamar sedang digunakan pada tanggal $dTglIn",
                            'datetime' => date('Y-m-d H:i:s')
                        ], 400);
                    }
                }



                // Tambahkan data ke array setelah validasi selesai
                $vaArray[] = [
                    'kode_reservasi' => $cKodeReservasi,
                    'no_kamar' => $item['kode_kamar'],
                    'harga_kamar' => $item['harga_kamar'],
                    'tgl_checkin' => $item['cek_in'],
                    'tgl_checkout' => $item['cek_out'],
                    'per_harga' => $item['per_harga'],
                ];
            }

            // dd($vaArray);

            $vaData = DB::table('reservasi')->insert([
                'kode_reservasi' => $cKodeReservasi,
                'nama_tamu' => $request->nama_tamu,
                'nik' => $request->nik,
                'no_telepon' => $request->no_telepon,
                'total_harga' => $request->total_harga,
                'total_kamar' => $request->total_kamar,
                'dp' => $request->dp,
                'sesi_jual' => $request->sesi_jual,
                'cara_bayar' => $request->metode_pembayaran,
                'tgl' => Carbon::now()
            ]);

            DB::table('detail_reservasi')->insert($vaArray);

            GetterSetter::setKodeFaktur('RSV');

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
                'kode_reservasi' => $cKodeReservasi,
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

    public function getDataEdit1(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'no_kamar' => 'required|string|max:14'
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
            $cNoKamar = $request->no_kamar;

            $vaData = DB::table('reservasi as r')
                ->select(
                    'r.id',
                    'r.nama_tamu',
                    'r.no_telepon',
                    'r.no_kamar',
                    'r.tgl_checkin',
                    'r.tgl_checkout',
                    'k.harga as harga_kamar'
                )
                ->leftJoin('kamar as k', 'k.no_kamar', '=', 'r.no_kamar')
                ->where('r.no_kamar', '=', $cNoKamar)
                ->orderByDesc('r.id')
                ->first();
            if (!$vaData) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Tidak Ada Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            } else {
                // Konversi tanggal ke objek Carbon
                $tglCheckin = Carbon::parse($vaData->tgl_checkin);
                $tglCheckout = Carbon::parse($vaData->tgl_checkout);

                // Hitung selisih hari
                $lamaMenginap = $tglCheckout->diffInDays($tglCheckin) + 1;

                // Tambahkan ke hasil
                $vaData->lama = $lamaMenginap;

                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => $vaData,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['GAGAL'] ?? 'GAGAL',
                'message' => $th->getMessage(),
                'data' => null,
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getDataReservasi(Request $request)
    {
        try {
            $vaData = DB::table('reservasi as r')
                ->select('r.kode_reservasi', 'r.nama_tamu', 'r.nik', 'r.no_telepon', 'r.dp', 'r.total_harga')
                ->where('r.status', '=', '0')
                ->get();

            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => [],
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            $data = [
                'kode' => ['ppn'],
            ];

            $request = new Request($data);

            $configController = new ConfigController();
            $response = $configController->data($request);

            $config = json_decode($response->getContent(), true);

            $response = $vaData->map(function ($reservasi) use ($config) {

                $vaData2 = DB::table('detail_reservasi as d')
                    ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                    ->select('k.no_kamar', 'k.kode_kamar', 'd.harga_kamar', 'd.tgl_checkin', 'd.tgl_checkout', 'd.per_harga')
                    ->where('d.kode_reservasi', $reservasi->kode_reservasi)
                    ->get();

                return [
                    'kode_reservasi' => $reservasi->kode_reservasi,
                    'nama_tamu' => $reservasi->nama_tamu,
                    'no_telepon' => $reservasi->no_telepon,
                    'nik' => $reservasi->nik,
                    'total_harga' => $reservasi->total_harga,
                    'dp' => $reservasi->dp,
                    'ppn' => isset($config['data']) ? $config['data']['ppn'] : 0,
                    'kamar' => $vaData2->map(function ($item) {
                        return [
                            'harga_kamar' => $item->harga_kamar,
                            'kode_kamar' => $item->kode_kamar,
                            'no_kamar' => $item->no_kamar,
                            'cek_in' => $item->tgl_checkin,
                            'cek_out' => $item->tgl_checkout,
                            'per_harga' => $item->per_harga
                        ];
                    }),
                ];
            });
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'error' => [
                    'error' => $th->getMessage(),
                    'line' => $th->getLine(),
                ],
                'message' => 'Terjadi Kesalahan Saat Proses Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function checkout(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $vaValidator = Validator::make($request->all(), [
            'kode_kamar' => 'required|max:6'
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
        try {
            $cKodeKamar = $vaRequestData['kode_kamar'];
            $vaData = DB::table('kamar')
                ->where('kode_kamar', '=', $cKodeKamar)
                ->update(['status' => '0']);

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

    public function laporan(Request $request)
    {
        $dTglAwal = $request->tgl_awal;
        $dTglAkhir = $request->tgl_akhir;
        try {
            $vaData = DB::table('reservasi as r')
                ->select(
                    'r.kode_reservasi',
                    'r.nama_tamu',
                    'r.nik',
                    'r.no_telepon',
                    'r.dp',
                    'p.keterangan as cara_bayar',
                    'r.cara_bayar as kode_cara_bayar'
                )
                ->leftJoin('pembayaran as p', 'r.cara_bayar', 'p.kode')
                ->whereBetween('r.tgl', [$dTglAwal, $dTglAkhir])
                ->get();

            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => [],
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            $response = $vaData->map(function ($reservasi) {
                // Query untuk mendapatkan detail kamar terkait dengan reservasi ini
                $vaData2 = DB::table('detail_reservasi as d')
                    ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                    ->select(
                        'k.no_kamar',
                        'k.kode_kamar',
                        'd.harga_kamar',
                        'd.tgl_checkin',
                        'd.tgl_checkout'
                    )
                    ->where('d.kode_reservasi', $reservasi->kode_reservasi)
                    ->get();

                $totalHarga = $vaData2->sum('harga_kamar');

                return [
                    'kode_reservasi' => $reservasi->kode_reservasi,
                    'nama_tamu' => $reservasi->nama_tamu,
                    'no_telepon' => $reservasi->no_telepon,
                    'nik' => $reservasi->nik,
                    'total_harga' => $totalHarga,
                    'dp' => $reservasi->dp,
                    'cara_bayar' => $reservasi->cara_bayar,
                    'kode_cara_bayar' => $reservasi->kode_cara_bayar,
                    'kamar' => $vaData2->map(function ($item) {
                        return [
                            'harga_kamar' => $item->harga_kamar,
                            'kode_kamar' => $item->kode_kamar,
                            'no_kamar' => $item->no_kamar,
                            'cek_in' => $item->tgl_checkin,
                            'cek_out' => $item->tgl_checkout,
                        ];
                    }),
                ];
            });
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
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

    public function laporanPerUser(Request $request)
    {
        $dTglAwal = $request->tgl_awal;
        $dTglAkhir = $request->tgl_akhir;
        try {
            $vaData = DB::table('reservasi as r')
                ->select(
                    'r.kode_reservasi',
                    'r.nama_tamu',
                    'r.nik',
                    'r.no_telepon',
                    'r.dp',
                    'p.keterangan as cara_bayar',
                    'r.cara_bayar as kode_cara_bayar',
                    'r.status'
                )
                ->leftJoin('pembayaran as p', 'r.cara_bayar', '=', 'p.kode')
                ->where('r.nik', $request->nik)
                ->where('r.no_telepon', $request->telepon)
                ->where(function ($query) use ($dTglAwal, $dTglAkhir) {
                    if ($dTglAwal && $dTglAkhir) {
                        $query->where('r.tgl', '>=', $dTglAwal)
                            ->where('r.tgl', '<=', $dTglAkhir);
                    }
                })
                ->get();


            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => [],
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            $response = $vaData->map(function ($reservasi) {
                // Query untuk mendapatkan detail kamar terkait dengan reservasi ini
                $vaData2 = DB::table('detail_reservasi as d')
                    ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                    ->select(
                        'k.no_kamar',
                        'k.kode_kamar',
                        'd.harga_kamar',
                        'd.tgl_checkin',
                        'd.tgl_checkout'
                    )
                    ->where('d.kode_reservasi', $reservasi->kode_reservasi)
                    ->get();

                $totalHarga = $vaData2->sum('harga_kamar');

                return [
                    'kode_reservasi' => $reservasi->kode_reservasi,
                    'nama_tamu' => $reservasi->nama_tamu,
                    'no_telepon' => $reservasi->no_telepon,
                    'nik' => $reservasi->nik,
                    'total_harga' => $totalHarga,
                    'status_transaksi' => $reservasi->status == '1' ? 'Sudah Ditransaksikan' : 'Belum Ditransaksikan',
                    'kamar' => $vaData2->map(function ($item) {
                        return [
                            'harga_kamar' => $item->harga_kamar,
                            'kode_kamar' => $item->kode_kamar,
                            'no_kamar' => $item->no_kamar,
                            'cek_in' => $item->tgl_checkin,
                            'cek_out' => $item->tgl_checkout,
                        ];
                    }),
                ];
            });
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
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

    public function getDataPdfReservasi(Request $request)
    {

        try {
            $reservasi = DB::table('reservasi as r')
                ->select(
                    'r.kode_reservasi',
                    'r.nama_tamu',
                    'r.nik',
                    'r.no_telepon',
                    'r.total_harga',
                    'p.keterangan as cara_bayar',
                    'r.tgl',
                    'r.dp',
                    'r.total_kamar',
                    'r.cara_bayar as kode_cara_bayar'
                )
                ->leftJoin('pembayaran as p', 'r.cara_bayar', 'p.kode')
                ->where('r.kode_reservasi', $request->kode_reservasi)
                ->first();

            if (!$reservasi) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data Tidak Ditemukan Atau Error',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }


            // Query untuk mendapatkan detail kamar terkait dengan reservasi ini
            $vaData2 = DB::table('detail_reservasi as d')
                ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                ->select('k.no_kamar', 'k.kode_kamar', 'd.harga_kamar', 'd.tgl_checkin', 'd.tgl_checkout', 'k.status as status_kamar', 'k.per_harga')
                ->where('d.kode_reservasi', $reservasi->kode_reservasi)
                ->get();

            // return response()->json([
            //     'status' => self::$status['BAD_REQUEST'],
            //     'message' => 'Terjadi Kesalahan Saat Proses Data',
            //     'data' => $totalHargaKamar,
            //     'datetime' => date('Y-m-d H:i:s')
            // ], 400);

            $data = [
                'kode' => ['logo', 'nama_hotel', 'alamat_hotel', 'no_telp'],
            ];

            $request = new Request($data);

            $configController = new ConfigController();
            $response = $configController->data($request);

            $config = json_decode($response->getContent(), true);

            $totalHarga = $reservasi->total_harga;

            $response = [
                'kode_reservasi' => $reservasi->kode_reservasi,
                'nama_tamu' => $reservasi->nama_tamu,
                'no_telepon' => $reservasi->no_telepon,
                'nik' => $reservasi->nik,
                'total_harga' => $totalHarga,
                'total_kamar' => $reservasi->total_harga,
                'dp' => $reservasi->dp,
                'ppn' => 0,
                'disc' => 0,
                'no_telp_hotel' => $config['data']['no_telp'],
                'alamat_hotel' => $config['data']['alamat_hotel'],
                'nama_hotel' => $config['data']['nama_hotel'],
                'logo_hotel' => $config['data']['logo'],
                'tgl_reservasi' => $reservasi->tgl,
                'cara_bayar' => $reservasi->cara_bayar,
                'kode_cara_bayar' => $reservasi->kode_cara_bayar,
                'kamar' => $vaData2->map(function ($item) {
                    return [
                        'harga_kamar' => $item->harga_kamar,
                        'no_kamar' => $item->no_kamar,
                        'kode_kamar' => $item->kode_kamar,
                        'cek_in' => $item->tgl_checkin,
                        'cek_out' => $item->tgl_checkout,
                        'status' => $item->status_kamar,
                        'per_harga' => $item->per_harga
                    ];
                }),
            ];

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
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
            $vaData = DB::table('reservasi')->where('kode_reservasi', $request->kode)->delete();
            $vaData = DB::table('detail_reservasi')->where('kode_reservasi', $request->kode)->delete();

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
