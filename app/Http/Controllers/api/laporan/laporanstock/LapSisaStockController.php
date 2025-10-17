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
 * Created on Mon May 13 2024 - 02:22:41
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan\laporanstock;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LapSisaStockController extends Controller
{
    function data(Request $request)
    {
        $vaValidator = Validator::make($request->all(), [
            'TglAwal' => 'required|date'
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
            $vaResult = [];
            $vaArray = [];
            $vaData = DB::table('stock as s')
                ->select(
                    's.Kode',
                    's.Kode_Toko',
                    's.Nama',
                    's.Status_Stock'
                )
                ->get();
            $nSaldo = 0;
            foreach ($vaData as $d) {
                $cKode = $d->Kode;
                $cStatusStock = $d->Status_Stock;
                $vaData2 = DB::table('kartustock')
                    ->select(
                        DB::raw('IFNULL(SUM(Debet - Kredit),0) as Saldo')
                    )
                    ->where('Kode', '=', $cKode)
                    ->where('Tgl', '<=', $request->TglAwal)
                    ->first();
                if ($vaData2) {
                    $nSaldo = $vaData2->Saldo;
                }
                if ($cStatusStock == '1') {
                    $nSaldo = "Unlimited";
                }
                $vaArray[] = [
                    'Kode' => $cKode,
                    'Kode_Toko' => $d->Kode_Toko,
                    'Nama' => $d->Nama,
                    'Saldo' => $nSaldo
                ];
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
                'total_data' => count($vaArray),
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
