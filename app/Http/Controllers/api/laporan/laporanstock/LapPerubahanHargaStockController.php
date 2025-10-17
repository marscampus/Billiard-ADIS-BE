<?php
/*
 * Copyright (C) Godong
 *http://www.marstech.co.id
 *Email. info@marstech.co.id
 *Telp. 0811-3636-09k
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
 * Created on Wed May 22 2024 - 08:45:47
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanstock;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapPerubahanHargaStockController extends Controller
{
    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        try {
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            unset($vaRequestData['auth']);
            $vaData = DB::table('perubahanhargastock as p')
                ->select(
                    'p.TANGGAL_PERUBAHAN',
                    'p.KODE',
                    's.KODE_TOKO',
                    's.NAMA',
                    'p.FAKTUR as FAKTURPEMBELIAN',
                    't.PO as FAKTURPO',
                    't.FAKTURASLI',
                    'sp.NAMA as SUPPLIER',
                    'p.HBLAMA',
                    'p.HJLAMA',
                    'p.HB',
                    'p.HJ',
                    'p.DATETIME'
                )
                ->leftJoin('stock as s', 's.KODE', '=', 'p.KODE')
                ->leftJoin('totpembelian as t', 't.FAKTUR', '=', 'p.FAKTUR')
                ->leftJoin('supplier as sp', 'sp.KODE', '=', 't.SUPPLIER')
                ->whereBetween('p.TANGGAL_PERUBAHAN', [$dTglAwal, $dTglAkhir]);

            $vaData = $vaData->get();

            // return $vaData;
            $nNo = 0;
            $vaArray = [];
            foreach ($vaData as $d) {
                $cKode = $d->KODE;
                $dTglPerubahan = $d->TANGGAL_PERUBAHAN;
                $nHpp = GetterSetter::getLastHP($cKode, $dTglPerubahan) ?? 0;
                $nHBLama = $d->HBLAMA;
                $nHBBaru = $d->HB;
                $nHJLama = $d->HJLAMA;
                $nHJBaru = $d->HJ;
                if ($nHBBaru != $nHBLama || $nHJBaru != $nHJLama) {
                    $nNo++;
                    $vaArray[] = [
                        'No' => $nNo,
                        'DateTime' => $d->DATETIME,
                        'Kode' => $cKode,
                        'Barcode' => $d->KODE_TOKO,
                        'Nama' => $d->NAMA,
                        'FakturPembelian' => $d->FAKTURPEMBELIAN,
                        'FakturPO' => $d->FAKTURPO,
                        'FakturAsli' => $d->FAKTURASLI,
                        'Supplier' => $d->SUPPLIER,
                        'HBLama' => $nHBLama,
                        'HJLama' => $nHJLama,
                        'HBBaru' => $nHBBaru,
                        'HJBaru' => $nHJBaru,
                        'HPP' => $nHpp
                    ];
                }
            }

            // If request is successful
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaArray,
                'total_data' => count($vaArray),
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

    public function dataFaktur(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        try {
            $cFaktur = $vaRequestData['Faktur'];
            unset($vaRequestData['auth']);
            $vaArray = []; // Inisialisasi variabel $vaArray

            if (strpos($cFaktur, 'PO') !== false) {
                $vaData = DB::table('totpo as tp')
                    ->select(
                        'tp.Faktur',
                        'tp.Tgl',
                        'tp.TglDO',
                        'tp.JthTmp',
                        'tp.SubTotal',
                        'tp.FakturAsli',
                        'tp.PPN',
                        'tp.Total',
                        'tp.Supplier',
                        's.Nama as NamaSupplier',
                        's.Alamat'
                    )
                    ->where('tp.Faktur', '=', $cFaktur)
                    ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                    ->first();
                if ($vaData) {
                    $vaArray = [
                        'Title' => "PURCHASE ORDER",
                        'FakturPO' => $cFaktur,
                        'Tgl' => $vaData->Tgl,
                        'TglDO' => $vaData->TglDO,
                        'JthTmp' => $vaData->JthTmp,
                        'FakturAsli' => $vaData->FakturAsli ?? "",
                        'SubTotal' => $vaData->SubTotal,
                        'PPN' => $vaData->PPN ?? 0,
                        'TotalFaktur' => $vaData->Total,
                        'Supplier' => $vaData->Supplier,
                        'NamaSupplier' => $vaData->NamaSupplier,
                        'Alamat' => $vaData->Alamat
                    ];
                    $vaData2 = DB::table('po as p')
                        ->select(
                            'p.Kode',
                            's.Nama',
                            'p.Harga',
                            'p.Qty',
                            'p.Discount',
                            'p.Jumlah',
                            's.Kode_Toko'
                        )
                        ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
                        ->where('p.Faktur', '=', $cFaktur)
                        ->get();
                    $nNo = 0;
                    foreach ($vaData2 as $d2) {
                        $nNo++;
                        $vaArray['detail'][] = [
                            'No' => $nNo,
                            'Kode' => $d2->Kode_Toko,
                            'Nama' => $d2->Nama,
                            'Harga' => $d2->Harga,
                            'Qty' => $d2->Qty,
                            'DiscBarang' => $d2->Discount,
                            'Total' => $d2->Jumlah
                        ];
                    }
                } else {
                    return response()->json([
                        'status' => self::$status['GAGAL'],
                        'message' => 'Faktur PB Tidak Ada!',
                        'datetime' => date('Y-m-d H:i:s')
                    ], 400);
                }
            } elseif (strpos($cFaktur, 'PB') !== false) {
                $vaData = DB::table('totpembelian as tp')
                    ->select(
                        'tp.Faktur as FakturPB',
                        'tp.PO as FakturPO',
                        'tp.Tgl',
                        'tp.JthTmp',
                        'tp.Gudang as KodeGudang',
                        'g.Keterangan as NamaGudang',
                        'tp.Discount as DiscTotal',
                        'tp.SubTotal',
                        'tp.FakturAsli',
                        'tp.PPN',
                        'tp.Total',
                        'tp.Supplier',
                        's.Nama as NamaSupplier',
                        's.Alamat'
                    )
                    ->leftJoin('gudang as g', 'g.Kode', 'tp.Gudang')
                    ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                    ->where('tp.Faktur', '=', $cFaktur)
                    ->first();
                if ($vaData) {
                    $vaArray = [
                        'Title' => "BUKTI TERIMA BARANG",
                        'FakturPB' => $cFaktur,
                        'FakturPO' => $vaData->FakturPO,
                        'Tgl' => $vaData->Tgl,
                        'JthTmp' => $vaData->JthTmp,
                        'Gudang' => "[" . $vaData->KodeGudang . "] " . $vaData->NamaGudang,
                        'DiscFaktur' => $vaData->DiscTotal,
                        'FakturAsli' => $vaData->FakturAsli ?? "",
                        'SubTotal' => $vaData->SubTotal,
                        'PPN' => $vaData->PPN ?? 0,
                        'TotalFaktur' => $vaData->Total,
                        'Supplier' => $vaData->Supplier,
                        'NamaSupplier' => $vaData->NamaSupplier,
                        'Alamat' => $vaData->Alamat
                    ];
                    $vaData2 = DB::table('pembelian as p')
                        ->select(
                            'p.Kode',
                            's.Nama',
                            'p.Harga',
                            'p.Qty',
                            'p.Discount',
                            'p.Jumlah',
                            's.Kode_Toko'
                        )
                        ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
                        ->where('p.Faktur', '=', $cFaktur)
                        ->get();
                    $nNo = 0;
                    foreach ($vaData2 as $d2) {
                        $nNo++;
                        $vaArray['detail'][] = [
                            'No' => $nNo,
                            'Kode' => $d2->Kode_Toko,
                            'Nama' => $d2->Nama,
                            'Harga' => $d2->Harga,
                            'Qty' => $d2->Qty,
                            'DiscBarang' => $d2->Discount,
                            'Total' => $d2->Jumlah
                        ];
                    }
                } else {
                    return response()->json([
                        'status' => self::$status['GAGAL'],
                        'message' => 'Faktur PB Tidak Ada!',
                        'datetime' => date('Y-m-d H:i:s')
                    ], 400);
                }
            } else {

                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Faktur Terima Tidak Ada!',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);

            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaArray,
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
}
