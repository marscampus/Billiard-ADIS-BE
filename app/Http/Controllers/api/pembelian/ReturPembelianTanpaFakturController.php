<?php

namespace App\Http\Controllers\api\pembelian;

use App\Helpers\ApiResponse;
use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\master\Stock;
use App\Models\pembelian\RtnPembelian;
use App\Models\pembelian\TotRtnPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReturPembelianTanpaFakturController extends Controller
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
        try {
            $limit = 10;
            $startDate = $request->START_DATE;
            $endDate = $request->END_DATE;
            $field = $request->FIELD;
            $value = $request->VALUE;

            // Pengecekan request JSON kosong
            if ((!$startDate && $endDate) || ($startDate && !$endDate)) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Tanggal Awal/Tanggal Akhir kosong',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            $rtnPembelian = TotRtnPembelian::with('supplier');
            if ($startDate !== null && $endDate !== null) {
                $rtnPembelian->whereBetween('Tgl', [$startDate, $endDate]);
            }

            if ($field && $value) {
                $rtnPembelian->where($field, 'LIKE', '%' . $value . '%');
            }
            $rtnPembelian->whereNull('FAKTURPEMBELIAN');
            $rtnPembelian->orderBy('FAKTUR', 'DESC');
            $rtnPembelian = $rtnPembelian->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $rtnPembelian,
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

    public function getBarcode(Request $request)
    {
        $KODE_TOKO = $request->KODE_TOKO;
        try {
            $row = Stock::where('KODE_TOKO', 'like', '%' . $KODE_TOKO)
                ->where('KODE_TOKO', '<>', 'kode')
                ->count();
            if ($row >= 1) {
                if ($row > 1) {
                    $query = Stock::where('KODE_TOKO', $KODE_TOKO)->get();
                } else {
                    $query = Stock::where('KODE_TOKO', 'like', '%' . $KODE_TOKO)->get();
                }
                if ($query->isNotEmpty()) {
                    $KODE = $query[0]->KODE;
                    $data[] = [
                        'KODE' => $KODE,
                        'NAMA' => $query[0]->NAMA,
                        'SATUAN' => $query[0]->SATUAN,
                        'PPN' => empty($query[0]->PPN) ? '0' : $query[0]->PPN,
                        'HARGABELI' => GetterSetter::getHargaBeli($KODE),
                        'HARGAJUAL' => GetterSetter::getHargaJual($KODE),
                        'EXPIRED' => Carbon::now()->toDateString()
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $data,
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

    public function store(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $messages = config('validate.validation');
            $vaValidator = validator::make($request->all(), [
                'FAKTUR' => 'required|max:20',
                'TGL' => 'date',
                'GUDANG' => 'max:4',
                'SUPPLIER' => 'max:6',
                'SUBTOTAL' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                'DISCOUNT' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                'KODE' => 'max:20',
                'BARCODE' => 'max:20',
                'QTY' => 'numeric|digits_between:1,10',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                'JUMLAH' => 'numeric|min:0',
                'KETERANGAN' => 'max:30'
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
            // dd('dah lewat');
            $faktur = $request->FAKTUR;
            $tgl = $request->TGL;
            $totrtnpembelian = [
                'FAKTUR' => $faktur,
                'TGL' => $tgl,
                'JTHTMP' => Carbon::now(),
                'GUDANG' => $request->GUDANG, //GET CONFIG
                'SUPPLIER' => $request->SUPPLIER,
                'SUBTOTAL' => $request->SUBTOTAL,
                'PAJAK' => $request->PAJAK,
                'DISCOUNT' => $request->DISCOUNT,
                'TOTAL' => $request->TOTAL,
                'KETERANGAN' => $request->KETERANGAN,
                'DATETIME' => Carbon::now(),
                "USERNAME" => $cUser //GET CONFIG
            ];
            foreach ($request->input('tabelTransaksiRtnPembelianFaktur') as $item) {
                try {
                    $allIterationsSuccessful = true;
                    $rtnpembelian = [
                        'FAKTUR' => $faktur,
                        'TGL' => $tgl,
                        'KODE' => $item['KODE'],
                        'BARCODE' => $item['BARCODE'],
                        'QTY' => $item['QTY'],
                        'HARGA' => $item['HARGA'],
                        // 'HJ' => $item['HJ'],
                        'SATUAN' => $item['SATUAN'],
                        'DISCOUNT' => $item['DISCOUNT'],
                        'JUMLAH' => $item['JUMLAH']
                    ];
                    RtnPembelian::create($rtnpembelian);
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }
            TotRtnPembelian::create($totrtnpembelian);
            Upd::updRekeningReturPembelian($faktur);
            Upd::updKartuStockReturPembelian($faktur);
            GetterSetter::setLastKodeRegister('RB');

            if ($allIterationsSuccessful) {
                // return response()->json(['status' => 'success']);
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Menyimpan Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Salah Satu data dari iterasi mengalami error',
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

    public function getDataEdit(Request $request)
    {
        try {
            $FAKTUR = $request->FAKTUR;
            $result = [];
            $totrtnpembelian = TotRtnPembelian::with('supplier')
                ->where('FAKTUR', $FAKTUR)
                ->first();
            $rtnpembelian = RtnPembelian::with('stock')
                ->where('FAKTUR', $FAKTUR)
                ->get();
            // dd($rtnpembelian);
            if ($totrtnpembelian && $rtnpembelian) {
                $result = [
                    "FAKTUR" => $totrtnpembelian->FAKTUR,
                    "TGL" => $totrtnpembelian->TGL,
                    "JTHTMP" => $totrtnpembelian->JTHTMP,
                    "PERSDISC" => $totrtnpembelian->PERSDISC, // INI BENTUKNYA PERSEN
                    "PERSPPN" => $totrtnpembelian->PPN, // INI BENTUKNYA PERSEN
                    "FAKTURASLI" => $totrtnpembelian->FAKTURASLI,
                    "SUPPLIER" => $totrtnpembelian->SUPPLIER,
                    "NAMA" => $totrtnpembelian->supplier->NAMA,
                    "GUDANG" => $totrtnpembelian->GUDANG,
                    "KETGUDANG" => $totrtnpembelian->gudang->KETERANGAN,
                    "ALAMAT" => $totrtnpembelian->supplier->ALAMAT,
                    "KOTA" => $totrtnpembelian->supplier->KOTA,
                    "SUBTOTAL" => $totrtnpembelian->SUBTOTAL,
                    "DISCOUNT" => $totrtnpembelian->DISCOUNT, // INI BENTUKNYA NOMINAL
                    "PPN" => $totrtnpembelian->PAJAK, // INI BENTUKNYA NOMINAL
                    "PEMBULATAN" => $totrtnpembelian->PEMBULATAN,
                    "TOTAL" => $totrtnpembelian->TOTAL,
                    "TUNAI" => $totrtnpembelian->TUNAI,
                    "KETERANGAN" => $totrtnpembelian->KETERANGAN,
                    "HUTANG" => $totrtnpembelian->HUTANG
                ];
                foreach ($rtnpembelian as $rpf) {
                    // dd($rpf);
                    $result['rtnpembelianfaktur'][] = [
                        "KODE" => $rpf->KODE,
                        "BARCODE" => $rpf->KODE_TOKO,
                        "NAMA" => optional($rpf->stock)->NAMA,
                        "RETUR" => $rpf->QTY,
                        "SATUAN" => $rpf->SATUAN,
                        "HARGABELI" => $rpf->HARGA,
                        "PPN" => $rpf->PPN,
                        "DISCOUNT" => $rpf->DISCOUNT,
                        "JUMLAH" => $rpf->JUMLAH
                    ];
                }
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            }
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data tidak ditemukan',
                'datetime' => date('Y-m-d H:i:s'),
            ], 400);

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
        $cUser = Func::dataAuth($request);
        try {
            $messages = config('validate.validation');
            $vaValidator = validator::make($request->all(), [
                'FAKTUR' => 'required|max:20',
                'TGL' => 'date',
                'GUDANG' => 'max:4',
                'SUPPLIER' => 'max:6',
                'SUBTOTAL' => 'numeric|min:0',
                'PAJAK' => 'numeric|min:0',
                'DISCOUNT' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                'KODE' => 'max:20',
                'BARCODE' => 'max:20',
                'QTY' => 'numeric|digits_between:1,10',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                'JUMLAH' => 'numeric|min:0',
                'KETERANGAN' => 'max:30'
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
            $totrtnpembelian = [
                'FAKTURASLI' => $request->FAKTURASLI,
                'TGL' => $tgl,
                'JTHTMP' => $request->JTHTMP,
                'GUDANG' => $request->gudang,
                'SUPPLIER' => $request->SUPPLIER,
                'PPN' => round($request->PPN),
                'PERSDISC' => round($request->PERSDISC),
                'SUBTOTAL' => round($request->SUBTOTAL),
                'PAJAK' => round($request->PAJAK),
                'DISCOUNT' => round($request->DISCOUNT),
                'PEMBULATAN' => round($request->PEMBULATAN),
                'TOTAL' => round($request->TOTAL),
                'TUNAI' => round($request->TUNAI),
                'HUTANG' => round($request->HUTANG),
                'KETERANGAN' => $request->KETERANGAN,
                'DATETIME' => Carbon::now(),
                'USERNAME' => $cUser //GET CONFIG
            ];
            $existingRtnPembelian = RtnPembelian::where('FAKTUR', $faktur)->delete();
            foreach ($request->input('tabelTransaksiRtnPembelianFaktur') as $item) {
                $kodeP = $item['KODE'];
                $hargaP = $item['HARGA'];

                $rtnpembelian = [
                    'FAKTUR' => $faktur,
                    'TGL' => $tgl,
                    'KODE' => $kodeP,
                    'QTY' => round($item['QTY']),
                    'HARGA' => $hargaP,
                    'SATUAN' => round($item['SATUAN']),
                    'DISCOUNT' => round($item['DISCOUNT']),
                    'JUMLAH' => round($item['JUMLAH'])
                ];
                RtnPembelian::create($rtnpembelian);
            }
            TotRtnPembelian::where('FAKTUR', $faktur)->update($totrtnpembelian);
            Upd::updRekeningReturPembelian($faktur);
            Upd::updKartuStockReturPembelian($faktur);

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

    public function delete(Request $request)
    {
        try {
            $totrtnpembelianfaktur = TotRtnPembelian::findOrFail($request->FAKTUR);
            $totrtnpembelianfaktur->delete();
            $rtnpembelianfaktur = RtnPembelian::findOrFail($request->FAKTUR);
            $rtnpembelianfaktur->delete();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menghapus Data',
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
