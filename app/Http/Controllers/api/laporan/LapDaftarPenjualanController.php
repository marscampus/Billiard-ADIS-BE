<?php

namespace App\Http\Controllers\api\laporan;

use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\penjualan\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapDaftarPenjualanController extends Controller
{
    public function data(Request $request)
    {
        // dd($request->filters);
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['auth']);

            $limit = 10;
            $gudang = $request->GUDANG;
            $kasir = $request->KASIR;
            $supervisor = $request->SUPERVISOR;
            $cStatus = $request->STATUS;
            $startDate = $request->input('START_DATE');
            $endDate = $request->input('END_DATE');

            // Query untuk mendapatkan data dari tabel penjualan dengan rentang tanggal
            $vaData = DB::table('penjualan as tp')
                ->whereBetween('tp.TGL', [$startDate, $endDate]);
            // ->orderBy('tp.TGL', 'DESC')
            // ->get();

            // $vaData = $vaData->paginate($limit);
            $vaData = $vaData->get();
            $vaArray = [];
            foreach ($vaData as $d) {
                $cFaktur = $d->FAKTUR;

                // Query untuk mendapatkan data detail dari totpenjualan berdasarkan FAKTUR
                $vaTotPenjualanQuery = DB::table('totpenjualan as tp')
                    ->select('tp.KODESESI', 'tp.KODESESI_RETUR', 'tp.DATETIME', 'tp.GUDANG', 'g.KETERANGAN as GUDANG')
                    ->leftJoin('gudang as g', 'g.KODE', '=', 'tp.GUDANG')
                    ->where('tp.FAKTUR', '=', $cFaktur)
                    ->where('tp.STATUS_RETUR', '=', $cStatus)
                    ->orderBy('tp.DATETIME', 'DESC');

                if (!empty($gudang)) {
                    $vaTotPenjualanQuery->where('tp.GUDANG', '=', $gudang);
                }
                $vaTotPenjualan = $vaTotPenjualanQuery->first();

                $cSPV = '';
                $cKasir = '';
                $skipData = false;
                $indikasiFaktur = '';

                if ($vaTotPenjualan) {
                    $cSesiJual = $vaTotPenjualan->KODESESI;
                    $cSesiRetur = $vaTotPenjualan->KODESESI_RETUR;
                    // Cek apakah FAKTUR berada di KODESESI atau KODESESI_RETUR
                    if (!empty($cSesiJual)) {
                        $indikasiFaktur = 'KODESESI';
                    } elseif (!empty($cSesiRetur)) {
                        $indikasiFaktur = 'KODESESI_RETUR';
                    } else {
                        $indikasiFaktur = 'TIDAK_DITEMUKAN';
                    }

                    // Query untuk mendapatkan data sesi_jual berdasarkan KODESESI
                    $vaData2Query = DB::table('sesi_jual as sesi')
                        ->select('sesi.KASIR', 'sesi.SUPERVISOR')
                        ->where(function ($query) use ($cSesiJual, $cSesiRetur) {
                            $query->where('SESIJUAL', '=', $cSesiJual)
                                ->orWhere('SESIJUAL', '=', $cSesiRetur);
                        });

                    if (!empty($kasir)) {
                        $vaData2Query->where('sesi.KASIR', '=', $kasir);
                    }
                    if (!empty($supervisor)) {
                        $vaData2Query->where('sesi.SUPERVISOR', '=', $supervisor);
                    }
                    $vaData2 = $vaData2Query->first();

                    if ($vaData2) {
                        $cKasir = $vaData2->KASIR;
                        $cSPV = $vaData2->SUPERVISOR;
                    } else {
                        $skipData = true;
                    }
                } else {
                    $skipData = true;
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
                    // Query untuk mendapatkan nama stock berdasarkan KODE
                    $vaStock = DB::table('stock')
                        ->select('NAMA')
                        ->where('KODE', '=', $d->KODE)
                        ->first();

                    // Memasukkan data ke dalam array
                    $vaArray[] = [
                        'TGL' => $vaTotPenjualan ? $vaTotPenjualan->DATETIME : '',
                        'FAKTUR' => $cFaktur,
                        'INDIKASI_FAKTUR' => $indikasiFaktur,
                        'KODE' => $d->KODE,
                        'BARCODE' => $d->BARCODE,
                        'NAMA' => $vaStock ? $vaStock->NAMA : '',
                        'GUDANG' => $vaTotPenjualan ? $vaTotPenjualan->GUDANG : '',
                        'HARGA' => $d->HARGA,
                        'QTY' => $d->QTY,
                        'SATUAN' => $d->SATUAN,
                        'JUMLAH' => $d->JUMLAH,
                        'HP' => $d->HP,
                        'DISCOUNT' => $d->DISCOUNT,
                        'SELISIHJUAL' => $d->JUMLAH - $d->HP - $d->PPN,
                        'PPN' => $d->PPN,
                        'KASIR' => $fullnameKasir,
                        'SPV' => $fullnameSpv,
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

            // $vaResult = [
            //     'perPage' => $vaData->perPage(),
            //     'currentPage' => $vaData->currentPage(),
            //     'total' => $vaData->total(),
            //     'lastPage' => $vaData->lastPage(),
            //     'data' => $vaArray,
            // ];
            $vaResult = [
                'data' => $vaArray,
                'total_data' => count($vaArray)
            ];

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
