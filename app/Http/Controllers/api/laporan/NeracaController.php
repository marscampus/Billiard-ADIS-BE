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
 * Created on Wed Jul 24 2024 - 04:51:16
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
use Illuminate\Support\Facades\Validator;

class NeracaController extends Controller
{
    function data(Request $request)
    {
        try {
            ini_set('max_execution_time', 0);

            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);
            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];
            $vaAset = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', 'LIKE', GetterSetter::getDBConfig('rek_aset') . '%')
                ->orderBy('Kode')
                ->get();
            foreach ($vaAset as $aset) {
                $cKode = $aset->Kode;
                $cKeterangan = $aset->Keterangan;
                $cJenis = $aset->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;
                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal ?? 0,
                        'Mutasi' => $nMutasi ?? 0,
                        'SaldoAkhir' => $nSaldoAkhir ?? 0
                    ];
                }
            }

            // Total Aset (Aktiva)
            $nTotalSaldoAwalAset = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_aset'));
            $nTotalSaldoAkhirAset = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_aset'));
            $nTotalMutasiAset = $nTotalSaldoAkhirAset - $nTotalSaldoAwalAset;
            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL ASET',
                'Jenis' => 'I',
                'SaldoAwal' => $nTotalSaldoAwalAset,
                'Mutasi' => $nTotalMutasiAset,
                'SaldoAkhir' => $nTotalSaldoAkhirAset
            ];

            // Pasiva
            // Kewajiban
            $vaKewajiban = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', 'LIKE', GetterSetter::getDBConfig('rek_kewajiban') . '%')
                ->orderBy('Kode')
                ->get();

            foreach ($vaKewajiban as $kewajiban) {
                $cKode = $kewajiban->Kode;
                $cKeterangan = $kewajiban->Keterangan;
                $cJenis = $kewajiban->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;
                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal ?? 0,
                        'Mutasi' => $nMutasi ?? 0,
                        'SaldoAkhir' => $nSaldoAkhir ?? 0
                    ];
                }
            }

            // Total Kewajiban
            $nTotalSaldoAwalKewajiban = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_kewajiban'));
            $nTotalSaldoAkhirKewajiban = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_kewajiban'));
            $nTotalMutasiKewajiban = $nTotalSaldoAkhirKewajiban - $nTotalSaldoAwalKewajiban;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL KEWAJIBAN',
                'Jenis' => 'I',
                'SaldoAwal' => $nTotalSaldoAwalKewajiban,
                'Mutasi' => $nTotalMutasiKewajiban,
                'SaldoAkhir' => $nTotalSaldoAkhirKewajiban
            ];

            // Modal
            $vaModal = DB::table('rekening')
                ->select('Kode', 'Keterangan', 'jenis')
                ->where('Kode', 'LIKE', GetterSetter::getDBConfig('rek_modal') . '%')
                ->orderBy('Kode')
                ->get();

            foreach ($vaModal as $modal) {
                $cKode = $modal->Kode;
                if ($cKode === GetterSetter::getDBConfig('rek_rekeningLaba')) {
                    $vaPendapatan = DB::table('rekening')
                        ->select('Kode', 'Keterangan', 'jenis')
                        ->where('Kode', 'LIKE', '4%')
                        ->orderBy('Kode')
                        ->get();
                    foreach ($vaPendapatan as $pendapatan) {
                        $cKode = $pendapatan->Kode;
                        $cKeterangan = $pendapatan->Keterangan;
                        $cJenis = $pendapatan->jenis;
                        $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                        $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                        $nMutasi = $nSaldoAkhir - $nSaldoAwal;
                    }

                    // Total Pendapatan
                    $nTotalSaldoAwalPendapatan = GetterSetter::getSaldoAwalLabarugi($dTglAwal, '4', '', true);
                    $nTotalSaldoAkhirPendapatan = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, '4', '', true);
                    $nTotalMutasiPendapatan = $nTotalSaldoAkhirPendapatan - $nTotalSaldoAwalPendapatan;

                    $vaBiaya = DB::table('rekening')
                        ->select('Kode', 'Keterangan', 'jenis')
                        ->where('Kode', 'LIKE', '5%')
                        ->orderBy('Kode')
                        ->get();
                    foreach ($vaBiaya as $biaya) {
                        $cKode = $biaya->Kode;
                        $cKeterangan = $biaya->Keterangan;
                        $cJenis = $biaya->jenis;
                        $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                        $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                        $nMutasi = $nSaldoAkhir - $nSaldoAwal;
                    }

                    // Total Biaya
                    $nTotalSaldoAwalBiaya = GetterSetter::getSaldoAwalLabarugi($dTglAwal, '5', '', true);
                    $nTotalSaldoAkhirBiaya = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, '5', '', true);
                    $nTotalMutasiBiaya = $nTotalSaldoAkhirBiaya - $nTotalSaldoAwalBiaya;
                    $vaArray[] = [
                        'Kode' => GetterSetter::getDBConfig('rek_rekeningLaba'),
                        'Keterangan' => GetterSetter::getKeterangan(GetterSetter::getDBConfig('rek_rekeningLaba'), 'Keterangan', 'rekening'),
                        'SaldoAwal' => $nTotalSaldoAwalPendapatan - $nTotalSaldoAwalBiaya,
                        'Mutasi' => $nTotalMutasiPendapatan - $nTotalMutasiBiaya,
                        'SaldoAkhir' => $nTotalSaldoAkhirPendapatan - $nTotalSaldoAkhirBiaya
                    ];
                }
                $cKeterangan = $modal->Keterangan;
                $cJenis = $modal->jenis;
                $nSaldoAwal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, $cKode, '');
                $nSaldoAkhir = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, $cKode, '');
                $nMutasi = $nSaldoAkhir - $nSaldoAwal;
                if ($cJenis === 'I' || ($cJenis === 'D' && ($nSaldoAwal != 0 || $nSaldoAkhir != 0 || $nMutasi != 0))) {
                    $vaArray[] = [
                        'Kode' => $cKode,
                        'Keterangan' => $cKeterangan,
                        'Jenis' => $cJenis,
                        'SaldoAwal' => $nSaldoAwal ?? 0,
                        'Mutasi' => $nMutasi ?? 0,
                        'SaldoAkhir' => $nSaldoAkhir ?? 0
                    ];
                }
            }

            // Total Modal
            $nTotalSaldoAwalModal = GetterSetter::getSaldoAwalLabarugi($dTglAwal, GetterSetter::getDBConfig('rek_modal')) + $nTotalSaldoAwalPendapatan + $nTotalSaldoAwalBiaya;
            $nTotalSaldoAkhirModal = GetterSetter::getSaldoAwalLabarugi($dTglAkhir, GetterSetter::getDBConfig('rek_modal')) + $nTotalSaldoAkhirPendapatan + $nTotalSaldoAkhirBiaya;
            $nTotalMutasiModal = $nTotalSaldoAkhirModal - $nTotalSaldoAwalModal;

            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL MODAL',
                'Jenis' => 'I',
                'SaldoAwal' => $nTotalSaldoAwalModal,
                'Mutasi' => $nTotalMutasiModal,
                'SaldoAkhir' => $nTotalSaldoAkhirModal
            ];

            // Total Pasiva
            $nTotalSaldoAwalPasiva = $nTotalSaldoAwalModal + $nTotalSaldoAwalKewajiban;
            $nTotalMutasiPasiva = $nTotalMutasiModal + $nTotalMutasiKewajiban;
            $nTotalSaldoAkhirPasiva = $nTotalSaldoAwalPasiva + $nTotalMutasiPasiva;
            $vaArray[] = [
                'Kode' => '',
                'Keterangan' => 'TOTAL KEWAJIBAN DAN MODAL',
                'SaldoAwal' => $nTotalSaldoAwalPasiva,
                'Mutasi' => $nTotalMutasiPasiva,
                'SaldoAkhir' => $nTotalSaldoAkhirPasiva
            ];
            // TOTAL AKTIVA DAN PASIVA PADA BULAN KEMARIN DAN BULAN INI
            $nBalanceAwal = round($nTotalSaldoAwalAset) - round($nTotalSaldoAwalPasiva);
            $vaArrayHariKemarin[] =
                [
                    'TotalAset' => 'TOTAL ASET : ',
                    'SaldoAktiva' => $nTotalSaldoAwalAset,
                    'Status' => ($nBalanceAwal == 0) ? "BALANCE" : "TIDAK BALANCE",
                    'Keterangan' => 'TOTAL KEWAJIBAN DAN MODAL',
                    'SaldoPasiva' => $nTotalSaldoAwalPasiva
                ];
            $nBalanceAkhir = round($nTotalSaldoAkhirAset) - round($nTotalSaldoAkhirPasiva);
            $vaArrayHariIni[] =
                [
                    'TotalAset' => 'TOTAL ASET : ',
                    'SaldoAktiva' => $nTotalSaldoAkhirAset,
                    'Status' => ($nBalanceAkhir == 0) ? "BALANCE" : "TIDAK BALANCE",
                    'Keterangan' => 'TOTAL KEWAJIBAN DAN MODAL',
                    'SaldoPasiva' => $nTotalSaldoAkhirPasiva
                ];

            $vaResults = [
                "neraca" => $vaArray,
                "awal" => $vaArrayHariKemarin,
                "akhir" => $vaArrayHariIni
            ];
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Ambil Data',
                "neraca" => $vaArray,
                "awal" => $vaArrayHariKemarin,
                "akhir" => $vaArrayHariIni,
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
