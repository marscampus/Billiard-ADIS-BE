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
 * Created on Tue May 14 2024 - 07:40:07
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\transaksistock;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\fun\Jurnal;
use App\Models\fun\KartuStock;
use App\Models\master\Stock;
use App\Models\transaksistock\StockOpname;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockOpnameController extends Controller
{
    public function data(Request $request)
    {
        $vaValidator = Validator::make($request->all(), [
            'Tgl' => 'required|date',
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
            $dTgl = $request->Tgl;
            $cGudang = $request->Gudang;
            $vaData = DB::table('stock_opname as so')
                ->select(
                    'so.Barcode as BARCODE',
                    'so.Kode as KODE',
                    'so.Nama as NAMA_PRODUK',
                    's.Satuan as SATUAN',
                    's.HB as HARGABELI',
                    'sh.HP as HARGAPOKOK',
                    's.HJ as HARGAJUAL',
                    'so.QtyAkhir as STOCK_AKHIR',
                    'so.QtyOpname as QTY_OPNAME'
                )
                ->leftJoin('stock as s', 's.Kode', '=', 'so.Kode')
                ->leftJoin('stock_hp as sh', 'sh.Kode', '=', 'so.Kode')
                ->where('so.Tgl', '=', $dTgl)
                ->where('so.Gudang', '=', $cGudang)
                ->groupBy('so.Kode')
                ->get();
            // Calculate totals
            $nTotalHPP = $vaData->sum('HARGAPOKOK');
            $nTotalStockAkhir = $vaData->sum('STOCK_AKHIR');
            $nTotalQtyOpname = $vaData->sum('QTY_OPNAME');
            $vaTotal = [
                'totalHPP' => $nTotalHPP,
                'totalStockAkhir' => $nTotalStockAkhir,
                'totalQtyOpname' => $nTotalQtyOpname
            ];
            // JIKA REQUEST SUKSES
            if ($vaData) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $vaData,
                    'total' => $vaTotal,
                    'total_data' => count($vaData),
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

    public function store(Request $request)
    {
        ini_set('max_execution_time', 0);
        DB::beginTransaction();
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cEmail = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        try {
            $dTgl = $vaRequestData['Tgl'];
            $cGudang = $vaRequestData['Gudang'];
            // Menghapus data yang memiliki tanggal sama dengan $dTgl dan gudang sama dengan $cGudang
            StockOpname::where('Tgl', '=', $dTgl)->where('Gudang', '=', $cGudang)
                ->chunk(200, function ($stock) {
                    foreach ($stock as $record) {
                        $record->delete();
                    }
                });
            $vaStockOpname = $request->input('data');
            if (!empty($vaStockOpname)) {
                foreach ($vaStockOpname as $v) {
                    $cKode = $v['KODE'];
                    $nQtyOpname = 0;
                    $vaData2 = DB::table('stock_opname')
                        ->where('Kode', '=', $cKode)
                        ->where('Tgl', '=', $dTgl)
                        ->where('Gudang', '=', $cGudang)
                        ->exists();
                    if ($vaData2) {
                        $vaData3 = DB::table('stock_opname')
                            ->select('QTYOPNAME')
                            ->where('Kode', '=', $cKode)
                            ->where('Tgl', '=', $dTgl)
                            ->where('Gudang', '=', $cGudang)
                            ->get();
                        foreach ($vaData3 as $d3) {
                            $nQtyOpname += $d3->QTYOPNAME;

                            $vaArray = [
                                "QTYOPNAME" => round($nQtyOpname + $v['QTY_OPNAME']) ?? 0,
                                "USEROPNAME" => $cEmail,
                                "DATETIME" => Carbon::now()
                            ];
                            StockOpname::where('KODE', '=', $cKode)
                                ->where('Gudang', '=', $cGudang)
                                ->where('Tgl', '=', $dTgl)
                                ->update($vaArray);
                        }
                    } else {
                        $vaArray = [
                            "TGL" => $dTgl,
                            "GUDANG" => $cGudang,
                            "BARCODE" => $v['BARCODE'],
                            "KODE" => $cKode,
                            "NAMA" => $v['NAMA_PRODUK'],
                            "QTYAKHIR" => round($v['STOCK_AKHIR']),
                            "QTYOPNAME" => round($v['QTY_OPNAME']),
                            "USEROPNAME" => $cEmail,
                            "DATETIME" => Carbon::now()
                        ];
                        StockOpname::create($vaArray);
                    }
                }
                DB::commit();
                // JIKA REQUEST SUKSES
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Menyimpan Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data Tidak Ditemukan',
                'datetime' => date('Y-m-d H:i:s'),
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cEmail = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        try {
            $dTgl = $vaRequestData['Tgl'];
            $cGudang = $vaRequestData['Gudang'];
            $vaDataTabel = $vaRequestData['data'];
            $cBarcode = $vaDataTabel['BARCODE'];
            $cKode = $vaDataTabel['KODE'];
            StockOpname::where('Tgl', '=', $dTgl)
                ->where('Gudang', '=', $cGudang)
                ->where('Barcode', '=', $cBarcode)
                ->where('Kode', '=', $cKode)
                ->delete();
            $vaArray = [
                "TGL" => $dTgl,
                "GUDANG" => $cGudang,
                "BARCODE" => $cBarcode,
                "KODE" => $cKode,
                "NAMA" => $vaDataTabel['NAMA_PRODUK'],
                "QTYAKHIR" => round($vaDataTabel['STOCK_AKHIR']),
                "QTYOPNAME" => round($vaDataTabel['QTY_OPNAME']),
                "USEROPNAME" => $cEmail,
                "DATETIME" => Carbon::now()
            ];
            StockOpname::create($vaArray);
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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

    public function prosesAdjustment(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cEmail = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        try {
            $dTgl = $vaRequestData['Tgl'];
            $cGudang = $vaRequestData['Gudang'];
            $vaDataTabel = $vaRequestData['data'];
            // STOCK_OPNAME
            StockOpname::where('Tgl', '=', $dTgl)->where('Gudang', '=', $cGudang)
                ->chunk(200, function ($stock) {
                    foreach ($stock as $record) {
                        $record->delete();
                    }
                });
            if (!empty($vaDataTabel)) {
                foreach ($vaDataTabel as $v) {
                    $cKode = $v['KODE'];
                    $nQtyOpname = 0;
                    $vaData2 = DB::table('stock_opname')
                        ->where('Kode', '=', $cKode)
                        ->where('Tgl', '=', $dTgl)
                        ->where('Gudang', '=', $cGudang)
                        ->exists();
                    if ($vaData2) {
                        $vaData3 = DB::table('stock_opname')
                            ->select('QTYOPNAME')
                            ->where('Kode', '=', $cKode)
                            ->where('Tgl', '=', $dTgl)
                            ->where('Gudang', '=', $cGudang)
                            ->get();
                        foreach ($vaData3 as $d3) {
                            $nQtyOpname += $d3->QTYOPNAME;

                            $vaArray = [
                                "QTYOPNAME" => round($nQtyOpname + $v['QTY_OPNAME']) ?? 0,
                                "USEROPNAME" => $cEmail,
                                "DATETIME" => Carbon::now()
                            ];
                            StockOpname::where('KODE', '=', $cKode)
                                ->where('Gudang', '=', $cGudang)
                                ->where('Tgl', '=', $dTgl)
                                ->update($vaArray);
                        }
                    } else {
                        $vaArray = [
                            "TGL" => $dTgl,
                            "GUDANG" => $cGudang,
                            "BARCODE" => $v['BARCODE'],
                            "KODE" => $cKode,
                            "NAMA" => $v['NAMA_PRODUK'],
                            "QTYAKHIR" => round($v['STOCK_AKHIR']),
                            "QTYOPNAME" => round($v['QTY_OPNAME']),
                            "USEROPNAME" => $cEmail,
                            "DATETIME" => Carbon::now()
                        ];
                        StockOpname::create($vaArray);
                    }
                }
                DB::commit();
            }
            // KARTUSTOCK
            $cStatus = Upd::KR_STOCKOPNAME;
            KartuStock::where('STATUS', '=', $cStatus)
                ->where('GUDANG', '=', $cGudang)
                ->where('TGL', '=', $dTgl)
                ->delete();
            foreach ($vaDataTabel as $d) {
                $cFaktur = GetterSetter::getLastFaktur("SO", 8);
                $nQtyOpname = $d['QTY_OPNAME'];
                $nQtyAkhir = $d['STOCK_AKHIR'];
                $nQty = abs($nQtyAkhir - $nQtyOpname);
                $cKode = $d['KODE'];
                if ($nQtyOpname < $nQtyAkhir) {
                    Upd::updKartuStock(
                        $cStatus,
                        $cFaktur,
                        $dTgl,
                        $cGudang,
                        $cKode,
                        $d['SATUAN'],
                        $nQty,
                        'K',
                        'Stock Opname an. ' . $d['NAMA_PRODUK'],
                        0,
                        0,
                        0,
                        0,
                        0,
                    );
                } else if ($nQtyOpname > $nQtyAkhir || $nQtyAkhir < 0) {
                    Upd::updKartuStock(
                        $cStatus,
                        $cFaktur,
                        $dTgl,
                        $cGudang,
                        $cKode,
                        $d['SATUAN'],
                        $nQty,
                        'D',
                        'Stock Opname an. ' . $d['NAMA_PRODUK'],
                        0,
                        0,
                        0,
                        0,
                        0,
                    );
                }
                GetterSetter::setLastKodeRegister('SO');
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Perubahan',
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

    public function batalAdjustment(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        unset($vaRequestData['page']);
        try {
            $dTgl = $vaRequestData['Tgl'];
            $cGudang = $vaRequestData['Gudang'];
            $cStatus = Upd::KR_STOCKOPNAME;
            KartuStock::where('STATUS', '=', $cStatus)
                ->where('GUDANG', '=', $cGudang)
                ->where('TGL', '=', $dTgl)
                ->delete();
            StockOpname::where('GUDANG', '=', $cGudang)
                ->where('TGL', '=', $dTgl)
                ->delete();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Perubahan',
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

    public function postingJurnalStockOpname(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cEmail = $vaRequestData['auth']['email'];
        try {
            $nTotalStockOpname = 0;
            $nTotalHpp = 0;
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $dTgl = $vaRequestData['Tgl'];
            $dTglBulanLalu = Func::MundurSatuBulanDanAmbilEOM($dTgl);
            $cGudang = $vaRequestData['Gudang'];
            $vaDataTabel = $vaRequestData['data'];
            foreach ($vaDataTabel as $d) {
                $nStockOpname = $d['QTY_OPNAME'];
                // $nHpp = $d['HARGAPOKOK'];                
                $nHpp = 100000;
                // Mengtotal
                $nTotalStockOpname += $nStockOpname;
                $nTotalHpp += $nHpp;
            }
            $nSaldoBulanLalu = GetterSetter::getSaldoAwalTnpGab(
                Carbon::create($dTglBulanLalu)->format('Y-m-d'),
                GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                "",
                "",
            );
            // dd(GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'));
            $nSaldoAkhir = $nTotalStockOpname * $nTotalHpp;

            // return response()->json([
            //     'status' => self::$status['SUKSES'],
            //     'message' => 'Berhasil Menyimpan Data',
            //     'saldoakhir' => $nSaldoAkhir,
            //     'saldobulanlalu' => $nSaldoBulanLalu,
            //     'datetime' => date('Y-m-d H:i:s'),
            // ], 200);

            // Mulai Update ke Jurnal dan Buku Besar
            $cFaktur = GetterSetter::getLastFaktur('SO', 8);
            BukuBesar::where('Tgl', '=', $dTgl)->where('Faktur', 'like', "SO%")->delete();
            Jurnal::where('Tgl', '=', $dTgl)->where('Faktur', 'like', "SO%")->delete();

            dd(GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'));
            // $cRekeningAsetNilaiPersediaan=
            
            // Upd Buku Besar
            Upd::updBukuBesar(
                $cFaktur,
                $cGudang,
                $dTgl,
                GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                'Aset Nilai Persediaan Bulan Ini ' . Func::EOM($dTgl),
                $nSaldoAkhir,
                0,
                Upd::KR_STOCKOPNAME,
                ''
            );
            Upd::updBukuBesar(
                $cFaktur,
                $cGudang,
                $dTgl,
                GetterSetter::getDBConfig('rek_hpp_toko'),
                'HPP Bulan Ini ' . Func::EOM($dTgl),
                0,
                $nSaldoAkhir,
                Upd::KR_STOCKOPNAME,
                ''
            );
            Upd::updBukuBesar(
                $cFaktur,
                $cGudang,
                $dTgl,
                GetterSetter::getDBConfig('rek_hpp_toko'),
                'HPP Bulan Lalu ' . Func::MundurSatuBulanDanAmbilEOM($dTgl),
                $nSaldoBulanLalu,
                0,
                Upd::KR_STOCKOPNAME,
                ''
            );
            Upd::updBukuBesar(
                $cFaktur,
                $cGudang,
                $dTgl,
                GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                'Aset Nilai Persediaan Bulan Lalu ' . Func::MundurSatuBulanDanAmbilEOM($dTgl),
                0,
                $nSaldoBulanLalu,
                Upd::KR_STOCKOPNAME,
                ''
            );
            // Upd Jurnal
            Upd::updJurnal(
                $cFaktur,
                $dTgl,
                GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                'Aset Nilai Persediaan Bulan Ini ' . Func::EOM($dTgl),
                $nSaldoAkhir,
                0
            );
            Upd::updJurnal(
                $cFaktur,
                $dTgl,
                GetterSetter::getDBConfig('rek_hpp_toko'),
                'HPP Bulan Ini ' . Func::EOM($dTgl),
                0,
                $nSaldoAkhir
            );
            Upd::updJurnal(
                $cFaktur,
                $dTgl,
                GetterSetter::getDBConfig('rek_hpp_toko'),
                'HPP Bulan Lalu ' . Func::MundurSatuBulanDanAmbilEOM($dTgl),
                $nSaldoBulanLalu,
                0
            );
            Upd::updJurnal(
                $cFaktur,
                $dTgl,
                GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                'Aset Nilai Persediaan Bulan Lalu ' . Func::MundurSatuBulanDanAmbilEOM($dTgl),
                0,
                $nSaldoBulanLalu
            );
            GetterSetter::setLastKodeRegister('SO');
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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
