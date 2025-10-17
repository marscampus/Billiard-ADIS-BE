<?php

namespace App\Http\Controllers\api\laporan;

use App\Helpers\Func;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class BukuBesarController extends Controller
{
    function dataTotal(Request $request)
    {
        try {
            $cUser = Func::dataAuth($request);
            $tglAwal = $request->TglAwal;
            $tglAkhir = $request->TglAkhir;
            $rekeningAwal = $request->RekeningAwal ?? GetterSetter::getDBConfig('rek_aset_awal');
            $rekeningAkhir = $request->RekeningAkhir ?? $rekeningAwal;

            // Ambil semua transaksi dalam periode
            $vaData = DB::table('bukubesar as b')
                ->select(
                    'b.Rekening',
                    DB::raw("DATE_FORMAT(b.Tgl, '%d-%m-%Y') as Tgl"),
                    'r.Keterangan',
                    DB::raw('SUM(b.Debet) as Debet'),
                    DB::raw('SUM(b.Kredit) as Kredit')
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'b.Rekening')
                ->whereBetween('b.Tgl', [$tglAwal, $tglAkhir])
                ->whereBetween('b.Rekening', [$rekeningAwal, $rekeningAkhir])
                ->groupBy('b.Rekening', 'b.Tgl', 'r.Keterangan')
                ->orderByDesc('b.Tgl')
                ->get();
            $saldoAwal = GetterSetter::getSaldoAwalLabarugi($tglAwal, $rekeningAwal, $rekeningAkhir);
            // Inisialisasi hasil
            $resultArray = collect([
                [
                    "No" => "",
                    "Tgl" => "",
                    "Keterangan" => "Saldo Awal",
                    "Debet" => "",
                    "Kredit" => "",
                    "Akhir" => $saldoAwal
                ]
            ]);

            $totalDebet = 0;
            $totalKredit = 0;

            // Iterasi melalui transaksi
            $saldoAkhir = $saldoAwal;
            foreach ($vaData as $index => $transaction) {
                $totalDebet += $transaction->Debet;
                $totalKredit += $transaction->Kredit;
                $saldoAkhir = $saldoAwal + $totalDebet - $totalKredit;

                $resultArray->push([
                    "No" => $index + 1,
                    "Tgl" => $transaction->Tgl,
                    "Keterangan" => "{$transaction->Keterangan} Tgl {$transaction->Tgl}",
                    "Debet" => $transaction->Debet,
                    "Kredit" => $transaction->Kredit,
                    "Akhir" => $saldoAkhir
                ]);
            }

            // Tambahkan total akhir
            $resultArray->push([
                "No" => "",
                "Tgl" => "",
                "Keterangan" => "Total",
                "Debet" => $totalDebet,
                "Kredit" => $totalKredit,
                "Akhir" => ""
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $resultArray,
                'total_data' => $resultArray->count(),
                'datetime' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    function dataDetail(Request $request)
    {
        try {

            $cUser = Func::dataAuth($request);
            $tglAwal = $request->TglAwal;
            $tglAkhir = $request->TglAkhir;
            $rekeningAwal = isset($request->RekeningAwal) ? $request->RekeningAwal : '';
            $rekeningAkhir = isset($request->RekeningAkhir) ? $request->RekeningAkhir : $rekeningAwal;

            $vaData = DB::table('rekening as r')
                ->select('r.Kode as Rekening', 'r.Keterangan as NamaPerkiraan', DB::raw('IFNULL(SUM(b.Debet-b.Kredit),0) as SaldoAwal'))
                ->leftJoin('bukubesar as b', function ($join) use ($tglAwal) {
                    $join->on('r.Kode', '=', 'b.Rekening');
                        // ->where('b.Tgl', '<', $tglAwal);
                })
                // ->leftJoin('cabang as c', 'c.kode', '=', 'b.Cabang')
                ->whereBetween('r.Kode', [$rekeningAwal, $rekeningAkhir])
                ->whereIn('r.Kode', function ($subquery) {
                    $subquery->select(DB::raw('distinct(rekening)'))
                        ->from('bukubesar');
                })
                ->groupBy('r.Kode', 'r.Keterangan')
                ->orderBy('r.Kode')
                ->get();

            // Inisialisasi array untuk menyimpan hasil akhir
            $finalResult = [];

            foreach ($vaData as $row) {
                $totalDebet = 0;
                $totalKredit = 0;

                $rekening = $row->Rekening;
                $namaPerkiraan = $row->NamaPerkiraan;

                $saldoAwalJudul = $row->SaldoAwal;
                $saldoAwal = $row->SaldoAwal;
                $vaData2 = DB::table('bukubesar as b')
                    ->select('b.Rekening', 'b.Faktur', 'b.Tgl', 'b.Keterangan', 'r.kode', 'r.keterangan as NamaPerkiraan', 'b.Debet', 'b.Kredit', 'b.UserName')
                    ->leftJoin('rekening as r', 'r.kode', '=', 'b.rekening')
                    ->where('tgl', '>=', $tglAwal)
                    ->where('tgl', '<=', $tglAkhir)
                    ->where('r.Kode', $rekening)
                    ->orderBy('Tgl')
                    ->orderBy(DB::raw("Faktur like 'AA%'"), 'desc')
                    ->orderBy(DB::raw("Faktur like 'km%'"), 'desc')
                    ->orderBy(DB::raw("Faktur like 'ag%'"), 'desc')
                    ->orderBy(DB::raw("Faktur like 'zz%'"), 'asc')
                    ->get();

                foreach ($vaData2 as $item) {
                    $totalDebet += $item->Debet;
                    $totalKredit += $item->Kredit;
                }

                $formattedResult = $vaData2->map(function ($item, $index) use (&$saldoAwal) {
                    $akhir = $saldoAwal + $item->Debet - $item->Kredit;
                    $row = [
                        "Rekening" => $item->Rekening,
                        "No" => $index + 1,
                        "Faktur" => $item->Faktur,
                        "Tgl" => date('d-m-Y', strtotime($item->Tgl)),
                        "Keterangan" => $item->Keterangan,
                        "Debet" => $item->Debet,
                        "Kredit" => $item->Kredit,
                        "Akhir" => $akhir,
                    ];

                    $saldoAwal = $akhir;

                    return $row;
                });

                $barisSaldoAkhir = [
                    "Rekening" => $rekening,
                    "No" => "",
                    "Faktur" => "",
                    "Tgl" => "",
                    "Keterangan" => "Total",
                    "Debet" => $totalDebet,
                    "Kredit" => $totalKredit,
                    "Akhir" => "",
                ];

                $finalResult[] = [
                    "Rekening" => $rekening,
                    "No" => $rekening,
                    "Faktur" => $namaPerkiraan,
                    "Tgl" => "",
                    "Keterangan" => "Saldo Awal",
                    "Debet" => "",
                    "Kredit" => "",
                    "Akhir" => $saldoAwalJudul,
                ];

                // Tambahkan hasil formattedResult ke $finalResult
                foreach ($formattedResult as $formattedRow) {
                    $finalResult[] = $formattedRow;
                }

                $finalResult[] = $barisSaldoAkhir;
            }


            $result = [
                "data" => array_merge(
                    $finalResult
                ),
            ];

            $filteredResult = [];
            $rekeningCount = []; // Menyimpan jumlah record untuk setiap rekening

            // Menghitung jumlah record untuk setiap rekening
            foreach ($result['data'] as $row) {
                $rekening = $row['Rekening'];
                if (!isset($rekeningCount[$rekening])) {
                    $rekeningCount[$rekening] = 0;
                }
                $rekeningCount[$rekening]++;
            }

            // Memfilter hasil untuk kebalikan dari sebelumnya
            foreach ($result['data'] as $row) {
                $rekening = $row['Rekening'];
                $keterangan = $row['Keterangan'];

                // Hanya tampilkan jika rekening memiliki tidak 2 record atau tidak memiliki keterangan yang sesuai
                if ($rekeningCount[$rekening] !== 2 || ($keterangan !== "Saldo Awal" && $keterangan !== "Total")) {
                    $filteredResult[] = $row;
                }
            }

            $result = [
                "data" => array_merge(
                    $filteredResult
                )
            ];

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $filteredResult,
                'total_data' => count($filteredResult),
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
