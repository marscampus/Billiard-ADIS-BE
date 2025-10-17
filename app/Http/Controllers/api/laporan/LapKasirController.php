<?php

namespace App\Http\Controllers\api\laporan;

use App\Http\Controllers\Controller;
use App\Models\penjualan\Penjualan;
use App\Models\penjualan\TotPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapKasirController extends Controller
{
    public function data(Request $request)
    {
        try {
            $limit = 10;
            $gudang = $request->GUDANG;
            $kasir = $request->KASIR;
            $spv = $request->SUPERVISOR;
            $cStatus = $request->STATUS;
            $startDate = $request->input('START_DATE');
            $endDate = $request->input('END_DATE');
            $vaData = DB::table('totpenjualan as tp')
                ->select(
                    'tp.KODESESI',
                    'tp.KODESESI_RETUR',
                    'tp.DATETIME',
                    'tp.FAKTUR',
                    'tp.GUDANG',
                    'tp.TOTAL',
                    'tp.SUBTOTAL',
                    'tp.TOTALHPP',
                    DB::raw('IFNULL(SUM(tp.DISCOUNT + tp.DISCOUNT2), 0) as DISCOUNT'),
                    DB::raw('IFNULL(SUM(tp.PAJAK + tp.PAJAK2), 0) as PPN'),
                    'tp.USERNAME',
                    'tp.KODESESI',
                    'tp.KODESESI_RETUR',
                    'tp.DONASI',
                    'g.KETERANGAN as GUDANG'
                )
                ->leftJoin('gudang as g', 'g.KODE', '=', 'tp.GUDANG')
                ->whereBetween('tp.TGL', [$startDate, $endDate])
                ->where('tp.STATUS_RETUR', '=', $cStatus)
                ->orderByDesc('tp.FAKTUR')
                ->groupBy('tp.FAKTUR');

            $vaData->orderByDesc('TGL');
            if ($gudang) {
                $vaData->where('tp.GUDANG', '=', $gudang);
            }
            if ($kasir) {
                $vaData->where('tp.USERNAME', '=', $kasir);
            }
            $vaData = $vaData->get();
            $vaArray = [];
            foreach ($vaData as $d) {
                $cSesiJual = $d->KODESESI;
                $cSesiRetur = $d->KODESESI_RETUR;
                $cSPV = '';
                $cKasir = '';
                $skipData = false;

                if ($cSesiJual || $cSesiRetur) {
                    $vaData2Query = DB::table('sesi_jual as sesi')
                        ->select('sesi.KASIR', 'sesi.SUPERVISOR')
                        ->where(function ($query) use ($cSesiJual, $cSesiRetur) {
                            $query->where('SESIJUAL', '=', $cSesiJual)
                                ->orWhere('SESIJUAL', '=', $cSesiRetur);
                        });
                    if (!empty($kasir)) {
                        $vaData2Query->where('sesi.KASIR', '=', $kasir);
                    }
                    if (!empty($spv)) {
                        $vaData2Query->where('sesi.SUPERVISOR', '=', $spv);
                    }
                    $vaData2 = $vaData2Query->first();

                    if ($vaData2) {
                        $cKasir = $vaData2->KASIR;
                        $cSPV = $vaData2->SUPERVISOR;
                    } else {
                        $skipData = true;
                    }
                }

                // Ambil FULLNAME untuk KASIR
                $fullnameKasir = '';
                if ($cKasir) {
                    $kasirData = DB::table('username')->select('FULLNAME')->where('USERNAME', $cKasir)->first();
                    if ($kasirData) {
                        $fullnameKasir = $kasirData->FULLNAME;
                    }
                }

                // Ambil FULLNAME untuk SPV
                $fullnameSpv = '';
                if ($cSPV) {
                    $spvData = DB::table('username')->select('FULLNAME')->where('USERNAME', $cSPV)->first();
                    if ($spvData) {
                        $fullnameSpv = $spvData->FULLNAME;
                    }
                }
                if (!$skipData) {
                    $vaArray[] = [
                        'TGL' => $d->DATETIME,
                        'KODESESI' => $d->KODESESI,
                        'KODESESI_RETUR' => $d->KODESESI_RETUR,
                        'FAKTUR' => $d->FAKTUR,
                        'GUDANG' => $d->GUDANG,
                        'TOTAL' => $d->SUBTOTAL,
                        'TOTALHPP' => $d->TOTALHPP,
                        'DISCOUNT' => $d->DISCOUNT,
                        'PPN' => $d->PPN,
                        'SELISIHJUAL' => $d->TOTAL - $d->TOTALHPP,
                        'KASIR' => $fullnameKasir,
                        'DONASI' => $d->DONASI,
                        'SPV' => $fullnameSpv
                    ];
                }
            }

            // Terapkan filter tambahan jika ada
            if (!empty($request->filters)) {
                foreach ($request->filters as $filterField => $filterValue) {
                    $vaArray = array_filter($vaArray, function ($item) use ($filterField, $filterValue) {
                        return strpos($item[$filterField], $filterValue) !== false;
                    });
                }
            }

            // $vaResult1 = [
            //     'perPage' => $vaData->perPage(),
            //     'currentPage' => $vaData->currentPage(),
            //     'total' => $vaData->total(),
            //     'lastPage' => $vaData->lastPage(),
            //     'data' => $vaArray,
            // ];


            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $vaArray,
                'total_data' => count($vaArray),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function getDataByFaktur(Request $request)
    {
        $FAKTUR = $request->FAKTUR;
        try {
            $penjualan = Penjualan::with('stock')
                ->where('FAKTUR', $FAKTUR)
                ->get();
            // dd($penjualan);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $penjualan,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
}
