<?php

namespace App\Http\Controllers\api\transaksi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\api\master\ConfigController;

class LaporanTransaksiController extends Controller
{
    public function data(Request $request)
    {
        $dTglAwal = $request->startDate;
        $dTglAkhir = $request->endDate;
        $kasir = $request->kasir;
        $supervisor = $request->supervisor;
        $tipeLaporan = $request->tipeLaporan;
        try {

            $vaData = [];
            $config = null;

            if ($tipeLaporan == 'invoice') {
                $vaData = DB::table('invoice as i')
                    ->select(
                        'i.kode_invoice',
                        'i.nama_tamu',
                        'i.nik',
                        'i.no_telepon',
                        'i.bayar',
                        'i.total_harga',
                        'p.keterangan as cara_bayar',
                        'i.tgl',
                        'i.total_kamar',
                        'i.status_bayar',
                        'i.kembalian',
                        'i.sisa_bayar',
                        'i.dp',
                        'i.sesi_jual',
                        'i.ppn',
                        's.kasir',
                        'i.disc'
                    )
                    ->leftJoin('pembayaran as p', 'i.cara_bayar', 'p.kode')
                    ->leftJoin('sesi_jual as s', 'i.sesi_jual', 's.sesijual')
                    ->whereBetween('i.tgl', [$dTglAwal, $dTglAkhir])
                    ->when($kasir, function ($query) use ($kasir) {
                        $query->where('s.kasir', $kasir);
                    })
                    ->when($supervisor, function ($query) use ($supervisor) {
                        $query->where('s.supervisor', $supervisor);
                    })
                    ->orderByDesc('i.tgl')
                    ->get();
            } else {
                $vaData = DB::table('reservasi as r')
                    ->select(
                        'r.kode_reservasi',
                        'r.nama_tamu',
                        'r.nik',
                        'r.no_telepon',
                        'r.dp',
                        'r.total_kamar',
                        'p.keterangan as cara_bayar',
                        'r.tgl',
                        'r.sesi_jual',
                        's.kasir'
                    )
                    ->leftJoin('pembayaran as p', 'r.cara_bayar', 'p.kode')
                    ->leftJoin('sesi_jual as s', 'r.sesi_jual', 's.sesijual')
                    ->whereBetween('r.tgl', [$dTglAwal, $dTglAkhir])
                    ->when($kasir, function ($query) use ($kasir) {
                        $query->where('s.kasir', $kasir);
                    })
                    ->when($supervisor, function ($query) use ($supervisor) {
                        $query->where('s.supervisor', $supervisor);
                    })->orderByDesc('r.tgl')
                    ->get();

                // Ambil detail kamar untuk setiap reservasi
                foreach ($vaData as $reservasi) {
                    $detailKamar = DB::table('detail_reservasi as dr')
                        ->leftJoin('kamar as k', 'dr.no_kamar', 'k.kode_kamar')
                        ->select(
                            'k.no_kamar',
                            'k.kode_kamar',
                            'k.per_harga',
                            'dr.harga_kamar',
                            'dr.tgl_checkin',
                            'dr.tgl_checkout',
                            'k.status as status_kamar'
                        )
                        ->where('dr.kode_reservasi', $reservasi->kode_reservasi)
                        ->get();
                    $reservasi->kamar = $detailKamar;
                }
            }

            if ($vaData->isEmpty()) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'SUKSES',
                    'data' => [],
                    'totData' => 0,
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            $totData = $vaData->count();
            $response = [];

            if ($tipeLaporan == 'invoice') {
                $totLaporan = 0;
                $response = $vaData->map(function ($invoice) use (&$totLaporan, $config) {
                    $vaData2 = DB::table('detail_invoice as d')
                        ->leftJoin('kamar as k', 'd.no_kamar', 'k.kode_kamar')
                        ->select(
                            'k.no_kamar',
                            'k.kode_kamar',
                            'd.harga_kamar',
                            'd.tgl_checkin',
                            'd.tgl_checkout',
                            'k.status as status_kamar',
                            'k.per_harga',
                        )
                        ->where('d.kode_invoice', $invoice->kode_invoice)
                        ->get();

                    $totalHarga = $invoice->total_harga;
                    $disc = $invoice->total_kamar * ($invoice->disc / 100);
                    $ppn = $invoice->total_kamar * ($invoice->ppn / 100);
                    $totalHargareal = $invoice->total_kamar + $ppn - $disc;

                    if ($invoice->status_bayar == '0') {
                        $totLaporan++;
                    }

                    return [
                        'kode' => $invoice->kode_invoice,
                        'kasir' => $invoice->kasir,
                        'nama_tamu' => $invoice->nama_tamu,
                        'no_telepon' => $invoice->no_telepon,
                        'nik' => $invoice->nik,
                        'total_bayar_tersisa' => $totalHarga,
                        'total_harga_real' => $totalHargareal,
                        'bayar' => $invoice->bayar,
                        'sesi_jual' => $invoice->sesi_jual,
                        'status_bayar' => $invoice->status_bayar,
                        'sisa_bayar' => $invoice->sisa_bayar,
                        'cara_bayar' => $invoice->cara_bayar,
                        'total_kamar' => $invoice->total_kamar,
                        'tgl' => $invoice->tgl,
                        'dp' => $invoice->dp,
                        'ppn' => $invoice->ppn,
                        'kembalian' => $invoice->kembalian,
                        'kamar' => $vaData2->map(function ($item) {
                            return [
                                'harga_kamar' => $item->harga_kamar,
                                'kode_kamar' => $item->kode_kamar,
                                'no_kamar' => $item->no_kamar,
                                'cek_in' => $item->tgl_checkin,
                                'cek_out' => $item->tgl_checkout,
                                'status' => $item->status_kamar,
                                'per_harga' => $item->per_harga
                            ];
                        }),
                    ];
                });
                $totData = $totLaporan;
            } else {
                $response = $vaData->map(function ($reservasi) {
                    return [
                        'kode' => $reservasi->kode_reservasi,
                        'kasir' => $reservasi->kasir,
                        'nama_tamu' => $reservasi->nama_tamu,
                        'sesi_jual' => $reservasi->sesi_jual,
                        'no_telepon' => $reservasi->no_telepon,
                        'nik' => $reservasi->nik,
                        'total_kamar' => $reservasi->total_kamar,
                        'dp' => $reservasi->dp,
                        'cara_bayar' => $reservasi->cara_bayar,
                        'tgl' => $reservasi->tgl,
                        'kamar' => $reservasi->kamar->map(function ($kamar) {
                            return [
                                'no_kamar' => $kamar->no_kamar,
                                'kode_kamar' => $kamar->kode_kamar,
                                'harga_kamar' => $kamar->harga_kamar,
                                'per_harga' => $kamar->per_harga,
                                'cek_in' => $kamar->tgl_checkin,
                                'cek_out' => $kamar->tgl_checkout,
                                'status_kamar' => $kamar->status_kamar,
                            ];
                        }),
                    ];
                });
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $response,
                'totData' => $totData,
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
}