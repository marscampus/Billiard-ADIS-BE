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
 * Created on Fri Jul 19 2024 - 04:09:58
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabaRugiController extends Controller
{
    function data(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);

            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTglAwal = $vaRequestData['TglAwal']; // 2024-06-01
            $dTglAkhir = $vaRequestData['TglAkhir']; // 2024-06-30
            // $dTglAwal = Func::MundurSatuBulanDanAmbilEOM($dTglAwal);
            $vaArray = [];

            // Pendapatan Operasional
            $vaPendapatanOps = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_pendapatanOperasionalAwal'))
                ->where('Kode', '<=', GetterSetter::getDBConfig('rek_pendapatanOperasionalAkhir'))
                ->orderBy('Kode')
                ->get();
            foreach ($vaPendapatanOps as $pdpOps) {
                $cKode = $pdpOps->Kode;
                $cKeterangan = $pdpOps->Keterangan;
                $cJenis = $pdpOps->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            // Total Pendapatan Operasional
            $nTotalSaldoAwalPdpOps = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_pendapatanOperasionalAwal'), GetterSetter::getDBConfig('rek_pendapatanOperasionalAkhir'));
            $nTotalSaldoAkhirPdpOps = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_pendapatanOperasionalAwal'), GetterSetter::getDBConfig('rek_pendapatanOperasionalAkhir'));
            $nTotalMutasiPdpOps = $nTotalSaldoAkhirPdpOps - $nTotalSaldoAwalPdpOps;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL PENDAPATAN OPERASIONAL',
                'SaldoAwal' => $nTotalSaldoAwalPdpOps,
                'Mutasi' => $nTotalMutasiPdpOps,
                'SaldoAkhir' => $nTotalSaldoAkhirPdpOps
            ];

            // Harga Pokok Penjualan as HPP
            $vaHPP = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_hppAwal'))
                ->where('Kode', '<=', GetterSetter::getDBConfig('rek_hppAkhir'))
                ->orderBy('Kode')
                ->get();
            foreach ($vaHPP as $hpp) {
                $cKode = $hpp->Kode;
                $cKeterangan = $hpp->Keterangan;
                $cJenis = $hpp->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            // Total Harga Pokok Penjualan as HPP
            $nTotalSaldoAwalHPP = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_hppAwal'), GetterSetter::getDBConfig('rek_hppAkhir'));
            $nTotalSaldoAkhirHPP = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_hppAwal'), GetterSetter::getDBConfig('rek_hppAkhir'));
            $nTotalMutasiHPP = $nTotalSaldoAkhirHPP - $nTotalSaldoAwalHPP;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL HARGA POKOK PENJUALAN',
                'SaldoAwal' => $nTotalSaldoAwalHPP,
                'Mutasi' => $nTotalMutasiHPP,
                'SaldoAkhir' => $nTotalSaldoAkhirHPP
            ];

            // Laba Kotor
            $nTotalSaldoAwalLabaKotor = $nTotalSaldoAwalPdpOps - $nTotalSaldoAwalHPP;
            $nTotalSaldoAkhirLabaKotor = $nTotalSaldoAkhirPdpOps - $nTotalSaldoAkhirHPP;
            $nTotalMutasiLabaKotor = $nTotalMutasiPdpOps - $nTotalMutasiHPP;
            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'LABA KOTOR',
                'SaldoAwal' => $nTotalSaldoAwalLabaKotor,
                'Mutasi' => $nTotalMutasiLabaKotor,
                'SaldoAkhir' => $nTotalSaldoAkhirLabaKotor
            ];

            // Biaya Admin dan Umum
            $vaBiayaAdminUmum = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_biayaAdminDanUmumAwal'))
                ->where('Kode', '<=', GetterSetter::getDBConfig('rek_biayaAdminDanUmumAkhir'))
                ->orderBy('Kode')
                ->get();

            foreach ($vaBiayaAdminUmum as $biayaAdmUmum) {
                $cKode = $biayaAdmUmum->Kode;
                $cKeterangan = $biayaAdmUmum->Keterangan;
                $cJenis = $biayaAdmUmum->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            // Total Biaya Admin dan Umum
            $nTotalSaldoAwalBiayaDanUmum = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_biayaAdminDanUmumAwal'), GetterSetter::getDBConfig('rek_biayaAdminDanUmumAkhir'));
            $nTotalSaldoAkhirBiayaDanUmum = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_biayaAdminDanUmumAwal'), GetterSetter::getDBConfig('rek_biayaAdminDanUmumAkhir'));
            $nTotalMutasiBiayaDanUmum = $nTotalSaldoAkhirBiayaDanUmum - $nTotalSaldoAwalBiayaDanUmum;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL BIAYA ADMIN DAN UMUM',
                'SaldoAwal' => $nTotalSaldoAwalBiayaDanUmum,
                'Mutasi' => $nTotalMutasiBiayaDanUmum,
                'SaldoAkhir' => $nTotalSaldoAkhirBiayaDanUmum
            ];


            // Laba / Rugi Operasional
            $nTotalSaldoAwalLabaRugiOps = $nTotalSaldoAwalPdpOps - $nTotalSaldoAwalHPP - $nTotalSaldoAwalBiayaDanUmum;
            $nTotalSaldoAkhirLabaRugiOps = $nTotalSaldoAkhirPdpOps - $nTotalSaldoAkhirHPP - $nTotalSaldoAkhirBiayaDanUmum;
            $nTotalMutasiLabaRugiOps = $nTotalMutasiPdpOps - $nTotalMutasiHPP - $nTotalMutasiBiayaDanUmum;
            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'LABA / RUGI OPERASIONAL',
                'SaldoAwal' => $nTotalSaldoAwalLabaRugiOps,
                'Mutasi' => $nTotalMutasiLabaRugiOps,
                'SaldoAkhir' => $nTotalSaldoAkhirLabaRugiOps
            ];

            // Pendapatan Non Operasional
            $vaPendapatanNonOps = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAwal'))
                ->where('Kode', '<=', GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAkhir'))
                ->orderBy('Kode')
                ->get();

            foreach ($vaPendapatanNonOps as $pdpNonOps) {
                $cKode = $pdpNonOps->Kode;
                $cKeterangan = $pdpNonOps->Keterangan;
                $cJenis = $pdpNonOps->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            // Total Pendapatan Operasional
            $nTotalSaldoAwalPdpNonOps = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAwal'), GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAkhir'));
            $nTotalSaldoAkhirPdpNonOps = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAwal'), GetterSetter::getDBConfig('rek_pendapatanNonOperasionalAkhir'));
            $nTotalMutasiPdpNonOps = $nTotalSaldoAkhirPdpNonOps - $nTotalSaldoAwalPdpNonOps;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL PENDAPATAN NON OPERASIONAL',
                'SaldoAwal' => $nTotalSaldoAwalPdpNonOps,
                'Mutasi' => $nTotalMutasiPdpNonOps,
                'SaldoAkhir' => $nTotalSaldoAkhirPdpNonOps
            ];


            // Biaya Non Operasional
            $vaBiayaNonOps = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_biayaNonOperasionalAwal'))
                ->where('Kode', '<=', GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'))
                ->orderBy('Kode')
                ->get();

            foreach ($vaBiayaNonOps as $biayaNonOps) {
                $cKode = $biayaNonOps->Kode;
                $cKeterangan = $biayaNonOps->Keterangan;
                $cJenis = $biayaNonOps->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            // Total Biaya Non Operasional
            $nTotalSaldoAwalBiayaNonOps = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_biayaNonOperasionalAwal'), GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'));
            $nTotalSaldoAkhirBiayaNonOps = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_biayaNonOperasionalAwal'), GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'));
            $nTotalMutasiBiayaNonOps = $nTotalSaldoAkhirBiayaNonOps - $nTotalSaldoAwalBiayaNonOps;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL BIAYA NON OPERASIONAL',
                'SaldoAwal' => $nTotalSaldoAwalBiayaNonOps,
                'Mutasi' => $nTotalMutasiBiayaNonOps,
                'SaldoAkhir' => $nTotalSaldoAkhirBiayaNonOps
            ];

            // Laba / Rugi Non Operasional
            $nTotalSaldoAwalLabaRugiNonOps = $nTotalSaldoAwalPdpNonOps - $nTotalSaldoAwalBiayaNonOps;
            $nTotalSaldoAkhirLabaRugiNonOps = $nTotalSaldoAkhirPdpNonOps - $nTotalSaldoAkhirBiayaNonOps;
            $nTotalMutasiLabaRugiNonOps = $nTotalMutasiPdpNonOps - $nTotalMutasiBiayaNonOps;
            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'LABA / RUGI NON OPERASIONAL',
                'SaldoAwal' => $nTotalSaldoAwalLabaRugiNonOps,
                'Mutasi' => $nTotalMutasiLabaRugiNonOps,
                'SaldoAkhir' => $nTotalSaldoAkhirLabaRugiNonOps
            ];


            // Laba / Rugi Tahun Berjalan Sebelum Pajak
            $nTotalSaldoAwalLabaRugiSblmPajak = self::hitungLabaRugiSblmPajak(
                $nTotalSaldoAwalPdpOps,
                $nTotalSaldoAwalHPP,
                $nTotalSaldoAwalBiayaDanUmum,
                $nTotalSaldoAwalPdpNonOps,
                $nTotalSaldoAwalBiayaNonOps
            );

            $nTotalSaldoAkhirLabaRugiSblmPajak = self::hitungLabaRugiSblmPajak(
                $nTotalSaldoAkhirPdpOps,
                $nTotalSaldoAkhirHPP,
                $nTotalSaldoAkhirBiayaDanUmum,
                $nTotalSaldoAkhirPdpNonOps,
                $nTotalSaldoAkhirBiayaNonOps
            );

            $nTotalMutasiLabaRugiSblmPajak = self::hitungLabaRugiSblmPajak(
                $nTotalMutasiPdpOps,
                $nTotalMutasiHPP,
                $nTotalMutasiBiayaDanUmum,
                $nTotalMutasiPdpNonOps,
                $nTotalMutasiBiayaNonOps
            );

            $nTotalSaldoAwalLabaRugiSblmPajak = $nTotalSaldoAwalPdpOps - $nTotalSaldoAwalHPP - $nTotalSaldoAwalBiayaDanUmum + $nTotalSaldoAwalPdpNonOps - $nTotalSaldoAwalBiayaNonOps;
            $nTotalSaldoAkhirLabaRugiSblmPajak = $nTotalSaldoAkhirPdpOps - $nTotalSaldoAkhirHPP - $nTotalSaldoAkhirBiayaDanUmum + $nTotalSaldoAkhirPdpNonOps - $nTotalSaldoAkhirBiayaNonOps;
            $nTotalMutasiLabaRugiSblmPajak = $nTotalMutasiPdpOps - $nTotalMutasiHPP - $nTotalMutasiBiayaDanUmum + $nTotalMutasiPdpNonOps - $nTotalMutasiBiayaNonOps;
            // return $va = [
            //     'nTotalSaldoAkhirPdpOps' => $nTotalSaldoAkhirPdpOps,
            //     'nTotalSaldoAkhirHPP' => $nTotalSaldoAkhirHPP,
            //     'nTotalSaldoAkhirBiayaDanUmum' => $nTotalSaldoAkhirBiayaDanUmum,
            //     'nTotalSaldoAkhirPdpNonOps' => $nTotalSaldoAkhirPdpNonOps,
            //     'nTotalSaldoAkhirBiayaNonOps' => $nTotalSaldoAkhirBiayaNonOps
            // ];

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'LABA / RUGI TAHUN BERJALAN SEBELUM PAJAK',
                'SaldoAwal' => $nTotalSaldoAwalLabaRugiSblmPajak,
                'Mutasi' => $nTotalMutasiLabaRugiSblmPajak,
                'SaldoAkhir' => $nTotalSaldoAkhirLabaRugiSblmPajak
            ];

            // Biaya Taksiran Pajak
            $vaBiayaTaksiranPajak = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '>=', GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'))
                ->where(function ($q) {
                    $q->where('Kode', '<', GetterSetter::getDBConfig('rek_hppAwal'))
                        ->orWhere('Kode', '>', GetterSetter::getDBConfig('rek_hppAkhir'));
                })
                ->orderBy('Kode')
                ->get();

            foreach ($vaBiayaTaksiranPajak as $biayaTaksPajak) {
                $cKode = $biayaTaksPajak->Kode;
                $cKeterangan = $biayaTaksPajak->Keterangan;
                $cJenis = $biayaTaksPajak->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;

                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal,
                        'Mutasi' => $nMutasi,
                        'SaldoAkhir' => $nSaldoAkhir
                    ];
                }
            }

            //Total Biaya Taksiran Pajak
            $nTotalSaldoAwalBiayaTaksPajak = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'), '');
            $nTotalSaldoAkhirBiayaTaksPajak = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'), '');
            $nTotalMutasiBiayaTaksPajak = $nTotalSaldoAkhirBiayaTaksPajak - $nTotalSaldoAwalBiayaTaksPajak;

            // Laba / Rugi Tahun Berjalan Sesudah Pajak
            $nTotalSaldoAwalLabaRugiSdhPajak = $nTotalSaldoAwalLabaRugiOps + $nTotalSaldoAwalLabaRugiNonOps;
            $nTotalSaldoAkhirLabaRugiSdhPajak = $nTotalSaldoAwalLabaRugiSdhPajak + $nTotalMutasiLabaRugiOps + $nTotalMutasiLabaRugiNonOps;
            $nTotalMutasiLabaRugiSdhPajak = $nTotalSaldoAkhirLabaRugiSdhPajak - $nTotalSaldoAwalLabaRugiSdhPajak;

            // Biaya Taksiran Pajak
            $vaBiayaPajak = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', '=', GetterSetter::getDBConfig('rek_biayaNonOperasionalAkhir'))
                ->orderBy('Kode')
                ->first();
            $cKodePajak = $vaBiayaPajak->Kode;
            $nSaldoAwalPajak = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKodePajak, '');
            $nSaldoAkhirPajak = GetterSetter::getSaldoMutasi($dTglAwal, $dTglAkhir, $cKodePajak, '');
            $nMutasiPajak = $nSaldoAkhirPajak - $nSaldoAwalPajak;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'LABA / RUGI TAHUN BERJALAN SESUDAH PAJAK',
                'SaldoAwal' => $nTotalSaldoAwalLabaRugiSblmPajak - $nSaldoAwalPajak,
                'Mutasi' => $nTotalMutasiLabaRugiSdhPajak - $nMutasiPajak,
                'SaldoAkhir' => $nTotalSaldoAkhirLabaRugiSdhPajak - $nSaldoAkhirPajak
            ];
            // JIKA REQUEST SUKSES
            if ($vaArray) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $vaArray,
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

    function hitungLabaRugiSblmPajak($pdpOps, $hpp, $biayaUmum, $pdpNonOps, $biayaNonOps)
    {
        return $pdpOps - $hpp - $biayaUmum + $pdpNonOps - $biayaNonOps;
    }
}
