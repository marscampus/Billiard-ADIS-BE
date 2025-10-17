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
 * Created on Mon Aug 05 2024 - 03:03:54
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\posting;

use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JurnalController extends Controller
{
    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'TglAwal' => 'required|date',
                'TglAkhir' => 'required|date'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'array' => 'Kolom :attribute harus berupa array.',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $dTglAwal = date('Y-m-d', strtotime($request->TglAwal));
            $dTglAkhir = date('Y-m-d', strtotime($request->TglAkhir));

            $vaData = DB::table('reservasi')
                ->select('kode_reservasi')
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->get();
            foreach ($vaData as $d) {
                Upd::updRekeningReservasi($d->kode_reservasi);
            }
            $vaData2 = DB::table('invoice')
                ->select('kode_invoice')
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->get();

            foreach ($vaData2 as $d) {
                Upd::updRekeningInvoice($d->kode_invoice);
            }

            // self::postingPembelian($dTglAwal, $dTglAkhir);
            // self::postingReturPembelian($dTglAwal, $dTglAkhir);
            // self::postingPenjualan($dTglAwal, $dTglAkhir);

            Upd::updRekeningAktivaDanJurnalLain($dTglAwal, $dTglAkhir);
            Upd::updRekeningTransaksiKas($dTglAwal, $dTglAkhir);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Posting Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Posting Data: ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }


    public function postingPembelian($dTglAwal, $dTglAkhir)
    {
        try {
            $vaData = DB::table('totpembelian')
                ->select('Faktur')
                ->whereBetween('Tgl', [$dTglAwal, $dTglAkhir])
                ->get();
            foreach ($vaData as $d) {
                Upd::updRekeningPembelian($d->Faktur, true);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function postingReturPembelian($dTglAwal, $dTglAkhir)
    {
        try {
            $vaData = DB::table('totrtnpembelian')
                ->select('Faktur')
                ->whereBetween('Tgl', [$dTglAwal, $dTglAkhir])
                ->get();
            foreach ($vaData as $d) {
                Upd::updRekeningReturPembelian($d->Faktur, true);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function postingPenjualan($dTglAwal, $dTglAkhir)
    {
        try {
            $vaData = DB::table('totpenjualan')
                ->select('KodeSesi')
                ->distinct('KodeSesi')
                ->whereBetween('Tgl', [$dTglAwal, $dTglAkhir])
                ->get();
            $cKodeSesi = '';
            foreach ($vaData as $d) {
                $cKodeSesi = $d->KodeSesi;
                Upd::UpdRekeningKasir($cKodeSesi, true);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function postingPembayaranFaktur($dTglAwal, $dTglAkhir)
    {
        try {
            $vaData = DB::table('jurnal')
                ->select(
                    'Faktur',
                    'Tgl',
                    'Rekening',
                    'Cabang',
                    'Keterangan',
                    'Debet',
                    'Kredit',
                    'UserName'
                )
                ->whereBetween('Tgl', [$dTglAwal, $dTglAkhir])
                ->get();
            foreach ($vaData as $d) {
                Upd::updBukuBesar(
                    $d->Faktur,
                    $d->Cabang,
                    $d->Tgl,
                    $d->Rekening,
                    $d->Keterangan,
                    $d->Debet,
                    $d->Kredit,
                    Upd::KR_PELUNASAN_HUTANG,
                    'N'
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
