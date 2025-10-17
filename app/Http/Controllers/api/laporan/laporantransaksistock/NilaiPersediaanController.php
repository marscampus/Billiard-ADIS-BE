<?php
/*
 * Copyright (C) Godong
 *http://www.marstech.co.id
 *Email. info@marstech.co.id
 *Telp. 0811-3636-09
 *Office        : Jl. Margatama Asri IV, Kanigoro, Kec. Kartoharjo, Kota Madiun, Jawa Timur 63118
 *Branch Office : Perum Griya Gadang Sejahtera Kav. 14 Gadang - Sukun - Kota Malang - Jawa Timur
 *
 *Godong
 *Adalah merek dagang dari PT. Marstech Global
 *
 *License Agreement
 *Software komputer atau perangkat lunak komputer ini telah diakui sebagai salah satu aset perusahaan yang bernilai.
 *Di Indonesia secara khusus,
 *software telah dianggap seperti benda-benda berwujud lainnya yang memiliki kekuatan hukum.
 *Oleh karena itu pemilik software berhak untuk memberi ijin atau tidak memberi ijin orang lain untuk menggunakan softwarenya.
 *Dalam hal ini ada aturan hukum yang berlaku di Indonesia yang secara khusus melindungi para programmer dari pembajakan software yang mereka buat,
 *yaitu diatur dalam hukum hak kekayaan intelektual (HAKI).
 *
 *********************************************************************************************************
 *Pasal 72 ayat 3 UU Hak Cipta berbunyi,
 *' Barangsiapa dengan sengaja dan tanpa hak memperbanyak penggunaan untuk kepentingan komersial '
 *' suatu program komputer dipidana dengan pidana penjara paling lama 5 (lima) tahun dan/atau '
 *' denda paling banyak Rp. 500.000.000,00 (lima ratus juta rupiah) '
 *********************************************************************************************************
 *
 *Proprietary Software
 *Adalah software berpemilik, sehingga seseorang harus meminta izin serta dilarang untuk mengedarkan,
 *menggunakan atau memodifikasi software tersebut.
 *
 *Commercial software
 *Adalah software yang dibuat dan dikembangkan oleh perusahaan dengan konsep bisnis,
 *dibutuhkan proses pembelian atau sewa untuk bisa menggunakan software tersebut.
 *Detail Licensi yang dianut di software https://en.wikipedia.org/wiki/Proprietary_software
 *EULA https://en.wikipedia.org/wiki/End-user_license_agreement
 *
 *Lisensi Perangkat Lunak https://id.wikipedia.org/wiki/Lisensi_perangkat_lunak
 *EULA https://id.wikipedia.org/wiki/EULA
 *
 * Created on Mon May 13 2024 - 02:22:15
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporantransaksistock;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NilaiPersediaanController extends Controller
{
    function data(Request $request)
    {
        $vaValidator = Validator::make($request->all(), [
            'Tgl' => 'required|date'
        ], [
            'required' => 'Kolom :attribute harus diisi.',
            'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
            'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
            'unique' => 'Kolom :attribute sudah ada di database.',
            'numeric' => 'Kolom :attribute harus angka'
        ]);
        if ($vaValidator->fails()) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => $vaValidator->errors()->first(),
                'datetime' => date('Y-m-d H:i:s')
            ], 422);
        }
        try {
            $vaArray = [];
            $totalHargaPokok = 0;
            $totalSaldoStock = 0;
            $totalNilaiStock = 0;

            $vaData = DB::table('stock as s')
                ->select(
                    's.Kode',
                    's.Kode_Toko as Barcode',
                    's.Nama',
                    's.Satuan',
                    's.HB',
                    's.HJ'
                )
                ->leftJoin('satuanstock as sa', 'sa.Kode', '=', 's.Satuan')
                ->get();
            foreach ($vaData as $d) {
                $cKode = $d->Kode;
                $nHP = 0; // Inisialisasi $nHP dengan 0
                $vaData2 = DB::table('stock_hp')
                    ->select('HP')
                    ->where('Tgl', '=', $request->Tgl)
                    ->where('Kode', '=', $cKode)
                    ->orderByDesc('ID')
                    ->first();
                if ($vaData2) {
                    $nHP = $vaData2->HP;
                }

                $nSaldoStock = 0;
                $vaData3 = DB::table('kartustock')
                    ->select(
                        DB::raw('IFNULL(SUM(Debet - Kredit),0) as SaldoStock')
                    )
                    ->where('Tgl', '=', $request->Tgl)
                    ->where('Kode', '=', $cKode)
                    ->groupBy('Kode')
                    ->first();
                if ($vaData3) {
                    $nSaldoStock = $vaData3->SaldoStock;
                }

                // Konversi SaldoStock ke angka
                $nSaldoStock = (float) $nSaldoStock;
                $nNilaiStock = $nHP * $nSaldoStock;

                $vaArray[] = [
                    'Barcode' => $d->Barcode,
                    'Kode' => $d->Kode,
                    'Nama' => $d->Nama,
                    'Satuan' => $d->Satuan,
                    'HargaBeli' => $d->HB,
                    'HargaPokok' => $nHP,
                    'HargaJual' => $d->HJ,
                    'SaldoStock' => $nSaldoStock,
                    'NilaiStock' => $nNilaiStock
                ];

                // Menambahkan ke total
                $totalHargaPokok += $nHP;
                $totalSaldoStock += $nSaldoStock;
                $totalNilaiStock += $nNilaiStock;
            }
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
                'totals' => [
                    'totalHargaPokok' => $totalHargaPokok,
                    'totalSaldoStock' => $totalSaldoStock,
                    'totalNilaiStock' => $totalNilaiStock
                ],
                'total_data' => count($vaArray),
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

    function exportPDF(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $dTgl = $vaRequestData['Tgl'];
        $cUser = $vaRequestData['auth']['name'];

        try {
            ini_set('max_execution_time', 0);
            $vaArray = [];
            $totalHargaPokok = 0;
            $totalSaldoStock = 0;
            $totalNilaiStock = 0;

            $vaData = DB::table('stock as s')
                ->select(
                    's.Kode',
                    's.Kode_Toko as Barcode',
                    's.Nama',
                    's.Satuan',
                    's.HB',
                    's.HJ'
                )
                ->leftJoin('satuanstock as sa', 'sa.Kode', '=', 's.Satuan');

            if (!empty($request->filters)) {
                foreach ($request->filters as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData = $vaData->get();
            foreach ($vaData as $d) {
                $cKode = $d->Kode;
                $nHP = 0; // Initialize $nHP
                $vaData2 = DB::table('stock_hp')
                    ->select('HP')
                    ->where('Tgl', '=', $dTgl)
                    ->where('Kode', '=', $cKode)
                    ->orderByDesc('ID')
                    ->first();
                if ($vaData2) {
                    $nHP = $vaData2->HP;
                }

                $nSaldoStock = 0;
                $vaData3 = DB::table('kartustock')
                    ->select(
                        DB::raw('IFNULL(SUM(Debet - Kredit),0) as SaldoStock')
                    )
                    ->where('Tgl', '=', $dTgl)
                    ->where('Kode', '=', $cKode)
                    ->groupBy('Kode')
                    ->first();
                if ($vaData3) {
                    $nSaldoStock = $vaData3->SaldoStock;
                }

                // Convert SaldoStock to a float
                $nSaldoStock = (float) $nSaldoStock;
                $nNilaiStock = $nHP * $nSaldoStock;

                $vaArray[] = [
                    'Barcode' => $d->Barcode,
                    'Kode' => $d->Kode,
                    'Nama' => $d->Nama,
                    'Satuan' => $d->Satuan,
                    'HargaBeli' => $d->HB,
                    'HargaPokok' => $nHP,
                    'HargaJual' => $d->HJ,
                    'SaldoStock' => $nSaldoStock,
                    'NilaiStock' => $nNilaiStock
                ];

                // Menambahkan ke total
                $totalHargaPokok += $nHP;
                $totalSaldoStock += $nSaldoStock;
                $totalNilaiStock += $nNilaiStock;
            }

            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'totals' => [
                    'totalHargaPokok' => $totalHargaPokok,
                    'totalSaldoStock' => $totalSaldoStock,
                    'totalNilaiStock' => $totalNilaiStock
                ]
            ];

            // Create an instance of the PDF
            $pdf = app('dompdf.wrapper'); // or use PDF::getFacadeRoot() if using a facade

            // Load the view
            $pdf->loadView(' laporan.nilai-persediaan.nilai-persediaan', ['persediaan' => $vaResult['data']]);

            // Return the generated PDF
            return $pdf->download('laporan_persediaan.pdf');
        } catch (\Throwable $th) {

            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
