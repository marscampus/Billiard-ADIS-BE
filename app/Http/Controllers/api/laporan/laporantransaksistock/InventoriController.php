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
 * Created on Mon May 20 2024 - 03:02:53
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporantransaksistock;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;

class InventoriController extends Controller
{
    // Lemot tapi totalnya bener
    public function data(Request $request)
    {
        ini_set('max_execution_time', 0);
        $vaValidator = Validator::make($request->all(), [
            'Bulan' => 'required',
            'Tahun' => 'required',
            'Gudang' => 'required'
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
            $dBulan = $request->Bulan;
            $dTahun = $request->Tahun;
            $dTglAkhir = date('Y-m-d', mktime(0, 0, 0, $dBulan + 1, 0, $dTahun));
            $cGudang = $request->Gudang;

            // Query to get data for total calculations
            $vaTotalQuery = DB::table('stock as s')
                ->select(
                    's.KODE',
                    's.KODE_TOKO',
                    's.NAMA',
                    's.GUDANG',
                    's.SATUAN',
                    's.HB',
                    's.HJ',
                    'g.KETERANGAN'
                )
                ->leftJoin('gudang as g', 'g.KODE', '=', 's.GUDANG')
                ->get();

            $nGrandTotalStockAkhir = 0;
            $nGrandTotalNilaiStock = 0;
            $nGrandTotalNilaiAdjustment = 0;
            $nGrandTotalHargaPokok = 0;

            foreach ($vaTotalQuery as $d) {
                $cKode = $d->KODE;
                $nHpp = GetterSetter::getLastHP($cKode, $dTglAkhir);

                $nSaldoAwal = floatval(self::getSaldoAwal($cKode, $dTglAkhir, $cGudang));
                $nSaldoPembelian = floatval(self::getSaldoPembelian($cKode, $dTglAkhir, $cGudang));
                $nSaldoRtnPembelian = floatval(self::getSaldoRtnPembelian($cKode, $dTglAkhir, $cGudang));
                $nSaldoPenjualan = floatval(self::getSaldoPenjualan($cKode, $dTglAkhir, $cGudang));
                $nSaldoRtnPenjualan = floatval(self::getSaldoRtnPenjualan($cKode, $dTglAkhir, $cGudang));
                $nSaldoMutasiKe = floatval(self::getSaldoMutasiKe($cKode, $dTglAkhir, $cGudang));
                $nSaldoMutasiDari = floatval(self::getSaldoMutasiDari($cKode, $dTglAkhir, $cGudang));
                $nSaldoPackingMasuk = floatval(self::getSaldoPackingMasuk($cKode, $dTglAkhir, $cGudang));
                $nSaldoPackingKeluar = floatval(self::getSaldoPackingKeluar($cKode, $dTglAkhir, $cGudang));
                $nSaldoAdjustment = floatval(self::getSaldoAdjustment($cKode, $dTglAkhir, $cGudang));

                $nSaldoAkhir = $nSaldoAwal + $nSaldoPembelian - $nSaldoRtnPembelian - $nSaldoPenjualan + $nSaldoRtnPenjualan - $nSaldoMutasiKe + $nSaldoMutasiDari + $nSaldoPackingMasuk - $nSaldoPackingKeluar + $nSaldoAdjustment;
                $nNilaiStock = $nSaldoAkhir * $nHpp;
                $nNilaiAdjustment = abs($nSaldoAdjustment) * $nHpp;

                $nGrandTotalStockAkhir += $nSaldoAkhir;
                $nGrandTotalNilaiStock += $nNilaiStock;
                $nGrandTotalNilaiAdjustment += $nNilaiAdjustment;
                $nGrandTotalHargaPokok += $nHpp;
            }

            // Now fetch paginated data for the response
            $vaDataQuery = DB::table('stock as s')
                ->select(
                    's.KODE',
                    's.KODE_TOKO',
                    's.NAMA',
                    's.GUDANG',
                    's.SATUAN',
                    's.HB',
                    's.HJ',
                    'g.KETERANGAN'
                )
                ->leftJoin('gudang as g', 'g.KODE', '=', 's.GUDANG')
                ->get();
            $nTotalStockAkhir = 0;
            $nTotalNilaiStock = 0;
            $nTotalNilaiAdjustment = 0;
            $nTotalHargaPokok = 0;

            foreach ($vaDataQuery as $d) {
                $cKode = $d->KODE;
                $nHpp = GetterSetter::getLastHP($cKode, $dTglAkhir);

                $nSaldoAwal = floatval(self::getSaldoAwal($cKode, $dTglAkhir, $cGudang));
                $nSaldoPembelian = floatval(self::getSaldoPembelian($cKode, $dTglAkhir, $cGudang));
                $nSaldoRtnPembelian = floatval(self::getSaldoRtnPembelian($cKode, $dTglAkhir, $cGudang));
                $nSaldoPenjualan = floatval(self::getSaldoPenjualan($cKode, $dTglAkhir, $cGudang));
                $nSaldoRtnPenjualan = floatval(self::getSaldoRtnPenjualan($cKode, $dTglAkhir, $cGudang));
                $nSaldoMutasiKe = floatval(self::getSaldoMutasiKe($cKode, $dTglAkhir, $cGudang));
                $nSaldoMutasiDari = floatval(self::getSaldoMutasiDari($cKode, $dTglAkhir, $cGudang));
                $nSaldoPackingMasuk = floatval(self::getSaldoPackingMasuk($cKode, $dTglAkhir, $cGudang));
                $nSaldoPackingKeluar = floatval(self::getSaldoPackingKeluar($cKode, $dTglAkhir, $cGudang));
                $nSaldoAdjustment = floatval(self::getSaldoAdjustment($cKode, $dTglAkhir, $cGudang));

                $nSaldoAkhir = $nSaldoAwal + $nSaldoPembelian - $nSaldoRtnPembelian - $nSaldoPenjualan + $nSaldoRtnPenjualan - $nSaldoMutasiKe + $nSaldoMutasiDari + $nSaldoPackingMasuk - $nSaldoPackingKeluar + $nSaldoAdjustment;
                $nNilaiStock = $nSaldoAkhir * $nHpp;
                $nNilaiAdjustment = abs($nSaldoAdjustment) * $nHpp;

                $nTotalStockAkhir += $nSaldoAkhir;
                $nTotalNilaiStock += $nNilaiStock;
                $nTotalNilaiAdjustment += $nNilaiAdjustment;
                $nTotalHargaPokok += $nHpp;

                // Append the result to the array
                $vaArray[] = [
                    'Kode' => $cKode,
                    'Barcode' => $d->KODE_TOKO,
                    'Nama' => $d->NAMA,
                    'Gudang' => $d->KETERANGAN,
                    'Satuan' => $d->SATUAN,
                    'HargaBeli' => $d->HB,
                    'HargaPokok' => $nHpp,
                    'HargaJual' => $d->HJ,
                    'SaldoAwal' => $nSaldoAwal,
                    'Pembelian' => $nSaldoPembelian,
                    'RetPembelian' => $nSaldoRtnPembelian,
                    'Penjualan' => $nSaldoPenjualan,
                    'RetPenjualan' => $nSaldoRtnPenjualan,
                    'MutasiKe' => $nSaldoMutasiKe,
                    'MutasiDari' => $nSaldoMutasiDari,
                    'PackingMasuk' => $nSaldoPackingMasuk,
                    'PackingKeluar' => $nSaldoPackingKeluar,
                    'Adjustment' => abs($nSaldoAdjustment),
                    'StokAkhir' => $nSaldoAkhir,
                    'NilaiStock' => $nNilaiStock,
                    'NilaiAdjustment' => $nNilaiAdjustment
                ];
            }

            $vaArrayTotal = [
                'TotalHargaPokok' => floatval($nGrandTotalHargaPokok),
                'TotalSaldoAwal' => floatval(self::getTotalSaldoAwal($dTglAkhir, $cGudang)),
                'TotalSaldoPembelian' => floatval(self::getTotalSaldoPembelian($dTglAkhir, $cGudang)),
                'TotalSaldoReturPembelian' => floatval(self::getTotalSaldoRtnPembelian($dTglAkhir, $cGudang)),
                'TotalSaldoPenjualan' => floatval(self::getTotalSaldoPenjualan($dTglAkhir, $cGudang)),
                'TotalSaldoRtnPenjualan' => floatval(self::getTotalSaldoRtnPenjualan($dTglAkhir, $cGudang)),
                'TotalSaldoMutasiKe' => floatval(self::getTotalSaldoMutasiKe($dTglAkhir, $cGudang)),
                'TotalSaldoMutasiDari' => floatval(self::getTotalSaldoMutasiDari($dTglAkhir, $cGudang)),
                'TotalSaldoPackingMasuk' => floatval(self::getTotalSaldoPackingMasuk($dTglAkhir, $cGudang)),
                'TotalSaldoPackingKeluar' => floatval(self::getTotalSaldoPackingKeluar($dTglAkhir, $cGudang)),
                'TotalSaldoAdjustment' => floatval(self::getTotalSaldoAdjustment($dTglAkhir, $cGudang)),
                'TotalStockAkhir' => floatval($nGrandTotalStockAkhir),
                'TotalNilaiStock' => floatval($nGrandTotalNilaiStock),
                'TotalNilaiAdjustment' => floatval($nGrandTotalNilaiAdjustment)
            ];

            // If request is successful
            if ($vaDataQuery) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $vaArray,
                    'total' => $vaArrayTotal,
                    'total_data' => count($vaArray),
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }



    function dataExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $dBulan = $vaRequestData['Bulan'];
            $dTahun = $vaRequestData['Tahun'];
            $dTglAkhir = date('Y-m-d', mktime(0, 0, 0, $dBulan + 1, 0, $dTahun));
            $cGudang = $vaRequestData['Gudang'];
            $cUser = $vaRequestData['auth']['name'];

            $perPage = 1000; // Jumlah data per halaman
            $currentPage = 1;
            $vaArray = []; // Inisialisasi array untuk menyimpan hasil

            do {
                // Query untuk mendapatkan data dengan batasan per halaman
                $vaDataQuery = DB::table('stock as s')
                    ->select(
                        's.KODE',
                        's.KODE_TOKO',
                        's.NAMA',
                        's.GUDANG',
                        's.SATUAN',
                        's.HB',
                        's.HJ',
                        'g.KETERANGAN'
                    )
                    ->leftJoin('gudang as g', 'g.KODE', '=', 's.GUDANG')
                    ->offset(($currentPage - 1) * $perPage)
                    ->limit($perPage)
                    ->get();

                foreach ($vaDataQuery as $d) {
                    // Lakukan proses per data di sini
                    $nHpp = GetterSetter::getLastHP($d->KODE, $dTglAkhir);
                    // PER KODE
                    $nSaldoAwal = floatval(self::getSaldoAwal($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoPembelian = floatval(self::getSaldoPembelian($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoRtnPembelian = floatval(self::getSaldoRtnPembelian($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoPenjualan = floatval(self::getSaldoPenjualan($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoRtnPenjualan = floatval(self::getSaldoRtnPenjualan($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoMutasiKe = floatval(self::getSaldoMutasiKe($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoMutasiDari = floatval(self::getSaldoMutasiDari($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoPackingMasuk = floatval(self::getSaldoPackingMasuk($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoPackingKeluar = floatval(self::getSaldoPackingKeluar($d->KODE, $dTglAkhir, $cGudang));
                    $nSaldoAdjustment = floatval(self::getSaldoAdjustment($d->KODE, $dTglAkhir, $cGudang));

                    $nSaldoAkhir = $nSaldoAwal + $nSaldoPembelian - $nSaldoRtnPembelian - $nSaldoPenjualan + $nSaldoRtnPenjualan - $nSaldoMutasiKe + $nSaldoMutasiDari + $nSaldoPackingMasuk - $nSaldoPackingKeluar + $nSaldoAdjustment;
                    $nNilaiStock = $nSaldoAkhir * $nHpp;
                    $nNilaiAdjustment = abs($nSaldoAdjustment) * $nHpp;

                    // Append the result to the array
                    $vaArray[] = [
                        'Kode' => $d->KODE,
                        'Barcode' => $d->KODE_TOKO,
                        'Nama' => $d->NAMA,
                        'Gudang' => $d->KETERANGAN,
                        'Satuan' => $d->SATUAN,
                        'HargaBeli' => $d->HB,
                        'HargaPokok' => $nHpp,
                        'HargaJual' => $d->HJ,
                        'SaldoAwal' => $nSaldoAwal,
                        'Pembelian' => $nSaldoPembelian,
                        'RetPembelian' => $nSaldoRtnPembelian,
                        'Penjualan' => $nSaldoPenjualan,
                        'RetPenjualan' => $nSaldoRtnPenjualan,
                        'MutasiKe' => $nSaldoMutasiKe,
                        'MutasiDari' => $nSaldoMutasiDari,
                        'PackingMasuk' => $nSaldoPackingMasuk,
                        'PackingKeluar' => $nSaldoPackingKeluar,
                        'Adjustment' => abs($nSaldoAdjustment),
                        'StokAkhir' => $nSaldoAkhir,
                        'NilaiStock' => $nNilaiStock,
                        'NilaiAdjustment' => $nNilaiAdjustment
                    ];
                }

                $currentPage++;
            } while (count($vaDataQuery) > 0); // Lanjutkan hingga tidak ada data lagi

            // Hitung total dan proses hasil yang telah didapat
            $vaResult = [
                'data' => $vaArray,
                // Total dan proses selanjutnya
            ];

            
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
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

    function getSaldoAwal($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoAwal')
            ->where('STATUS', '=', Upd::KR_SALDOAWAL)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoAwal = $d->SaldoAwal;
        }
        return $nSaldoAwal;
    }

    function getSaldoPembelian($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoPembelian')
            ->where('STATUS', '=', Upd::KR_PEMBELIAN)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoPembelian = $d->SaldoPembelian;
        }
        return $nSaldoPembelian;
    }

    function getSaldoRtnPembelian($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoRtnPembelian')
            ->where('STATUS', '=', Upd::KR_RETUR_PEMBELIAN)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoRtnPembelian = $d->SaldoRtnPembelian;
        }
        return $nSaldoRtnPembelian;
    }

    function getSaldoPenjualan($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoPenjualan')
            ->where('STATUS', '=', Upd::KR_PENJUALAN)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoPenjualan = $d->SaldoPenjualan;
        }
        return $nSaldoPenjualan;
    }

    function getSaldoRtnPenjualan($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoRtnPenjualan')
            ->where('STATUS', '=', Upd::KR_RETUR_PENJUALAN)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoRtnPenjualan = $d->SaldoRtnPenjualan;
        }
        return $nSaldoRtnPenjualan;
    }

    function getSaldoMutasiKe($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoMutasiKe')
            ->where('STATUS', '=', Upd::KR_MUTASISTOKKE)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoMutasiKe = $d->SaldoMutasiKe;
        }
        return $nSaldoMutasiKe;
    }

    function getSaldoMutasiDari($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as SaldoMutasiDari')
            ->where('STATUS', '=', Upd::KR_MUTASISTOKDARI)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoMutasiDari = $d->SaldoMutasiDari;
        }
        return $nSaldoMutasiDari;
    }

    function getSaldoAdjustment($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Debet - Kredit),0) as SaldoAdjustment')
            ->where('STATUS', '=', Upd::KR_STOCKOPNAME)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoAdjustment = $d->SaldoAdjustment;
        }
        return $nSaldoAdjustment;
    }

    function getSaldoPackingMasuk($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Debet),0) as SaldoPackingMasuk')
            ->where('STATUS', '=', Upd::KR_PACKING)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoPackingMasuk = $d->SaldoPackingMasuk;
        }
        return $nSaldoPackingMasuk;
    }

    function getSaldoPackingKeluar($cKode, $dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Kredit),0) as SaldoPackingKeluar')
            ->where('STATUS', '=', Upd::KR_PACKING)
            ->where('KODE', '=', $cKode)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nSaldoPackingKeluar = $d->SaldoPackingKeluar;
        }
        return $nSaldoPackingKeluar;
    }

    function getTotalSaldoAwal($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoAwal')
            ->where('STATUS', '=', Upd::KR_SALDOAWAL)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoAwal = $d->TotalSaldoAwal;
        }
        return $nTotalSaldoAwal;
    }

    function getTotalSaldoPembelian($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoPembelian')
            ->where('STATUS', '=', Upd::KR_PEMBELIAN)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoPembelian = $d->TotalSaldoPembelian;
        }
        return $nTotalSaldoPembelian;
    }

    function getTotalSaldoRtnPembelian($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoRtnPembelian')
            ->where('STATUS', '=', Upd::KR_RETUR_PEMBELIAN)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoRtnPembelian = $d->TotalSaldoRtnPembelian;
        }
        return $nTotalSaldoRtnPembelian;
    }

    function getTotalSaldoPenjualan($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoPenjualan')
            ->where('STATUS', '=', Upd::KR_PENJUALAN)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoPenjualan = $d->TotalSaldoPenjualan;
        }
        return $nTotalSaldoPenjualan;
    }

    function getTotalSaldoRtnPenjualan($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoRtnPenjualan')
            ->where('STATUS', '=', Upd::KR_RETUR_PENJUALAN)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoRtnPenjualan = $d->TotalSaldoRtnPenjualan;
        }
        return $nTotalSaldoRtnPenjualan;
    }

    function getTotalSaldoMutasiKe($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoMutasiKe')
            ->where('STATUS', '=', Upd::KR_MUTASISTOKKE)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoMutasiKe = $d->TotalSaldoMutasiKe;
        }
        return $nTotalSaldoMutasiKe;
    }

    function getTotalSaldoMutasiDari($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(QTY),0) as TotalSaldoMutasiDari')
            ->where('STATUS', '=', Upd::KR_MUTASISTOKDARI)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoMutasiDari = $d->TotalSaldoMutasiDari;
        }
        return $nTotalSaldoMutasiDari;
    }

    function getTotalSaldoAdjustment($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Debet - Kredit),0) as TotalSaldoAdjustment')
            ->where('STATUS', '=', Upd::KR_STOCKOPNAME)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoAdjustment = $d->TotalSaldoAdjustment;
        }
        return $nTotalSaldoAdjustment;
    }

    function getTotalSaldoPackingMasuk($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Debet),0) as TotalSaldoPackingMasuk')
            ->where('STATUS', '=', Upd::KR_PACKING)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoPackingMasuk = $d->TotalSaldoPackingMasuk;
        }
        return $nTotalSaldoPackingMasuk;
    }

    function getTotalSaldoPackingKeluar($dTgl, $cGudang)
    {
        $vaData = DB::table('kartustock')
            ->selectRaw('IFNULL(SUM(Kredit),0) as TotalSaldoPackingKeluar')
            ->where('STATUS', '=', Upd::KR_PACKING)
            ->where('GUDANG', '=', $cGudang)
            ->where('TGL', '<=', $dTgl)
            ->get();
        foreach ($vaData as $d) {
            $nTotalSaldoPackingKeluar = $d->TotalSaldoPackingKeluar;
        }
        return $nTotalSaldoPackingKeluar;
    }
}
