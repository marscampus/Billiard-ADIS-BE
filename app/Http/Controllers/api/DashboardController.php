<?php

namespace App\Http\Controllers\api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\api\master\ConfigController;

class DashboardController extends Controller
{
    public function data(Request $request)
    {
        try {
            // Ambil data kamar
            $vaData = DB::table('kamar as k')
                ->select(
                    't.keterangan as tipe_kamar',
                    'k.no_kamar',
                    'k.kode_kamar',
                    'k.status as status_kamar',
                    'k.foto as foto_kamar',
                    'k.harga as harga_kamar',
                    'k.fasilitas',
                    'k.per_harga'
                )
                ->leftJoin('tipe_kamar as t', 't.kode', '=', 'k.tipe_kamar')
                ->get()
                ->groupBy('tipe_kamar');

            // Struktur data akhir
            $result = [];

            $data = [
                'kode' => ['ppn'],
            ];


            $request = new Request($data);

            $configController = new ConfigController();
            $response = $configController->data($request);

            $config = json_decode($response->getContent(), true);

            // dd($data);
            foreach ($vaData as $tipeKamar => $rooms) {
                // Hitung jumlah kamar tersedia berdasarkan status == 0
                $tersedia = $rooms->where('status_kamar', 0)->count();

                // Map setiap kamar
                $kamar = $rooms->map(function ($room) use ($request) {
                    // Pecah string fasilitas menjadi array
                    $fasilitasKode = explode('|', $room->fasilitas);

                    // Ambil data fasilitas berdasarkan kode
                    $fasilitas = DB::table('fasilitas_kamar')
                        ->whereIn('kode', $fasilitasKode)
                        ->get()
                        ->map(function ($fasilitasItem) {
                            return [
                                'nama' => $fasilitasItem->keterangan
                            ];
                        });




                    $nowHour = Carbon::now()->format('H:i');

                    // Ambil semua jam yang lebih besar dari jam sekarang
                    $allHours = DB::table('jammain')
                        ->pluck('jam')
                        ->filter(function ($v) use ($nowHour) {
                            return intval(substr($v, 0, 2)) > intval(substr($nowHour, 0, 2));
                        })
                        ->values()
                        ->toArray();


                    // Ambil jam yang sudah terpakai
                    $vaDetail = DB::table('detail_reservasi as d')
                        ->leftJoin('reservasi as r', 'd.kode_reservasi', 'r.kode_reservasi')
                        ->select('d.tgl_checkin as cek_in', 'd.tgl_checkout as cek_out', 'r.nama_tamu')
                        ->where('d.no_kamar', $room->kode_kamar)
                        ->where('r.status', '0')
                        ->get();

                    $vaDetailInvoice = DB::table('detail_invoice as d')
                        ->leftJoin('invoice as i', 'd.kode_invoice', 'i.kode_invoice')
                        ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                        ->select('d.tgl_checkin as cek_in', 'd.tgl_checkout as cek_out', 'i.nama_tamu')
                        ->where('d.no_kamar', $room->kode_kamar)
                        ->where('k.status', '1')
                        ->where('i.tgl', date('Y-m-d'))
                        ->get();

                    $vaTerpakai = [];
                    $vaTerpakaiText = [];

                    foreach ($vaDetail as $d) {
                        $in = Carbon::parse($d->cek_in)->format('H:i');
                        $out = Carbon::parse($d->cek_out)->format('H:i');
                        $tgl = Carbon::parse($d->cek_in)->format('Y-m-d');
                        $nama = $d->nama_tamu;
                        $vaTerpakai[] = [
                            'start' => $in,
                            'end'   => $out,
                        ];


                        $vaTerpakaiText[] = "$tgl | $in - $out [ $nama ]";
                    }

                    foreach ($vaDetailInvoice as $d) {
                        $in = Carbon::parse($d->cek_in)->format('H:i');
                        $out = Carbon::parse($d->cek_out)->format('H:i');
                        $tgl = Carbon::parse($d->cek_in)->format('Y-m-d');
                        $nama = $d->nama_tamu;
                        $vaTerpakai[] = [
                            'start' => $in,
                            'end'   => $out,
                        ];


                        $vaTerpakaiText[] = "$tgl | $in - $out [ $nama ]";
                    }


                    // FILTER dari $allHours yg tidak bentrok
                    $availableHours = array_filter($allHours, function ($jam) use ($vaTerpakai) {

                        foreach ($vaTerpakai as $range) {

                            if ($jam >= $range['start'] && $jam < $range['end']) {
                                return false;
                            }
                        }

                        return true;
                    });

                    $availableHours = array_values($availableHours);

                    $vaKamar = [
                        'no_kamar'      => $room->no_kamar,
                        'kode_kamar'    => $room->kode_kamar,
                        'status_kamar'  => $room->status_kamar,
                        'harga_kamar'   => $room->harga_kamar,
                        'fasilitas'     => $fasilitas,
                        'per_harga'     => $room->per_harga,
                        'used' => $vaTerpakaiText,
                        'unused' => $availableHours
                    ];


                    if (!$request->dashboard) {

                        if (!empty($room->foto_kamar)) {
                            $fileKey = 'images/meja/' . $room->foto_kamar;
                            $foto_kamar = Storage::disk('minio')->get($fileKey);
                            $base64 = base64_encode($foto_kamar);
                            $room->foto_kamar = 'data:image/jpeg;base64,' . $base64;
                        } else {
                            $room->foto_kamar = null;
                        }

                        $vaKamar['foto_kamar'] = $room->foto_kamar;
                    }

                    return $vaKamar;
                });

                $result[] = [
                    'tipe_kamar' => $tipeKamar,
                    'tersedia' => $tersedia,
                    'kamar' => $kamar,
                ];
            }

            $vaPembayaran = DB::table('pembayaran')->select('kode as value', 'keterangan as label')->get();


            $now = Carbon::now();

            $vaReservasi1 = DB::table('detail_reservasi as d')
                ->leftJoin('reservasi as r', 'd.kode_reservasi', 'r.kode_reservasi')
                ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                ->select(
                    'r.nama_tamu as nama',
                    'd.tgl_checkin as cek_in',
                    'd.tgl_checkout as cek_out',
                    'k.no_kamar as meja'
                )
                ->where('r.tgl', date('Y-m-d'));

            $vaReservasi2 = DB::table('detail_invoice as d')
                ->leftJoin('invoice as i', 'd.kode_invoice', 'i.kode_invoice')
                ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                ->select(
                    'i.nama_tamu as nama',
                    'd.tgl_checkin as cek_in',
                    'd.tgl_checkout as cek_out',
                    'k.no_kamar as meja'
                )
                ->where('i.tgl', date('Y-m-d'));

            $vaReservasi = $vaReservasi1
                ->union($vaReservasi2) 
                ->get();


            $vaJam = DB::table('jammain')->get();


            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $result,
                'dataReservasi' => $vaReservasi,
                'dataPembayaran' => $vaPembayaran,
                'jam' => $vaJam,
                'ppn' => isset($config['data']) ? $config['data']['ppn'] : 0,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage() . $th->getLine(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getDataKamarDigunakan(Request $request)
    {
        try {
            // Ambil data kamar
            $kamars = DB::table('kamar as k')
                ->leftJoin('tipe_kamar as t', 't.kode', '=', 'k.tipe_kamar')
                ->select(
                    't.keterangan as tipe_kamar',
                    'k.no_kamar',
                    'k.kode_kamar',
                    'k.status as status_kamar',
                    'k.harga as harga_kamar',
                    'k.fasilitas'
                )
                ->get();

            $totKamar = $kamars->count();
            $tersedia = $kamars->where('status_kamar', 0)->count();
            $terpakai = $kamars->where('status_kamar', 1)->count();

            $vaData = $kamars->groupBy('tipe_kamar');

            $result = $vaData->map(function ($rooms, $tipeKamar) {
                $kamarAktif = $rooms->where('status_kamar', 1);
                $kode_kamar = $kamarAktif->pluck('kode_kamar')->toArray();

                $vaKamarUsed = DB::table('detail_invoice as d')
                    ->select('d.no_kamar', 'd.tgl_checkout')
                    ->whereIn('d.no_kamar', $kode_kamar)
                    ->whereRaw('d.id IN (SELECT MAX(id) FROM detail_invoice GROUP BY no_kamar)')
                    ->get();

                $kamar = $rooms->map(function ($room) {
                    $fasilitasKode = explode('|', $room->fasilitas);
                    $fasilitas = DB::table('fasilitas_kamar')
                        ->whereIn('kode', $fasilitasKode)
                        ->pluck('keterangan')
                        ->map(fn($fasilitas) => ['nama' => $fasilitas])
                        ->values();

                    return [
                        'no_kamar' => $room->no_kamar,
                        'kode_kamar' => $room->kode_kamar,
                        'status_kamar' => $room->status_kamar,
                        'harga_kamar' => $room->harga_kamar,
                        'fasilitas' => $fasilitas,
                    ];
                });

                return [
                    'tipe_kamar' => $tipeKamar,
                    'kamar' => $kamar,
                    'kamar_used' => $vaKamarUsed,
                ];
            })->values();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $result,
                'kamar_tersedia' => $tersedia,
                'tot_kamar' => $totKamar,
                'kamar_terpakai' => $terpakai,
                'datetime' => now(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
