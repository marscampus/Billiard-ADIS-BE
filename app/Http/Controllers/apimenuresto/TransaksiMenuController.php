<?php

namespace App\Http\Controllers\apimenuresto;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\menuresto\Usermenu;
use Illuminate\Support\Facades\DB;
use App\Models\menuresto\total_penjualan;
use Illuminate\Support\Facades\Validator;
use App\Models\menuresto\detail_penjualan;

class TransaksiMenuController extends Controller
{
    function generatefaktur()
    {
        $faktur = 'INV' . mt_rand(1000000000, 2000000000);
        while (total_penjualan::where('faktur', $faktur)->exists()) {
            $faktur = 'INV' . mt_rand(1000000000, 2000000000);
        }

        return $faktur;
    }

    // public function store(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'id_user' => 'required|integer',
    //         'sub_total' => 'required|numeric',
    //         'total' => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => $validator->errors(),
    //         ], 422);
    //     }

    //     try {
    //         // Generate random faktur
    //         $faktur = $this->generatefaktur();

    //         // Create total_penjualan record
    //         $transaksi = new total_penjualan();
    //         $transaksi->faktur = $faktur;
    //         $transaksi->id_user = $request->id_user;
    //         $transaksi->TGL = Carbon::now()->format('Y-m-d');
    //         $transaksi->DATETIME = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
    //         $transaksi->SUBTOTAL = $request->sub_total;
    //         $transaksi->PERSDISC = $request->diskon_persen;
    //         $transaksi->DISCOUNT = $request->diskon_rupiah;
    //         $transaksi->total = $request->total;
    //         $transaksi->save();

    //         // Create detail_penjualan records
    //         foreach ($request->items as $item) {
    //             $detail = new detail_penjualan();
    //             $detail->faktur = $faktur;
    //             $detail->KODE = $item['kode_menu'];
    //             $detail->QTY = $item['count'];
    //             $QTY = $item['count'];
    //             $subttl = $item['sub_total_item'];
    //             $harga = $subttl / $QTY;
    //             $detail->JUMLAH = $item['total_item'];
    //             $detail->HARGA = $harga;
    //             $detail->TGL = Carbon::now()->format('Y-m-d');
    //             $detail->HARGADISC = $item['diskon_rupiah_item'];
    //             $detail->DISCOUNT = $item['diskon_persen_item'];
    //             $detail->save();
    //         }
    //         return response()->json([
    //             'message' => 'Transaksi berhasil disimpan',
    //             'faktur' => $faktur
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json(["message" => "Bad Request"], 400);
    //     }
    // }

    function generateKodeSesi()
    {
        $num = 1;
        $lastid = DB::table('totpenjualan_tmp')->orderBY('ID', 'desc')->first();
        if ($lastid) {
            $num = (int)substr($lastid->KODESESI, -6);
        }

        $newnum = str_pad($num + 1, 5, '0', STR_PAD_LEFT);
        $tgl = date('Ymd');
        $kdsesi = "SJ$tgl$newnum";

        return $kdsesi;
    }



    public function store(Request $request)
    {
        // return $request;
        // $username = isset($request->auth->email) ? $request->auth->email : "";

        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer',
            'sub_total' => 'required|numeric',
            'total' => 'required|numeric',
            'nama' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            // Generate random faktur
            $faktur = $this->generatefaktur();
            $kodeSesi = $this->generateKodeSesi();

            $trans = DB::table('totpenjualan_tmp')->insert([
                'CATATAN' => $request->nama,
                'KODESESI' => $kodeSesi,
                'TGL' => date('Y-m-d'),
                'GUDANG' => 01,
                'PERSDISC' => $request->diskon_persen,
                'DISCOUNT' => $request->diskon_rupiah,
                'DATETIME' => date('Y-m-d H:i:s'),
                'SUBTOTAL' => $request->sub_total,
                'USERNAME' => $request->username,
                'TOTAL' => $request->total,
            ]);

            $transaksi = new total_penjualan();
            $transaksi->faktur = $faktur;
            $transaksi->id_user = $request->id_user;
            $transaksi->TGL = Carbon::now()->format('Y-m-d');
            $transaksi->DATETIME = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $transaksi->SUBTOTAL = $request->sub_total;
            $transaksi->PERSDISC = $request->diskon_persen;
            $transaksi->DISCOUNT = $request->diskon_rupiah;
            $transaksi->total = $request->total;
            $transaksi->save();

            foreach ($request->items as $item) {
                $QTY = $item['count'];
                $subttl = $item['sub_total_item'];
                $harga = $subttl / $QTY;
                $trans = DB::table('penjualan_tmp')->insert([
                    'CATATAN' => $request->nama,
                    'KODE' => $item['kode_menu'],
                    'TGL' => date('Y-m-d'),
                    'QTY' => $QTY,
                    'HARGA' => $harga,
                    'BARCODE' => $item['barcode'],
                    'JUMLAH' => $item['total_item'],
                    'HARGADISC' => $item['diskon_rupiah_item'],
                    'DISCOUNT' => $item['diskon_persen_item'],
                    'KETERANGAN' => 'Penjualan Resto',
                ]);

                $detail = new detail_penjualan();
                $detail->faktur = $faktur;
                $detail->KODE = $item['kode_menu'];
                $detail->QTY = $item['count'];
                $detail->JUMLAH = $item['total_item'];
                $detail->HARGA = $harga;
                $detail->TGL = Carbon::now()->format('Y-m-d');
                $detail->HARGADISC = $item['diskon_rupiah_item'];
                $detail->DISCOUNT = $item['diskon_persen_item'];
                $detail->save();
            }
            return response()->json([
                'message' => 'Transaksi berhasil disimpan',
                'faktur' => $faktur
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request" . $th->getMessage()], 400);
        }
    }

    public function index()
    {
        $transaksi = total_penjualan::with(['user', 'detailPenjualan'])
            ->get();

        return response()->json($transaksi);
    }
    public function destroy($id)
    {
        $transaksi = total_penjualan::findOrFail($id);
        $transaksi->delete();

        return response()->json(['message' => 'Transaksi deleted successfully'], 200);
    }

    public function statistics()
    {

        $totalPenjualan = total_penjualan::sum('total');
        $jumlahOrder = total_penjualan::count();
        $jumlahClient = total_penjualan::distinct('id_user')->count('id_user');

        return response()->json([
            'total_penjualan' => $totalPenjualan,
            'jumlah_order' => $jumlahOrder,
            'jumlah_client' => $jumlahClient
        ]);
    }
    public function getTransactionsByDateRange(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $transactions = total_penjualan::whereBetween('tanggal', [$request->from, $request->to])->get();

        return response()->json($transactions);
    }
    public function getTransactionByUser($id)
    {
        $transaction = total_penjualan::where('id_user', $id)->get();
        return response()->json($transaction);
    }
    public function getTransactionByUserWithDetails($id, Request $request)
    {
        $query = total_penjualan::where('ID_USER', $id);

        if ($request->has('start_date')) {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $query->where('TGL', '>=', $start_date);
        }

        if ($request->has('end_date')) {
            $end_date = Carbon::parse($request->end_date)->endOfDay();
            $query->where('TGL', '<=', $end_date);
        }

        $transactions = $query->orderBy('DATETIME', 'desc')->get();

        $formattedTransactions = $transactions->map(function ($transaction) {
            $details = detail_penjualan::where('FAKTUR', $transaction->FAKTUR)
                ->join('stock', 'penjualan_resto.KODE', '=', 'stock.KODE')
                ->select('penjualan_resto.*', 'stock.nama as menu_name', 'stock.FOTO')
                ->get();

            $mainItem = $details->first();
            $otherItemsCount = $details->count() - 1;

            $user = Usermenu::find($transaction->ID_USER);

            return [
                'faktur' => $transaction->FAKTUR,
                'id_user' => $transaction->ID_USER,
                'no_telepon' => $user ? $user->phone : null,
                'alamat' => $user ? $user->address : null,
                'tanggal' => Carbon::parse($transaction->TGL)->format('Y-m-d'),
                'total' => $transaction->TOTAL,
                'sub_total' => $transaction->SUBTOTAL,
                'diskon_rupiah' => $transaction->DISCOUNT,
                'details' => $details->map(function ($detail) {
                    return [
                        'KODE' => $detail->KODE,
                        'QTY' => $detail->QTY,
                        'JUMLAH' => $detail->JUMLAH,
                        'menu_name' => $detail->menu_name,
                        'FOTO' => $detail->FOTO,
                        'subtotal' => $detail->HARGA * $detail->QTY,
                    ];
                }),
                'main_item' => [
                    'menu_name' => $mainItem->menu_name,
                    'FOTO' => $mainItem->FOTO,
                ],
                'other_items_count' => $otherItemsCount,
            ];
        });

        return response()->json($formattedTransactions);
    }
    public function getTransaksiByFaktur($faktur)
    {
        $transaksi = total_penjualan::with('detailPenjualan')
            ->where('faktur', $faktur)
            ->first();

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi not found'], 404);
        }

        return response()->json($transaksi);
    }
}
