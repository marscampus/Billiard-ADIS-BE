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
 * Created on Mon Jun 03 2024 - 07:34:09
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanstock;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapExpiredController extends Controller
{

    public function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser =  $vaRequestData['auth']['name'];
        try {
            $dBulan = $vaRequestData['Bulan'];
            $dTahun = $vaRequestData['Tahun'];
            $cFilterData = $vaRequestData['FilterData'];

            // Calculate the end date
            $endDate = Carbon::createFromDate($dTahun, $dBulan, 1)->addMonths(1)->subDay();

            // Calculate the date 6 months from now
            $dateSixMonthsEarlier = $endDate->copy()->addMonths(6)->format('Y-m-d');

            $vaData = DB::table('stock_expired2 as se')
                ->select(
                    'se.Kode',
                    's.Kode_Toko',
                    's.Nama',
                    'se.Expired',
                    DB::raw('IFNULL(SUM(DEBET - KREDIT),0) as SisaStock')
                )
                ->leftJoin('stock as s', 's.Kode', '=', 'se.Kode')
                ->leftJoin('kartustock AS ks', function ($join) {
                    $join->on('ks.KODE', '=', 's.KODE')
                        ->on('ks.EXPIRED', '=', 'se.EXPIRED');
                });

            // Apply the filter based on $cFilterData
            if ($cFilterData === 'B') {
                $vaData->whereBetween('se.Expired', [$endDate, $dateSixMonthsEarlier]);
            } elseif ($cFilterData === 'U') {
                $vaData->where('se.Expired', '<', $endDate);
            }

            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    if (!empty($filterValue)) {
                        $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                    }
                }
            }

            $vaData->groupBy('s.Kode', 's.Kode_Toko', 's.Nama', 'se.Expired');
            $vaData = $vaData->get();
            $nNo = 0;
            $vaArray = [];
            foreach ($vaData as $d) {
                $nNo++;
                $vaArray[] = [
                    'No' => $nNo,
                    'Kode' => $d->Kode,
                    'KodeToko' => $d->Kode_Toko,
                    'Nama' => $d->Nama,
                    'Expired' => $d->Expired,
                    'SisaStock' => $d->SisaStock
                ];
            }

            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaArray)
            ];

            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => $vaResult
            ];
            Func::writeLog('Lap Expired', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaResult);
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
            Func::writeLog('Lap Expired', 'data', $vaRequestData, $vaRetVal, $cUser);
            return response()->json($vaRetVal);
            return response()->json(['status' => 'error']);
        }
    }
}
