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
 * Created on Thu Jun 20 2024 - 03:18:34
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanpenjualan;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SisaPembayaranPiutangController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['email'];
            $mandatoryKey = [
                'TglAwal',
                'TglAkhir'
            ];
            $vaRequestData = Func::filterArrayClean($vaRequestData, $mandatoryKey);
            if (Func::filterArrayValue($vaRequestData, $mandatoryKey) === false) return [];
            foreach ($mandatoryKey as $val) {
                $$val = $vaRequestData[$val];
            }
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $vaArray = [];

            $nGrandTotalPiutang = 0;
            $nGrandSisaPiutang = 0;
            $vaData = DB::table('kartupiutang as kp')
                ->select(
                    'kp.Member',
                    'dt.Nama',
                    'kp.JthTmp',
                    'g.Keterangan as Gudang',
                    DB::raw('IFNULL(SUM(kp.Debet),0) as Total'),
                    DB::raw('IFNULL(SUM(kp.Debet - kp.Kredit),0) as Sisa')
                )
                ->leftJoin('debitur_toko as dt', 'dt.Kode', '=', 'kp.Member')
                ->leftJoin('gudang as g', 'g.Kode', '=', 'kp.Gudang')
                ->whereBetween('kp.Tgl', [$TglAwal, $TglAkhir]);
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    if (!empty($filterValue)) {
                        $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                    }
                }
            }
            $vaData->groupBy('kp.Member');
            $vaData->havingRaw('Sisa > 0');
            $vaData = $vaData->get();
            foreach ($vaData as $d) {
                $vaArray[] = [
                    'Member' => $d->Member,
                    'NamaMember' => $d->Nama,
                    'JthTmp' => $d->JthTmp,
                    'TotalPiutang' => $d->Total,
                    'SisaPiutang' => $d->Sisa
                ];
                $nGrandTotalPiutang += $d->Total;
                $nGrandSisaPiutang += $d->Sisa;
            }
            $vaTotal = [
                'GrandTotalPiutang' => $nGrandTotalPiutang,
                'GrandSisaPiutang' => $nGrandSisaPiutang
            ];
            $vaResult = [
                'data' => $vaArray,
                'total' => $vaTotal,
                'total_data' => count($vaArray)
            ];
            if ($vaResult) {
                $vaRetVal = [
                    "status" => "00",
                    "message" => $vaResult
                ];
                Func::writeLog('Lap Sisa Pembayaran Piutang', 'data', $vaRequestData, $vaRetVal, $cUser);
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
            Func::writeLog('Lap Sisa Pembayaran Piutang', 'data', $vaRequestData, $vaRetVal, $cUser);
            // return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
