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
 * Created on Tue Dec 12 2023 - 10:37:01
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Helpers;

use App\Helpers\Func\Date;
use App\Models\fun\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerhitunganPinjaman
{
    public static function getPenyusutan($cKode, $dTgl)
    {
        try {
            $va = [
                "TglPenyusutan" => "",
                "Awal" => 0,
                "BulanIni" => 0,
                "Akhir" => 0,
                "Ke" => 0
            ];
            $vaData = DB::table('aktiva')
                ->select(
                    'TglPerolehan',
                    'TglPenyusutan',
                    'HargaPerolehan',
                    'Residu',
                    'JenisPenyusutan',
                    'Lama',
                    'StartPenyusutan',
                    'PenyusutanAwal',
                    'PenyusutanPerBulan',
                    'Status'
                )
                ->where('Kode', '=', $cKode)
                ->first();
            if ($vaData) {
                $dTglAwal = $vaData->TglPenyusutan;
                if ($vaData->StartPenyusutan > 0) {
                    $dTglAwal = date('Y-m-d', Func::nextMonth(Func::Tgl2Time($vaData->TglPerolehan), $vaData->StartPenyusutan));
                }
                $nKe = GetterSetter::getKe($dTglAwal, $dTgl, $vaData->Lama) + 1;
                $nPenyusutanAwal = $vaData->PenyusutanAwal;
                $nHargaPerolehan = $vaData->HargaPerolehan - $vaData->Residu;
                if ($vaData->PenyusutanPerBulan > 0) {
                    $nPenyusutan = $vaData->PenyusutanPerBulan;
                } else {
                    $nPenyusutan = round(Func::Devide($nHargaPerolehan, $vaData->Lama), 0);
                }
                if ($vaData->Status == '2') {
                    $va['Awal'] = 0;
                    $va['Akhir'] = 0;
                    $va['BulanIni'] = 0;
                    $va['Ke'] = $vaData->Lama;
                } else {
                    if ($nKe < $vaData->Lama) {
                        $va['Awal'] = (Func::modAktiva($nPenyusutan) * ($nKe - 1)) + $nPenyusutanAwal;
                        $va['BulanIni'] = Func::modAktiva($nPenyusutan);
                        $va['Akhir'] = Func::modAktiva($va['Awal']) + Func::modAktiva($va['BulanIni']);
                        if ($va['Akhir'] >= $nHargaPerolehan) {
                            $va['Awal'] = min(((Func::modAktiva($nPenyusutan) * ($nKe - 1)) + $nPenyusutanAwal), $nHargaPerolehan);
                            $va['Akhir'] = $nHargaPerolehan;
                            $va['BulanIni'] = max($nHargaPerolehan - $va['Awal'], 0);
                        }
                        $va["Ke"] = $nKe;
                    } else if ($nKe == $vaData->Lama) {
                        $va['Awal'] = (Func::modAktiva($nPenyusutan) * ($nKe - 1)) + $nPenyusutanAwal;
                        $va['Akhir'] = Func::modAktiva($nHargaPerolehan);
                        $va['BulanIni'] = Func::modAktiva($va['Akhir']) - Func::modAktiva($va['Awal']) + $nPenyusutanAwal;
                        $va['Ke'] = $nKe;
                    } else {
                        $va['Awal'] = Func::modAktiva($nHargaPerolehan);
                        $va['Akhir'] = Func::modAktiva($nHargaPerolehan);
                        $va['BulanIni'] = 0;
                        $va['Ke'] = $vaData->Lama;
                    }
                }
            }
            return $va;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function getAnuitasEfektif($nSukuBunga, $nPlafond, $nLama, $nKe)
    {
        $vaResult = [
            "Pokok" => 0,
            "Bunga" => 0
        ];
        if ($nSukuBunga > 0 && $nPlafond > 0 && $nLama > 0) {
            $nAngsPerBulan = ($nPlafond * ($nSukuBunga / 12 / 100)) / (1 - 1 / pow((1 + $nSukuBunga / 12 / 100), $nLama));
            for ($i = 0; $i < $nKe; $i++) {
                $nBunga = $nPlafond * $nSukuBunga / 12 / 100;
                $nPokok = $nAngsPerBulan - $nBunga;
                $nPlafond -= $nPokok;
                $vaResult['Pokok'] = Func::RoundUp($nPokok, 1);
                $vaResult['Bunga'] = Func::RoundUp($nBunga, 1);
            }
        }
        return $vaResult;
    }

}
