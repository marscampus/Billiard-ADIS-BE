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
 * Created on Mon Jul 22 2024 - 06:35:46
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanpenjualan;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanPerBarangController extends Controller
{
    public function data(Request $request)
    {
        try {

            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $vaData = DB::table('penjualan as p')
                ->select(
                    'p.Tgl',
                    'p.Kode',
                    's.Kode_Toko',
                    's.Nama',
                    DB::raw('IFNULL(SUM(p.QTY), 0) AS QtyTerjual')
                )
                ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
                ->whereBetween('p.Tgl', [$dTglAwal, $dTglAkhir]);
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    if (!empty($filterValue)) {
                        $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                    }
                }
            }
            $vaData->groupBy('s.Kode');
            $vaData->orderBy('p.Tgl', 'desc');
            $vaData = $vaData->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaData,
                'total_data' => count($vaData),
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
