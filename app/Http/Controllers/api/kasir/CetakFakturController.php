<?php

namespace App\Http\Controllers\api\kasir;

use App\Http\Controllers\Controller;
use App\Models\pembelian\Pembelian;
use App\Models\pembelian\Po;
use App\Models\pembelian\RtnPembelian;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotPo;
use App\Models\pembelian\TotRtnPembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakFakturController extends Controller
{
    public function data(Request $request)
    {
        try {
            $limit = 10;
            $kategori = $request->KATEGORI;
            $startDate = $request->input('START_DATE');
            $endDate = $request->input('END_DATE');

            $table = 'totpo'; // Default table
            if ($kategori === 'Pembelian') {
                $table = 'totpembelian';
            } elseif ($kategori !== 'Purchase Order') {
                $table = 'totrtnpembelian';
            }

            $vaData = DB::table($table)
                ->select(
                    "$table.FAKTUR",
                    "$table.FAKTURASLI",
                    "$table.SUPPLIER",
                    "$table.TGL",
                    "$table.JTHTMP",
                    "$table.SUBTOTAL",
                    "$table.PAJAK",
                    "$table.DISCOUNT",
                    "$table.TOTAL",
                    "$table.KETERANGAN",
                    "$table.DATETIME",
                    "$table.USERNAME",
                    'users.name as USERNAME'
                )
                ->leftJoin('users', "$table.UserName", '=', 'users.email')
                ->whereBetween("$table.TGL", [$startDate, $endDate])
                ->orderByDesc("$table.DATETIME");

            // Join with 'supplier' table if needed
            if ($table !== 'supplier') {
                $vaData->leftJoin('supplier as s', 's.KODE', '=', $table . '.SUPPLIER')
                    ->addSelect('s.NAMA as SUPPLIER');
            }

            $vaData = $vaData->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil mengambil data',
                'data' => $vaData,
                'total' => count($vaData),
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
            // Daftar model yang akan dicari dengan title yang sesuai
            $models = [
                ['model' => Po::class, 'title' => 'Purchase Order', 'totModel' => TotPo::class],
                ['model' => Pembelian::class, 'title' => 'Pembelian Penerimaan Barang', 'totModel' => TotPembelian::class],
                ['model' => RtnPembelian::class, 'title' => 'Retur Pembelian', 'totModel' => TotRtnPembelian::class]
            ];

            $result = [];
            $totData = [];

            foreach ($models as $entry) {
                $model = $entry['model'];
                $currentTitle = $entry['title'];
                $data = $model::with('stock')->where('FAKTUR', $FAKTUR)->get();

                if ($data->isNotEmpty()) {
                    $totModelQuery = $entry['totModel']::query()->where('FAKTUR', $FAKTUR);

                    // Tambahkan relasi 'supplier' jika model memiliki relasi tersebut
                    if (method_exists($entry['totModel'], 'supplier')) {
                        $totModelQuery->with('supplier');
                    }

                    // Tambahkan relasi 'gudang' jika model memiliki relasi tersebut
                    if (method_exists($entry['totModel'], 'gudang')) {
                        $totModelQuery->with('gudang');
                    }

                    $totModel = $totModelQuery->first();

                    // Jika model tidak ditemukan, lewati iterasi ini
                    if (!$totModel) {
                        continue;
                    }

                    $totData = [
                        'TITLE' => $currentTitle,
                        'FAKTUR' => $FAKTUR,
                        'FAKTURASLI' => $totModel->FAKTURASLI ?? null,
                        'TGL' => $totModel->TGL ?? null,
                        'TGLDO' => $totModel->TGLDO ?? null,
                        'PAYMENTDATE' => $totModel->JTHTMP ?? null,
                        'SUPPLIER' => $totModel->SUPPLIER ?? null,
                        'NAMASUPPLIER' => $totModel->supplier->NAMA ?? null,
                        'ALAMATSUPPLIER' => $totModel->supplier->ALAMAT ?? null,
                        'SUBTOTAL' => $totModel->SUBTOTAL ?? null,
                        'DISCOUNT' => $totModel->DISCOUNT ?? null,
                        'PPN' => $totModel->PAJAK ?? null,
                        'TOTAL' => $totModel->TOTAL ?? null,
                        'GUDANG' => $totModel->GUDANG ?? null,
                        'KETGUDANG' => $totModel->gudang->KETERANGAN ?? null,
                        'FAKTURPO' => $totModel->PO ?? $totModel->FAKTURPO ?? null,
                        'FAKTURPEMBELIAN' => $totModel->FAKTURPEMBELIAN ?? null,
                    ];

                    // Menambahkan data item ke dalam result
                    foreach ($data as $item) {
                        $result[] = $item;
                    }
                }
            }

            if (count($result) > 0) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil mengambil data',
                    'totData' => $totData,
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data kosong',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }



    public function getDataByFakturSebelumTitle(Request $request)
    {
        $FAKTUR = $request->FAKTUR;
        try {
            // Daftar model yang akan dicari
            $models = [Po::class, Pembelian::class, RtnPembelian::class];
            $result = [];

            foreach ($models as $model) {
                $data = $model::with('stock')->where('FAKTUR', $FAKTUR)->get();

                if ($data->isNotEmpty()) {
                    $result = array_merge($result, $data->toArray());
                }
            }

            if (!empty($result)) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil',
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Data tidak ada',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
