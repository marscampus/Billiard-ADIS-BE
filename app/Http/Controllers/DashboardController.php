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
 * Created on Thu Dec 14 2023 - 13:46:20
 * Author : Salsabila Emma | salsabila17emma@gmail.com
 * Version : 1.0
 */

namespace App\Http\Controllers;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Models\fun\MutasiDeposito;
use App\Models\fun\MutasiTabungan;
use App\Models\fun\Username;
use App\Models\master\Stock;
use App\Models\teller\MutasiAnggota;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotPo;
use App\Models\penjualan\TotPenjualan;
use App\Models\penjualan\TotRtnPenjualan;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        // Mendapatkan tanggal mulai dan akhir dari request
        $startDate = $request->input('PERIODE_START');
        $endDate = $request->input('PERIODE_END');
        $dHariIni = Carbon::now()->format('Y-m-d');
        // $dHariIni = "2024-08-19";
        $dataPenjualan = TotPenjualan::where('STATUS_RETUR', '0')
            ->where('DATETIME', '>=', $startDate)
            ->where('DATETIME', '<=', $endDate)
            ->get();

        // Mengambil jumlah data dengan filter tanggal
        $pembelian = TotPembelian::where('DATETIME', '>=', $startDate)
            ->where('DATETIME', '<=', $endDate)
            ->count();
        // $penjualan = TotPenjualan::where('STATUS_RETUR', '0')->where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->count();
        $penjualan = TotPenjualan::where('STATUS_RETUR', '0')
            ->where('DATETIME', '>=', $startDate)
            ->where('DATETIME', '<=', $endDate)
            ->count();
        $rtnPenjualan = TotPenjualan::where('STATUS_RETUR', '1')
            ->where('DATETIME', '>=', $startDate)
            ->where('DATETIME', '<=', $endDate)
            ->count();
        // mengambil jumlah transaksi penjualan hari ini
        $vaTrxJualNow  = TotPenjualan::where('STATUS_RETUR', '0')
            ->where('DATETIME', 'LIKE', $dHariIni . '%')
            ->count();

        $vaBarangTerjualNow = DB::table('penjualan as p')
            ->select(DB::raw('IFNULL(SUM(p.Qty), 0) as Qty'))
            ->leftJoin('totpenjualan as tp', 'tp.Faktur', '=', 'p.Faktur')
            ->where('tp.STATUS_RETUR', '=', '0')
            ->where('DATETIME', 'LIKE', $dHariIni . '%')
            ->first();

        $vaNominalPenjualanNow = DB::table('totpenjualan')
            ->select(DB::raw('IFNULL(SUM(Total), 0) as Total'))
            ->where('STATUS_RETUR', '=', '0')
            ->where('DATETIME', 'LIKE', $dHariIni . '%')
            ->first();

        $stock = Stock::where('TGL_MASUK', '<=', $endDate)->count();

        $po = TotPo::where('DATETIME', '>=', $startDate)
            ->where('DATETIME', '<=', $endDate)
            ->count();

        $vaProdukTerlaris = DB::table('penjualan as p')
            ->select(
                'p.Kode',
                's.Kode_Toko as Barcode',
                's.Nama',
                DB::raw('IFNULL(SUM(p.Qty),0) as Qty')
            )
            ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
            ->where('Tgl', '>=', $startDate)
            ->where('Tgl', '<=', $endDate)
            ->groupBy('p.Kode')
            ->orderByDesc('Qty')
            ->limit(10)
            ->get();

        // Mengambil nilai gross, netto, all trx, dan avg dengan filter tanggal
        $gross = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->sum(DB::raw('total - discount'));
        $netto = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->sum(DB::raw('total - discount - pajak'));
        $allTrx = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->where('STATUS_RETUR', '0', '0')->sum('total');
        $avg = round(TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->avg('total'));

        // Mengambil tanggal pembaruan terakhir dengan filter tanggal
        $lastUpdateGross = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->max('DATETIME');
        $lastUpdateNetto = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->max('DATETIME');
        $lastUpdateAllTrx = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->max('DATETIME');
        $lastUpdateAvg = TotPenjualan::where('DATETIME', '>=', $startDate)->where('DATETIME', '<=', $endDate)->max('DATETIME');

        // Menampilkan bulan dan tahun dalam format: Desember 2023
        $currentDate = Carbon::parse($startDate)->translatedFormat('F Y');

        $data = [
            "countPembelian" => $pembelian,
            "countPenjualan" => $penjualan,
            "countRtnPenjualan" => $rtnPenjualan,
            "countStock" => $stock,
            "countPo" => $po,
            "gross" => $gross,
            "lastUpdateGross" => $lastUpdateGross,
            "netto" => $netto,
            "lastUpdateNetto" => $lastUpdateNetto,
            "allTrx" => $allTrx,
            "lastUpdateAllTrx" => $lastUpdateAllTrx,
            "avg" => $avg,
            "lastUpdateAvg" => $lastUpdateAvg,
            "currentDate" => $currentDate,
            "dataPenjualan" => $dataPenjualan,
            "trxJualNow" => $vaTrxJualNow,
            "barangTerjualNow" => intval($vaBarangTerjualNow->Qty),
            "nominalPenjualanNow" => $vaNominalPenjualanNow->Total,
            "dataProdukTerlaris" => $vaProdukTerlaris
        ];

        return response()->json($data);
    }

    // ---------------------------------------------------------------< gtw >
    public function insertUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            $cEmail = $vaRequestData['auth']['email'];
            unset($vaRequestData['auth']);
            $vaData = DB::table('username')
                ->where('USERNAME', '=', $cEmail)
                ->exists();

            $vaArray = [
                "USERNAME" => $cEmail,
                "TGL" => GetterSetter::getTglTransaksi(),
                "AKTIF" => 1,
                "FULLNAME" => $cUser,
                "DATETIMELOGIN" => Carbon::now()
            ];

            if (!$vaData) {
                Username::create($vaArray);
            } else {
                Username::where('USERNAME', '=', $cEmail)->update($vaArray);
            }

            DB::commit();

            // JIKA REQUEST SUKSES
            $vaRetVal = [
                "status" => "00",
                "message" => "SUKSES"
            ];
            Func::writeLog('Dashboard', 'insertUser', $vaRequestData, $vaRetVal, $cUser);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $th) {
            DB::rollback();
            // JIKA TERJADI KESALAHAN
            $vaRetVal = [
                "status" => "99",
                "message" => "Terjadi kesalahan saat memproses permintaan",
                "error" => [
                    "code" => $th->getCode(),
                    "message" => $th->getMessage(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                ]
            ];
            Func::writeLog('Dashboard', 'insertUser', $vaRequestData ?? [], $vaRetVal, $cUser ?? '');
            return response()->json($vaRetVal);
        }
    }

    public function getJenisGabungan(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cEmail = $vaRequestData['auth']['email'];
        $cJenisGabungan = [];
        unset($vaRequestData['auth']);
        $vaData = DB::table('username')
            ->select("Gabungan")
            ->where('UserName', '=', $cEmail)
            ->first();
        if ($vaData) {
            $cGabungan = $vaData->Gabungan;
            switch ($cGabungan) {
                case 0:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor']
                    ];
                    break;
                case 1:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor'],
                        ['name' => 'B', 'label' => 'Cabang Induk']
                    ];
                    break;
                case 2:
                    $cJenisGabungan = [
                        ['name' => 'A', 'label' => 'Per Kantor'],
                        ['name' => 'B', 'label' => 'Cabang Induk'],
                        ['name' => 'C', 'label' => 'Konsolidasi']
                    ];
                    break;
                default:
                    break;
            }
        }
        return response()->json(['fields' => $cJenisGabungan]);
    }
}
