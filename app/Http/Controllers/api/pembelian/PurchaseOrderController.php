<?php

namespace App\Http\Controllers\api\pembelian;

use App\Helpers\ApiResponse;
use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionController;
use App\Models\kasir\Kasir;
use App\Models\master\Stock;
use App\Models\master\Supplier;
use App\Models\pembelian\Po;
use App\Models\pembelian\StockSupplier;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotPo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Env;

class PurchaseOrderController extends Controller
{

    public function getFaktur(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        try {
            $response = GetterSetter::getLastFaktur($KODE, $LEN);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $response,
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
            $vaData = DB::table('totpo as t')
                ->select(
                    't.faktur',
                    't.keterangan',
                    't.tgl',
                    't.jthtmp',
                    's.nama as supplier',
                    't.total'
                )
                ->leftJoin('supplier as s', 's.kode', '=', 't.supplier')
                ->whereBetween('t.tgl', [$request->TglAwal, $request->TglAkhir])
                ->get();
            if ($vaData) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $vaData,
                    'total_data' => count($vaData),
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

    public function getBarcode(Request $request)
    {
        $KODE_TOKO = $request->KODE_TOKO;
        $jml = 1;
        try {
            if (strpos($KODE_TOKO, '*') !== false) {
                $jml = substr($KODE_TOKO, 0, strpos($KODE_TOKO, '*'));
                // $jml = func::getInt2StringFormat($jml);
                $KODE_TOKO = substr($KODE_TOKO, strpos($KODE_TOKO, '*') + 1);
            }

            $row = Stock::where('KODE_TOKO', 'LIKE', '%' . $KODE_TOKO . '%')
                ->where('KODE_TOKO', '<>', 'kode')
                ->count();
            if ($row >= 1) {
                $query = Stock::where('KODE_TOKO', 'LIKE', '%' . $KODE_TOKO . '%')->get();
                if ($query) {
                    $KODE = $query[0]->KODE;
                    $data[] = [
                        'KODE' => $KODE,
                        'BARCODE' => $query[0]->KODE_TOKO,
                        'NAMA' => $query[0]->NAMA,
                        'SATUAN' => $query[0]->SATUAN,
                        'HARGABELI' => $query[0]->HB,
                        'HJ' => $query[0]->HJ,
                        'DISCOUNT' => empty($query[0]->DISCOUNT) ? '0' : $query[0]->DISCOUNT,
                        'PPN' => empty($query[0]->PPN) ? '0' : $query[0]->PPN,
                        'TERIMA' => $jml
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
        $cUser = Func::dataAuth($request);
        // dd($cUser);
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [

                'FAKTUR' => 'required|max:20|unique:totpo,FAKTUR',
                'TGL' => 'date',
                'KODE' => 'max:20',
                'QTY' => 'numeric|min:0',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                // 'DISCOUNT' => 'numeric|digits_between:1,6',
                // 'PPN' => 'numeric|min:0',
                'JUMLAH' => 'numeric|min:0',
                'FAKTURASLI' => 'max:255',
                'JTHTMP' => 'date',
                'SUPPLIER' => 'max:6',
                // 'PERSDISC' => 'numeric|digits_between:1,6',
                'SUBTOTAL' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                // 'PEMBULATAN' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                // 'TUNAI' => 'numeric|min:0',
                // 'HUTANG' => 'numeric|digits_between:1,16'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'numeric' => 'Kolom :attribute harus angka',
                'date' => 'Kolom :attribute harus berupa tanggal',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $faktur = $request->FAKTUR;
            $tgl = $request->TGL;
            // if ($request->PPN == true) {
            // $ppn = env('VAR_PPN');
            // } else {
            //     $ppn = '0';
            // }
            $kode = "";
            $supplier = $request->SUPPLIER;
            $cabangEntry = '101'; // GET CONFIG
            $totpo = TotPo::create([
                'FAKTUR' => $faktur,
                'FAKTURASLI' => $request->FAKTURASLI,
                'TGL' => $tgl,
                'JTHTMP' => $request->JTHTMP,
                'TGLDO' => $request->TGLDO,
                'SUPPLIER' => $supplier,
                'PPN' => round($request->PPN) ?? 0,
                'PERSDISC' => round($request->PERSDISC) ?? 0,
                'SUBTOTAL' => round($request->SUBTOTAL) ?? 0,
                'PAJAK' => round($request->PAJAK) ?? 0,
                'DISCOUNT' => round($request->DISCOUNT) ?? 0,
                'TOTAL' => round($request->TOTAL) ?? 0,
                'KETERANGAN' => $request->KETERANGAN,
                'CABANGENTRY' => $cabangEntry, // GET CONFIG
                'USERNAME' => $cUser, //GET CONFIG
                'DATETIME' => Carbon::now()
            ]);
            foreach ($request->input('tabelTransaksiPo') as $item) {
                try {
                    $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
                    $barcode = $item['BARCODE'];
                    $cariKode = Stock::where('KODE_TOKO', $barcode)->first();
                    if ($cariKode) {
                        $kode = $cariKode->KODE;
                    }
                    Po::create([
                        'FAKTUR' => $faktur,
                        'TGL' => $tgl,
                        'KODE' => $kode,
                        'BARCODE' => $barcode,
                        'QTY' => round($item['QTY']) ?? 0,
                        'HARGA' => round($item['HARGA']) ?? 0,
                        'SATUAN' => $item['SATUAN'],
                        'DISCOUNT' => round($item['DISCOUNT']) ?? 0,
                        'PPN' => round($item['PPN']) ?? 0,
                        'TGLEXP' => $item['TGLEXP'],
                        'JUMLAH' => round($item['JUMLAH']) ?? 0
                    ]);

                    $stock_supplier = [
                        'KODE' => $kode,
                        'SUPPLIER' => $supplier,
                        'TGL' => $tgl
                    ];
                    // Cek apakah entri dengan kode dan supplier yang sama sudah ada dalam database
                    $existingStockSupplier = StockSupplier::where('KODE', $kode)
                        ->where('SUPPLIER', $supplier)
                        ->first();

                    if ($existingStockSupplier) {
                        // Jika entri sudah ada, perbarui data
                        $existingStockSupplier->fill($stock_supplier); // Isi model dengan data baru
                        $existingStockSupplier->save(); // Simpan perubahan
                    } else {
                        $stockSupplier = new StockSupplier();
                        $stockSupplier->KODE = $kode;
                        $stockSupplier->SUPPLIER = $supplier;
                        $stockSupplier->TGL = $tgl;
                        $stockSupplier->save();
                    }
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }
            // GetterSetter::setLastFaktur('PO');
            GetterSetter::setLastKodeRegister('PO');
            if ($allIterationsSuccessful) {
                // return response()->json(['status' => 'success']);
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Menyimpan Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Terjadi kesalahan saat proses iterasi. Iterasi di hentikan',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);                // return response()->json(['status' => 'error', 'message' => 'One or more iterations failed']);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function getDataEdit(Request $request)
    {
        try {
            $FAKTUR = $request->FAKTUR;
            $result = [];
            $totpo = DB::table('totpo as t')
                ->select('t.faktur', 't.tgl', 't.tgldo', 't.jthtmp', 't.persdisc', 't.ppn', 't.fakturasli', 't.supplier', 's.nama', 's.alamat', 's.kota', 't.keterangan')
                ->leftJoin('supplier as s', 's.kode', '=', 't.supplier')
                ->where('t.faktur', '=', $FAKTUR)
                ->first();
            $po = DB::table('po as p')
                ->select('p.kode', 's.kode_toko', 's.nama', 'p.qty', 'p.satuan', 'p.harga', 'p.discount', 'p.ppn', 'p.jumlah')
                ->leftJoin('stock as s', 's.kode', '=', 'p.kode')
                ->where('p.faktur', '=', $FAKTUR)
                ->get();
            if ($totpo && $po) {
                $existsPembelian = TotPembelian::where('PO', $FAKTUR)
                    ->exists();
                if ($existsPembelian) {
                    return response()->json([
                        'status' => self::$status['GAGAL'],
                        'message' => 'Data sudah di transaksikan',
                        'datetime' => date('Y-m-d H:i:s')
                    ], 400);
                }

                $result = [
                    "FAKTUR" => $totpo->faktur,
                    "TGL" => $totpo->tgl,
                    "TGLDO" => $totpo->tgldo,
                    "JTHTMP" => $totpo->jthtmp,
                    "PERSDISC" => round($totpo->persdisc), // INI BENTUKNYA PERSEN
                    "PPN" => round($totpo->ppn), // INI BENTUNYA PERSEN
                    "FAKTURASLI" => $totpo->fakturasli,
                    "SUPPLIER" => $totpo->supplier,
                    "NAMA" => $totpo->nama,
                    "ALAMAT" => $totpo->alamat,
                    "KOTA" => $totpo->kota,
                    "KETERANGAN" => $totpo->keterangan
                ];
                foreach ($po as $po) {
                    $result['po'][] = [
                        "KODE" => $po->kode,
                        "BARCODE" => $po->kode_toko,
                        "NAMA" => $po->nama,
                        "QTY" => round($po->qty),
                        "SATUAN" => $po->satuan,
                        "HARGABELI" => round($po->harga),
                        "DISCOUNT" => round($po->discount),
                        "PPN" => round($po->ppn),
                        "JUMLAH" => round($po->jumlah)
                    ];
                }
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Sukses',
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            }

            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data tidak ditemukan',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [
                'FAKTUR' => 'required|max:20',
                'TGL' => 'date',
                'KODE' => 'max:20',
                'QTY' => 'numeric|min:0',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                'JUMLAH' => 'numeric|min:0',
                'FAKTURASLI' => 'max:255',
                'JTHTMP' => 'date',
                'SUPPLIER' => 'max:6',
                'SUBTOTAL' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kolom :attribute sudah ada di database.',
                'numeric' => 'Kolom :attribute harus angka',
                'date' => 'Kolom :attribute harus berupa tanggal',
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }
            $faktur = $request->FAKTUR;
            TotPo::where('FAKTUR', $faktur)->update([
                'FAKTURASLI' => $request->FAKTURASLI,
                'TGL' => $request->TGL,
                'JTHTMP' => $request->JTHTMP,
                'TGLDO' => $request->TGLDO,
                'SUPPLIER' => $request->SUPPLIER,
                'PPN' => round($request->PPN),
                'PERSDISC' => round($request->PERSDISC),
                'SUBTOTAL' => round($request->SUBTOTAL),
                'PAJAK' => round($request->PAJAK),
                'DISCOUNT' => round($request->DISCOUNT),
                'TOTAL' => round($request->TOTAL),
                'KETERANGAN' => $request->KETERANGAN,
                'USERNAME' => $cUser, //GET CONFIG
                'CABANGENTRY' => '101', // GET CONFIG
                'DATETIME' => Carbon::now()
            ]);
            // Check if Po with the given 'FAKTUR' exists
            $existingPo = Po::where('FAKTUR', $faktur)->delete();
            foreach ($request->input('tabelTransaksiPo') as $item) {
                try {
                    $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
                    // Create a new Po record
                    $barcode = $item['BARCODE'];
                    $cariKode = Stock::where('KODE_TOKO', $barcode)->first();
                    if ($cariKode) {
                        $kode = $cariKode->KODE;
                    }
                    Po::create([
                        'FAKTUR' => $faktur,
                        'TGL' => $request->TGL,
                        'KODE' => $kode,
                        'BARCODE' => $barcode,
                        'QTY' => round($item['QTY']),
                        'HARGA' => round($item['HARGA']),
                        'SATUAN' => $item['SATUAN'],
                        'DISCOUNT' => round($item['DISCOUNT']),
                        'PPN' => round($item['PPN']),
                        'TGLEXP' => $item['TGLEXP'],
                        'JUMLAH' => round($item['JUMLAH'])
                    ]);
                } catch (\Throwable $th) {
                    // dd($th);
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }
            if ($allIterationsSuccessful) {
                // return response()->json(['status' => 'success']);
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Menyimpan Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Terjadi kesalahan saat proses iterasi. Iterasi di hentikan',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);                // return response()->json(['status' => 'error', 'message' => 'One or more iterations failed']);
            }
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
            $existsPembelian = TotPembelian::where('PO', $request->FAKTUR)
                ->exists();
            if ($existsPembelian) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Data sudah ditraksaksikan. Tidak boleh dihapus',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }
            $totpo = TotPo::findOrFail($request->FAKTUR);
            $totpo->delete();
            $po = Po::findOrFail($request->FAKTUR);
            $po->delete();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menghapus Data',
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

    public function repeatPO(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $vaData = DB::table('totpo as t')
                ->select(
                    't.Faktur',
                    't.Supplier',
                    's.Nama',
                    's.Alamat',
                    't.Tgl',
                    't.JthTmp',
                    't.PersDisc',
                    't.SubTotal',
                    't.PPN',
                    't.Pajak',
                    't.Discount',
                    't.Keterangan',
                    't.DateTime',
                    'u.name as FullName'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 't.Supplier')
                ->leftJoin('users as u', 'u.email', '=', 't.Username');
            if (!empty($vaRequestData['filters'])) {
                foreach ($vaRequestData['filters'] as $filterField => $filterValue) {
                    $vaData->where($filterField, "LIKE", '%' . $filterValue . '%');
                }
            }
            $vaData->orderByDesc('t.Faktur');
            $limit = 25; // Set the limit here
            $vaData->take($limit); // Apply the limit
            $vaData = $vaData->get();
            $results = [];
            foreach ($vaData as $d) {
                $result = [
                    "FAKTUR" => $d->Faktur,
                    "SUPPLIER" => $d->Supplier,
                    "NAMA" => $d->Nama,
                    "ALAMAT" => $d->Alamat,
                    "TGL" => $d->Tgl,
                    "JTHTMP" => $d->JthTmp,
                    "SUBTOTAL" => $d->SubTotal,
                    "PERSDISC" => $d->PersDisc,
                    "PPN" => $d->PPN,
                    "PAJAK" => $d->Pajak,
                    "DISCOUNT" => $d->Discount,
                    "KETERANGAN" => $d->Keterangan,
                    "DATETIME" => $d->DateTime,
                    "USERNAME" => $d->FullName
                ];
                $results[] = $result; // Add the result to the results array
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $results,
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

    public function getDataByRepeatPO(Request $request)
    {
        try {
            $faktur = $request->FAKTUR;
            $totpo = TotPo::with('supplier')
                ->where('FAKTUR', $faktur)
                ->first();
            $po = Po::with('stock')
                ->where('FAKTUR', $faktur)
                ->get();
            if ($totpo) {
                $result = [
                    "SUPPLIER" => optional($totpo->supplier)->KODE,
                    "NAMA" => optional($totpo->supplier)->NAMA,
                    "ALAMAT" => optional($totpo->supplier)->ALAMAT,
                    "PERSDISC" => $totpo->PERSDISC,
                    "PPN" => $totpo->PPN,
                ];
                foreach ($po as $s) {
                    $result['detail'][] = [
                        "KODE" => $s->KODE,
                        "BARCODE" => $s->BARCODE,
                        "NAMA" => optional($s->stock)->NAMA,
                        "QTY" => $s->QTY,
                        "SATUAN" => $s->SATUAN,
                        "HARGABELI" => $s->HARGA,
                        "PPN" => $s->PPN,
                        "DISCOUNT" => $s->DISCOUNT,
                        "JUMLAH" => $s->JUMLAH
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $result,
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
    // ----------------------------------------------------------------------------------------------------------< REORDER >
    public function reorderPO(Request $request)
    {
        try {
            // Inisialisasi query builder dengan eager loading relasi
            $query = StockSupplier::with('stock', 'supplier')->orderBy('TGL', 'DESC');

            // Terapkan filter jika ada
            if ($filters = $request->input('filters')) {
                foreach ($filters as $filterField => $filterValue) {
                    $query->where($filterField, 'LIKE', '%' . $filterValue . '%');
                }
            }

            // Ambil data dengan paginasi
            $limit = 25;
            $stockSuppliers = $query->paginate($limit);

            // Siapkan hasil
            $results = $stockSuppliers->map(function ($s) {
                return [
                    "KODE" => $s->KODE,
                    "SUPPLIER" => $s->SUPPLIER,
                    "NAMA" => optional($s->supplier)->NAMA,
                    "ALAMAT" => optional($s->supplier)->ALAMAT,
                    "TELEPON" => optional($s->supplier)->TELEPON,
                    "NAMA_CP_1" => optional($s->supplier)->NAMA_CP_1,
                    "TELEPON_CP_1" => optional($s->supplier)->TELEPON_CP_1,
                    // "REORDER" => $s->REORDER,
                    // "MINSTOCK" => $s->MINSTOCK,
                    // "TGL" => $s->TGL
                ];
            });

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $results,
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

    public function getDataByReorderPO(Request $request)
    {
        try {
            $supplier = $request->SUPPLIER;
            $barcode = "";
            $dataSupplier = Supplier::where('KODE', $supplier)->first();
            $stock_supplier = StockSupplier::with('stock')
                ->where('SUPPLIER', $supplier);

            // Terapkan filter jika diperlukan
            if (!empty($request->filters)) {
                foreach ($request->filters as $k => $v) {
                    // Menambahkan kondisi WHERE dengan menggunakan RIGHT dan LIKE pada kolom-kolom yang sesuai
                    $stock_supplier->whereRaw("$k LIKE ?", ['%' . $v . '%']);
                }
            }

            // Terapkan limit jika diperlukan
            $limit = 25;
            $stock_supplier->take($limit);

            $stock_supplier = $stock_supplier->get();

            if ($dataSupplier && $stock_supplier) {
                $result = [
                    "KODESUPPLIER" => $dataSupplier->KODE,
                    "SUPPLIER" => $dataSupplier->NAMA,
                    "ALAMAT" => $dataSupplier->ALAMAT,
                    "KOTA" => $dataSupplier->KOTA
                ];
                foreach ($stock_supplier as $s) {
                    $kode = $s->KODE;
                    $cariBarcode = Stock::where('KODE', $kode)->first();
                    if ($cariBarcode) {
                        $barcode = $cariBarcode->KODE_TOKO;
                    }
                    // dd($cariBarcode);
                    $result['detail'][] = [
                        "KODE" => $kode,
                        "BARCODE" => $barcode,
                        "NAMA" => optional($s->stock)->NAMA,
                        "QTY" => $s->REORDER,
                        "SATUAN" => optional($s->stock)->SATUAN,
                        "HARGABELI" => optional($s->stock)->HB,
                        "PPN" => optional($s->stock)->PAJAK,
                        "DISCOUNT" => optional($s->stock)->DISCOUNT,
                        "JUMLAH" => optional($s->stock)->JUMLAH,

                        // "KODE" => $kode,
                        // "BARCODE" => $barcode,
                        // "NAMA" => optional($s->stock)->NAMA,
                        // "QTY" => $s->REORDER,
                        // "SATUAN" => optional($s->stock)->SATUAN,
                        // "HARGABELI" => optional($s->stock)->HB
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $result,
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

    public function print(Request $request)
    {
        $faktur = $request->FAKTUR;
        $totpo = TotPo::with('supplier')
            ->where('FAKTUR', $faktur)
            ->first();
        // dd($totpo);
        $po = Po::with('stock')
            ->where('FAKTUR', $faktur)
            ->get();
        return view('pembelian.printPo', compact('totpo', 'po'));
    }
}
