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
 * Created on Fri May 24 2024 - 03:06:20
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanstock;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapMutasiAntarGudangController extends Controller
{
    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser =  $vaRequestData['auth']['name'];
        try {
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $cStatus = $vaRequestData['Status'];
            $nNo = 0;
            $vaArray = [];
            $nQtyKirim = 0;
            unset($vaRequestData['auth']);
            $vaData = DB::table('mutasigudang_ke as mk')
                ->select(
                    'mk.FAKTUR as FakturKirim',
                    'mk.TGL AS TglKirim',
                    'mk.STATUS',
                    'g1.KETERANGAN AS GudangKirim',
                    'g2.KETERANGAN AS GudangTerima',
                    DB::raw('SUM(mk.QTY) AS QtyKirim'),
                    'mk.USERNAME AS UserKirim',
                    'mk.DATETIME',
                    'md.FAKTUR AS FakturTerima',
                    'md.TGL AS TglTerima',
                    DB::raw('SUM(md.QTY) AS QtyTerima'),
                    'md.USERNAME AS UserTerima'
                )
                ->leftJoin('mutasigudang_dari as md', 'md.FAKTUR_KIRIM', '=', 'mk.FAKTUR')
                ->leftJoin('gudang as g1', 'g1.KODE', '=', 'mk.GUDANG_KIRIM')
                ->leftJoin('gudang as g2', 'g2.KODE', '=', 'mk.GUDANG_TERIMA')
                ->whereBetween('mk.TGL', [$dTglAwal, $dTglAkhir]);
            if ($cStatus === 'B') {
                $vaData->where('mk.STATUS', '=', '1');
            } elseif ($cStatus === 'C') {
                $vaData->where('mk.STATUS', '=', '0');
            }
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    if (!empty($filterValue)) {
                        $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                    }
                }
            }
            $vaData->groupBy('mk.FAKTUR');
            $vaData = $vaData->get();
            foreach ($vaData as $d) {
                $nNo++;
                $nQtyKirim = $d->QtyKirim;
                $cKet = 'Sudah Diterima';
                if ($d->STATUS === '0') {
                    $cKet = 'Belum Diterima';
                }
                $vaArray[] = [
                    'No' => $nNo,
                    'FakturKirim' => $d->FakturKirim,
                    'FakturTerima' => $d->FakturTerima,
                    'TglKirim' => $d->TglKirim,
                    'TglTerima' => $d->TglTerima,
                    'Status' => $cKet,
                    'GudangKirim' => $d->GudangKirim,
                    'GudangTerima' => $d->GudangTerima,
                    'QtyKirim' => $d->QtyKirim,
                    'QtyTerima' => $d->QtyTerima,
                    'UserKirim' => $d->UserKirim,
                    'UserTerima' => $d->UserTerima,
                    'DateTime' => $d->DATETIME
                ];
            }
            $vaResult = [
                'data' => $vaArray,
                'total' => $nQtyKirim,
                'total_data' => count($vaArray)
            ];

            // If request is successful
            if ($vaResult) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Lap Mutasi Antar Gudang', 'data', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            }
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Lap Mutasi Antar Gudang', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }

    public function dataFaktur(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser =  $vaRequestData['auth']['name'];
        try {
            $cFaktur = $vaRequestData['Faktur'];
            unset($vaRequestData['auth']);
            if (strpos($cFaktur, 'BK') !== false) {
                $vaData = DB::table('mutasigudang_ke as mk')
                    ->select(
                        'mk.Tgl',
                        'mk.Kode',
                        's.Nama',
                        'mk.Qty',
                        'mk.Satuan',
                        's.HJ'
                    )
                    ->leftJoin('stock as s', 's.Kode_Toko', '=', 'mk.Kode')
                    ->where('mk.Faktur', '=', $cFaktur)
                    ->get();
                $nQty = 0;
                $nHJ = 0;
                $vaArray = [];
                $nNo = 0;
                $nGrandTotalHJ = 0;
                foreach ($vaData as $d) {
                    $nNo++;
                    $nQty += $d->Qty;
                    $nHJ += $d->HJ;
                    $nTotalHJ = $nQty * $nHJ;
                    $vaArray[] = [
                        'No' => $nNo,
                        'Kode' => $d->Kode,
                        'Nama' => $d->Nama,
                        'Qty' => $d->Qty,
                        'Satuan' => $d->Satuan,
                        'HJ' => $d->HJ,
                        'TotalHJ' => $nTotalHJ
                    ];
                    $nGrandTotalHJ += $nTotalHJ;
                }
            } elseif (strpos($cFaktur, 'BA') !== false) {
                $vaData = DB::table('mutasigudang_dari as mk')
                    ->select(
                        'mk.Tgl',
                        'mk.Kode',
                        's.Nama',
                        'mk.Qty',
                        'mk.Satuan',
                        's.HJ'
                    )
                    ->leftJoin('stock as s', 's.Kode_Toko', '=', 'mk.Kode')
                    ->where('mk.Faktur', '=', $cFaktur)
                    ->get();
                $nQty = 0;
                $nHJ = 0;
                $vaArray = [];
                $nNo = 0;
                $nGrandTotalHJ = 0;
                foreach ($vaData as $d) {
                    $nNo++;
                    $nQty += $d->Qty;
                    $nHJ += $d->HJ;
                    $nTotalHJ = $nQty * $nHJ;
                    $vaArray[] = [
                        'No' => $nNo,
                        'Kode' => $d->Kode,
                        'Nama' => $d->Nama,
                        'Qty' => $d->Qty,
                        'Satuan' => $d->Satuan,
                        'HJ' => $d->HJ,
                        'TotalHJ' => $nTotalHJ
                    ];
                    $nGrandTotalHJ += $nTotalHJ;
                }
            } else {
                $vaRetVal = [
                    "status" => "99",
                    "message" => "Faktur Terima Tidak Ada!"
                ];
                Func::writeLog('Lap Mutasi Antar Gudang', 'dataFaktur', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaRetVal);
            }
            $vaResult = [
                'tgl' => $d->Tgl,
                'data' => $vaArray,
                'total' => $nQty,
                'total_data' => count($vaArray),
                'total_HJ' => $nGrandTotalHJ
            ];
            // If request is successful
            if ($vaResult) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Lap Mutasi Antar Gudang', 'dataFaktur', $vaRequestData, $vaRetVal, $cUser);
                return response()->json($vaResult);
            }
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            $vaRetVal = [
                "status" => "99",
                "message" => "REQUEST TIDAK VALID",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Lap Mutasi Antar Gudang', 'dataFaktur', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
