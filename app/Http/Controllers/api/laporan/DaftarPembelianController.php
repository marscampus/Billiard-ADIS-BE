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
 * Created on Thu Jun 13 2024 - 02:06:13
 * Author : ARADHEA | aradheadhifa23@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers\api\laporan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\laporan\PembelianFakturPajak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DaftarPembelianController extends Controller
{
    public function data(Request $request)
    {
        $vaValidator = Validator::make($request->all(), [
            'TglAwal' => 'required|date',
            'TglAkhir' => 'required|date'
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
            $dTglAwal = $request->TglAwal;
            $dTglAkhir = $request->TglAkhir;
            $cSupplier = $request->Supplier;
            $cStatus = $request->Status;
            $vaArray = [];
            $vaData = DB::table('totpembelian as tpe')
                ->select(
                    'tpe.Tgl',
                    'tpe.Faktur as FakturPembelian',
                    's.Nama as Supplier',
                    's.Alamat as AlamatSupplier',
                    'tpo.Faktur as FakturPO',
                    'tpo.TglDO',
                    'tpo.FakturAsli',
                    'tpe.JthTmp',
                    'tpo.Total as TotalPO',
                    'tpe.Total AS TotalTerima',
                    'tpe.PPN',
                    'tpe.Discount',
                    'trp.Total as TotalRetur',
                    'tpo.Pajak as PajakPO',
                    'tpo.Total as TotalPO',
                    'kh.FKT as FakturBayar',
                    DB::raw('IFNULL(SUM(kh.Kredit - kh.Debet),0) as Sisa'),
                    'tpe.UserName',
                    DB::raw('IFNULL(SUM(kh.Kredit),0) as TotalBayar'),
                    'pf.tglfaktur_pajak as TglFktPajak',
                    'pf.tglterima_faktur as TglBayar',
                    'pf.jumlah_faktur',
                    'pf.seri_faktur',
                    'pf.cekfaktur'
                )
                ->leftJoin('users as us', 'us.email', '=', 'tpe.UserName')
                ->leftJoin('supplier as s', 's.Kode', '=', 'tpe.Supplier')
                ->leftJoin('totpo as tpo', 'tpo.Faktur', '=', 'tpe.PO')
                ->leftJoin('totrtnpembelian as trp', 'trp.FakturPembelian', '=', 'tpe.Faktur')
                ->leftJoin('kartuhutang as kh', 'kh.Faktur', '=', 'tpe.Faktur')
                ->leftJoin('pembelian_fakturpajak as pf', 'pf.nomortran', '=', 'tpe.Faktur')
                ->whereBetween('tpe.Tgl', [$dTglAwal, $dTglAkhir])
                ->where('tpe.Total', '>', 0)
                ->where('tpe.PO', '<>', '');
            if (!empty($Supplier)) {
                $vaData->where('tpo.Supplier', '=', $Supplier);
            }

            if ($cStatus === 'L') {
                // Hanya tampilkan data dengan Pembayaran <= 0
                $vaData->having('Sisa', '<=', 0);
            } else if ($cStatus === 'BL') {
                // Hanya tampilkan data dengan Pembayaran > 0
                $vaData->having('Sisa', '>', 0);
            }
            $vaData = $vaData->groupBy(
                'tpe.Faktur'
            )->get();
            $vaArray = [];
            $nNo = 0;
            foreach ($vaData as $d) {
                $nSelisihPO = $d->TotalTerima - $d->TotalPO;
                $nNo++;
                $vaArray[] = [
                    'No' => $nNo,
                    'Tgl' => $d->Tgl,
                    'FktPembelian' => $d->FakturPembelian,
                    'Supplier' => $d->Supplier,
                    'AlamatSupplier' => $d->AlamatSupplier,
                    'FktPO' => $d->FakturPO,
                    'TglDO' => $d->TglDO,
                    'FktAsli' => $d->FakturAsli,
                    'JthTmp' => $d->JthTmp,
                    'Pembayaran' => $d->Sisa,
                    'TotalPO' => $d->TotalPO,
                    'TotalTerima' => $d->TotalTerima,
                    'SelisihPO' => $nSelisihPO,
                    'PPN' => $d->PPN,
                    'Disc' => $d->Discount,
                    'Retur' => $d->TotalRetur,
                    'TotalBayar' => $d->TotalBayar,
                    'TglFakturPajak' => $d->TglFktPajak,
                    'JmlFktPajak' => $d->jumlah_faktur,
                    'NoSeriFktPajak' => $d->seri_faktur,
                    'CekFaktur' => $d->cekfaktur,
                    'UserName' => $d->UserName,
                    'FktBayar' => $d->FakturBayar,
                    'TglBayar' => $d->TglBayar
                ];
            }

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

    public function store(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $mandatoryKey = [
                'FktPembelian',
                'TglTerimaFaktur',
                'TglTerimaPajak',
                'Jml',
                'Seri'
            ];
            $vaRequestData = Func::filterArrayClean($vaRequestData, $mandatoryKey);
            if (Func::filterArrayValue($vaRequestData, $mandatoryKey) === false)
                return [];
            foreach ($mandatoryKey as $val) {
                $$val = $vaRequestData[$val];
            }
            $vaArray = [
                'nomortran' => $vaRequestData['FktPembelian'],
                'tanggal' => date('Y-m-d'),
                'tglfaktur_pajak' => $vaRequestData['TglTerimaPajak'],
                'tglterima_faktur' => $vaRequestData['TglTerimaFaktur'],
                'jumlah_faktur' => $vaRequestData['Jml'],
                'seri_faktur' => $vaRequestData['Seri'],
                'OprId' => $cUser
            ];
            PembelianFakturPajak::create($vaArray);
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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

    public function update(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $mandatoryKey = [
                'FktPembelian',
                'CekFkt'
            ];
            $vaRequestData = Func::filterArrayClean($vaRequestData, $mandatoryKey);
            foreach ($mandatoryKey as $val) {
                $$val = $vaRequestData[$val];
            }
            unset($vaRequestData['auth']);
            unset($vaRequestData['page']);
            $vaArray = [
                "cekfaktur" => $CekFkt
            ];
            PembelianFakturPajak::where('nomortran', '=', $FktPembelian)->update($vaArray);
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
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
