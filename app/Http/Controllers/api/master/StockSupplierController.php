<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\master\Stock;
use App\Models\pembelian\StockSupplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockSupplierController extends Controller
{
    public function getDataBySupplier(Request $request)
    {
        $SUPPLIER = $request->SUPPLIER;
        $result = [];
        try {
            //code...
            $data = StockSupplier::with('stock') // Menggunakan Eloquent Relationship
                ->where('supplier', $SUPPLIER)
                ->orderByDesc('Tgl')
                ->get();
            // dd($data);
            foreach ($data as $item) {
                $sisaStock = DB::table('kartustock')
                    ->select(DB::raw('IFNULL(SUM(debet-kredit),0) AS SisaStock'))
                    ->where('Kode', $item->KODE)
                    ->first()->SisaStock;
                $result[] = [
                    'KODE' => $item->KODE,
                    'KODE_TOKO' => $item->stock->KODE_TOKO,
                    'NAMA' => $item->stock->NAMA,
                    'SUPPLIER' => $item->SUPPLIER,
                    'REORDER' => $item->REORDER,
                    'MINSTOCK' => $item->MINSTOCK,
                    'SISASTOCK' => $sisaStock
                ];
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $result,
                'total_data' => count($result),
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

    public function getBarcode(Request $request)
    {
        $KODE_TOKO = $request->KODE_TOKO;
        $jml = 1;
        try {
            $data = [];
            if (strpos($KODE_TOKO, '*') !== false) {
                $jml = substr($KODE_TOKO, 0, strpos($KODE_TOKO, '*'));
                $KODE_TOKO = substr($KODE_TOKO, strpos($KODE_TOKO, '*') + 1);
            }

            $row = Stock::where('KODE_TOKO', 'LIKE', '%' . $KODE_TOKO . '%')
                ->where('KODE_TOKO', '<>', 'kode')
                ->count();
            if ($row >= 1) {
                $query = Stock::where('KODE_TOKO', 'LIKE', '%' . $KODE_TOKO . '%')->get();
                if ($query) {
                    $KODE = $query[0]->KODE;
                    $sisaStock = DB::table('kartustock')
                        ->select(DB::raw('IFNULL(SUM(debet-kredit),0) AS SisaStock'))
                        ->where('Kode', $KODE)
                        ->first()->SisaStock;
                    $data[] = [
                        'KODE' => $KODE,
                        'BKP' => $query[0]->BKP,
                        'BARCODE' => $query[0]->KODE_TOKO,
                        'NAMA' => $query[0]->NAMA,
                        'SATUAN' => $query[0]->SATUAN,
                        'HARGABELI' => $query[0]->HB,
                        'HJ' => $query[0]->HJ,
                        'TGLEXP' => $query[0]->EXPIRED,
                        'DISCOUNT' => empty($query[0]->DISCOUNT) ? '0' : $query[0]->DISCOUNT,
                        'PAJAK' => empty($query[0]->PAJAK) ? '0' : $query[0]->PAJAK,
                        'TERIMA' => $jml,
                        'SISASTOCK' => $sisaStock
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
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

    public function store(Request $request)
    {
        $messages = config('validate.validation');
        $vaValidator = Validator::make($request->all(), [
            'SUPPLIER' => 'required',
            'tabelStockSupplier' => 'required',
            // 'MINSTOCK' => 'required|numeric',
        ], [
            'required' => 'Kolom :attribute harus diisi.',
            'max' => 'Kolom ::attribute tidak boleh lebih dari :max karakter.',
            'unique' => 'Kolom :attribute sudah ada di database.'
        ]);

        if ($vaValidator->fails()) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => $vaValidator->errors()->first(),
                'datetime' => date('Y-m-d H:i:s')
            ], 422);
        }
        try {
            $cSupplier = $request->SUPPLIER;
            $dTgl = Carbon::now()->toDateString();
            foreach ($request->input('tabelStockSupplier') as $item) {
                $cKode = $item['KODE'];
                $nQty = $item['REORDER'];
                $nMinStock = $item['MINSTOCK'];
                $vaArray = [
                    'KODE' => $cKode,
                    'SUPPLIER' => $cSupplier,
                    'REORDER' => $nQty,
                    'MINSTOCK' => $nMinStock,
                    'TGL' => $dTgl
                ];
                $existingData = StockSupplier::where('SUPPLIER', $cSupplier)->where('KODE', $cKode)->first();
                if ($existingData) {
                    $existingData->update($vaArray); // Perbarui data jika sudah ada
                } else {
                    StockSupplier::create($vaArray); // Buat data baru jika tidak ada
                }
                // StockSupplier::create($vaArray);
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil disimpan',
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

    public function delete(Request $request)
    {
        try {
            $cKode = $request->KODE;
            $cSupplier = $request->SUPPLIER;

            // Menemukan entri berdasarkan KODE dan SUPPLIER
            $stockSupplier = DB::table('stock_supplier')
                ->where('Kode', '=', $cKode)
                ->where('supplier', '=', $cSupplier)
                ->delete();

            if ($stockSupplier) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Data berhasil dihapus',
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data gagal Dihapus',
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
