<?php

namespace App\Http\Controllers\api\pembelian;

use App\Helpers\Assist;
use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuHutang;
use App\Models\master\Stock;
use App\Models\pembelian\Pembelian;
use App\Models\pembelian\Po;
use App\Models\pembelian\RtnPembelian;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotPo;
use App\Models\pembelian\TotRtnPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Return_;

class ReturPembelianDenganFakturController extends Controller
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
        $vaValidator = validator::make($request->all(), [
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
            $startDate = $request->TglAwal;
            $endDate = $request->TglAkhir;

            $vaData = DB::table('totrtnpembelian AS tr')
                ->select(
                    'tr.FAKTUR',
                    'tr.FAKTURPEMBELIAN',
                    'tr.KETERANGAN',
                    'tr.TGL',
                    'tr.JTHTMP',
                    'tr.TOTAL',
                    's.NAMA',
                    DB::raw('(SELECT IFNULL(SUM(QTY), 0) FROM rtnpembelian WHERE FAKTUR = tr.FAKTUR) AS TOTALRETUR')
                )
                ->leftJoin('supplier AS s', 'tr.SUPPLIER', '=', 's.KODE')
                ->whereBetween('tr.Tgl', [$startDate, $endDate])
                ->orderBy('tr.FAKTUR', 'DESC')
                ->get();
            // JIKA REQUEST SUKSES
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

    public function getFakturPembelian()
    {
        $cSession_Cabang = '101'; // Ganti dengan nilai yang sesuai
        $limit = 100;
        try {
            $query = DB::table('totpo AS t')
                ->select(
                    'tp.FAKTUR',
                    't.FAKTUR AS FAKTURPO',
                    's.NAMA AS SUPPLIER',
                    't.TOTAL',
                    't.TGL',
                    't.JTHTMP',
                    't.KETERANGAN',
                    DB::raw('IFNULL(SUM(p.QTY), 0) AS TERIMABRG'),
                    DB::raw('(SELECT IFNULL(SUM(QTY), 0) FROM po WHERE faktur = t.FAKTUR) AS POBRG'),
                    DB::raw('(SELECT IFNULL(SUM(QTY), 0) FROM rtnpembelian WHERE faktur = tr.FAKTUR) AS BRGRETUR')
                )
                ->leftJoin('supplier AS s', 's.Kode', '=', 't.Supplier')
                ->leftJoin('totpembelian AS tp', 'tp.PO', '=', 't.Faktur')
                ->leftJoin('pembelian AS p', 'p.Faktur', '=', 'tp.Faktur')
                ->leftJoin('totrtnpembelian AS tr', 'tr.FAKTURPO', '=', 't.Faktur')
                // ->where('t.CABANGENTRY', $cSession_Cabang)
                ->whereNotNull('tp.FAKTUR')
                ->groupBy(
                    'tp.FAKTUR'
                    // 't.Faktur',
                    // 's.Nama',
                    // 't.TOTAL',
                    // 't.TGL',
                    // 't.JTHTMP',
                    // 't.KETERANGAN',
                    // 'tr.FAKTUR'
                )
                ->orderByDesc('t.TGL')
                ->orderByDesc('t.ID');

            $results = $query->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $results,
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

    public function getDataByFakturPembelian(Request $request)
    {
        try {

            $faktur = $request->FAKTUR;
            $tglDO = '';
            $keterangan = '';
            $exists = TotRtnPembelian::where('FAKTUR', $faktur)
                ->exists();
            if ($exists) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'DATA TELAH DIRETUR, TIDAK DAPAT DIPROSES',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            $totpembelian = TotPembelian::with('supplier')
                ->with('gudang')
                ->where('FAKTUR', $faktur)
                ->first();
            $pembelian = Pembelian::with('stock')
                ->where('FAKTUR', $faktur)
                ->where('HARGA', '>', '0')->get();
            if ($totpembelian && count($pembelian) > 0) {
                $fakturPO = $totpembelian->PO;
                $totpo = TotPo::where('FAKTUR', $fakturPO)->first();
                if ($totpo) {
                    $persdisc = $totpo->PERSDISC;
                    $ppn = $totpo->PPN;
                    $tglDO = $totpo->TGLDO;
                    $keterangan = $totpo->KETERANGAN;
                }

                $result = [
                    "FAKTUR" => $faktur,
                    "FAKTURASLI" => $totpembelian->FAKTURASLI,
                    "TGLPO" => $totpembelian->TGL,
                    "JTHTMP" => $totpembelian->JTHTMP,
                    "TGLDO" => $tglDO,
                    "GUDANG" => $totpembelian->GUDANG,
                    "KETGUDANG" => optional($totpembelian->gudang)->KETERANGAN,
                    // "PERSDISC" => $totpembelian->PERSDISC,
                    "KETERANGAN" => $keterangan,
                    "SUPPLIER" => $totpembelian->SUPPLIER,
                    "NAMA" => optional($totpembelian->supplier)->NAMA,
                    "ALAMAT" => optional($totpembelian->supplier)->ALAMAT,
                    "KOTA" => optional($totpembelian->supplier)->KOTA,
                    "PERSDISC" => $persdisc,
                    "PPN" => $ppn,
                    'detail' => []
                ];
                $nQtyTotal = 0;
                foreach ($pembelian as $pe) {
                    $nQtyTotal += $pe->QTY;
                    $nQtyPO = 0;
                    $po = Po::where('FAKTUR', $fakturPO)
                        ->where('KODE', $pe->KODE)
                        ->limit(1)
                        ->get();
                    foreach ($po as $p) {
                        $nQtyPO = $p->QTY;
                    }
                    $harga = $pe->HARGA;
                    $retur = $pe->RETUR;
                    $kodeProgram = $pe->KODE;
                    $result['detail'][] = [
                        "KODE" => $pe->KODE,
                        "BARCODE" => optional($pe->stock)->KODE_TOKO,
                        "NAMA" => optional($pe->stock)->NAMA,
                        "QTYPO" => $nQtyPO,
                        "TERIMABRG" => $this->terimaBarang($fakturPO, $kodeProgram),
                        "RETUR" => $retur,
                        "SATUAN" => $pe->SATUAN,
                        "HARGA" => $harga,
                        "TOTAL" => $harga * $retur,
                        "DISCOUNT" => $pe->DISCOUNT,
                        "PPN" => $pe->PPN,
                        "JUMLAH" => $pe->JUMLAH
                    ];
                }
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'DATA TIDAK DITEMUKAN, TRANSAKSI TIDAK BISA DILANJUTKAN!',
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

    public function terimaBarang($fakturPO, $kodeProgram)
    {
        $nTerimaBarang = 0;
        $terimaBrg = DB::table('pembelian AS p')
            ->leftJoin('totpembelian AS t', 't.Faktur', '=', 'p.Faktur')
            ->select(DB::raw('IFNULL(SUM(p.Qty), 0) as TerimaBrg'))
            ->where('t.PO', '=', $fakturPO)
            ->where('p.Kode', '=', $kodeProgram)
            ->first();
        if ($terimaBrg) {
            $nTerimaBarang = $terimaBrg->TerimaBrg;
        }
        return $nTerimaBarang;
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
                'FAKTURPEMBELIAN' => 'max:20',
                'FAKTURASLI' => 'max:20',
                'TGL' => 'date',
                'JTHTMP' => 'date',
                'GUDANG' => 'max:4',
                'SUPPLIER' => 'max:6',
                'PPN' => 'numeric|digits_between:1,6',
                'PERSDISC' => 'numeric|digits_between:1,6',
                'SUBTOTAL' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                'TUNAI' => 'numeric|min:0',
                'HUTANG' => 'numeric|min:0',
                'KODE' => 'max:20',
                'BARCODE' => 'max:20',
                'QTY' => 'numeric',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                'JUMLAH' => 'numeric|min:0'
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
            $fakturPembelian = $request->FAKTURPEMBELIAN;
            $tgl = $request->TGL;
            $fakturPO = '';
            $tot = TotRtnPembelian::where('FAKTUR', $faktur)->exists();
            if ($tot) {
                $delete = TotRtnPembelian::where('FAKTUR', $faktur)->delete();
            }
            $rtn = RtnPembelian::where('FAKTUR', $faktur)->exists();
            if ($rtn) {
                $delete = RtnPembelian::where('FAKTUR', $faktur)->delete();
            }
            $totpembelian = TotPembelian::where('FAKTUR', $fakturPembelian)->first();
            if ($totpembelian) {
                $fakturPO = $totpembelian->PO;
            }
            $totrtnpembelianfaktur = [
                'FAKTUR' => $faktur,
                'FAKTURPEMBELIAN' => $fakturPembelian,
                'FAKTURPO' => $fakturPO,
                'FAKTURASLI' => $request->FAKTURASLI,
                'TGL' => $tgl,
                'JTHTMP' => $request->JTHTMP,
                'GUDANG' => '',
                'SUPPLIER' => $request->SUPPLIER,
                'SUBTOTAL' => round($request->SUBTOTAL),
                'PAJAK' => round($request->PAJAK),
                'DISCOUNT' => round($request->DISCOUNT),
                'TOTAL' => round($request->TOTAL),
                'KETERANGAN' => $request->KETERANGAN,
                'DATETIME' => Carbon::now(),
                'USERNAME' => $cUser
            ];

            foreach ($request->input('tabelTransaksiRtnPembelianFaktur') as $item) {
                try {
                    $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
                    $kodeP = $item['KODE'];
                    $hargaP = $item['HARGA'];
                    $barcode = '';
                    $cariBarcode = Stock::where('KODE', $kodeP)->first();
                    if ($cariBarcode) {
                        $barcode = $cariBarcode->KODE_TOKO;
                    }
                    $rtnpembelianfaktur = [
                        'FAKTUR' => $faktur,
                        'TGL' => $tgl,
                        'KODE' => $kodeP,
                        'BARCODE' => $barcode,
                        'QTY' => $item['RETUR'],
                        'HARGA' => $hargaP,
                        'SATUAN' => $item['SATUAN'],
                        'DISCOUNT' => $item['DISCOUNT'],
                        'PPN' => $item['PPN'],
                        'JUMLAH' => $item['JUMLAH']
                    ];
                    RtnPembelian::create($rtnpembelianfaktur);
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }
            TotRtnPembelian::create($totrtnpembelianfaktur);
            Upd::updKartuStockReturPembelian($faktur);
            Upd::updRekeningReturPembelian($faktur);
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
            $editRetur = DB::table('totrtnpembelian AS trp')
                ->select(
                    'trp.FAKTURPEMBELIAN AS FAKTURTERIMA',
                    'trp.TGL AS TGLFAKTUR',
                    'trp.FAKTURASLI',
                    'tpo.TGL AS TGLPO',
                    'tpo.TGLDO',
                    'tpo.JTHTMP',
                    'trp.GUDANG',
                    'g.KETERANGAN AS NAMAGUDANG',
                    'trp.SUPPLIER',
                    's.NAMA AS NAMASUPPLIER',
                    's.ALAMAT AS ALAMATSUPPLIER',
                    's.KOTA AS KOTASUPPLIER',
                    'trp.KETERANGAN',
                    'trp.PERSDISC',
                    'tpo.PPN',
                    'tpo.FAKTUR AS FAKTURPO'
                )
                ->leftJoin('totpembelian AS tp', 'trp.FAKTURPEMBELIAN', '=', 'tp.FAKTUR')
                ->leftJoin('totpo AS tpo', 'tp.PO', '=', 'tpo.FAKTUR')
                ->leftJoin('supplier AS s', 'trp.SUPPLIER', '=', 's.KODE')
                ->leftJoin('gudang AS g', 'trp.GUDANG', '=', 'g.KODE')
                ->where('trp.FAKTUR', $FAKTUR)
                ->first();

            if ($editRetur) {
                $FAKTURPEMBELIAN = $editRetur->FAKTURTERIMA;
                $FAKTURPO = $editRetur->FAKTURPO;
                $result = [
                    "FAKTUR" => $FAKTUR,
                    "FAKTURTERIMA" => $FAKTURPEMBELIAN,
                    "FAKTURASLI" => $editRetur->FAKTURASLI,
                    "TGL" => $editRetur->TGLFAKTUR,
                    "TGLPO" => $editRetur->TGLPO,
                    "TGLDO" => $editRetur->TGLDO,
                    "JTHTMP" => $editRetur->JTHTMP,
                    "KETERANGAN" => $editRetur->KETERANGAN,
                    "GUDANG" => $editRetur->GUDANG,
                    "KETGUDANG" => $editRetur->NAMAGUDANG,
                    "SUPPLIER" => $editRetur->SUPPLIER,
                    "NAMA" => $editRetur->NAMASUPPLIER,
                    "ALAMAT" => $editRetur->ALAMATSUPPLIER,
                    "KOTASUPPLIER" => $editRetur->KOTASUPPLIER,
                    "PERSDISC" => $editRetur->PERSDISC,
                    "PERSPPN" => $editRetur->PPN
                ];
            }

            $detailEditRetur = Pembelian::with('stock')
                ->where('FAKTUR', $FAKTURPEMBELIAN)
                ->where('HARGA', '>', '0')
                ->get();
            foreach ($detailEditRetur as $detail) {
                $KODE = $detail->KODE;
                $po = Po::where('FAKTUR', $FAKTURPO)
                    ->where('KODE', $KODE)
                    ->get();
                $QTYPO = 0;
                foreach ($po as $p) {
                    $QTYPO = $p->QTY;
                }
                $retur = RtnPembelian::where('FAKTUR', $FAKTUR)
                    ->where('KODE', $KODE)
                    ->get();
                $QTYRETUR = 0;
                foreach ($retur as $r) {
                    $QTYRETUR = $r->QTY;
                }
                $result['detail'][] = [
                    "KODE" => $detail->KODE,
                    "BARCODE" => $detail->BARCODE,
                    "NAMA" => $detail->stock->NAMA,
                    "QTY" => round($QTYPO),
                    "TERIMABRG" => round($this->terimaBarang($FAKTURPO, $KODE)),
                    "RETUR" => round($QTYRETUR),
                    "SATUAN" => $detail->SATUAN,
                    "HARGA" => round($detail->HARGA),
                    "PPN" => round($detail->PPN),
                    "DISCOUNT" => round($detail->DISCOUNT),
                    "JUMLAH" => round($detail->JUMLAH)
                ];
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil mengambil data',
                'data' => $result,
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
        $cUser = Func::dataAuth($request);
        try {
            $messages = config('validate.validation');
            $vaValidator = validator::make($request->all(), [
                'FAKTUR' => 'required|max:20',
                'FAKTURPEMBELIAN' => 'max:20',
                'FAKTURASLI' => 'max:20',
                'TGL' => 'date',
                'JTHTMP' => 'date',
                'GUDANG' => 'max:4',
                'SUPPLIER' => 'max:6',
                'PPN' => 'numeric|digits_between:1,6',
                'PERSDISC' => 'numeric|digits_between:1,6',
                'SUBTOTAL' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                'TUNAI' => 'numeric|min:0',
                'HUTANG' => 'numeric|min:0',
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
            $totrtnpembelianfaktur = [
                'FAKTURPEMBELIAN' => $request->FAKTURPEMBELIAN,
                'FAKTURASLI' => $request->FAKTURASLI,
                'TGL' => $tgl,
                'JTHTMP' => $request->JTHTMP,
                'GUDANG' => '01', //GET CONFIG
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
                "USERNAME" => $cUser //GET CONFIG
            ];
            $existingRtnPembelianfaktur = RtnPembelian::where('FAKTUR', $faktur)->delete();
            foreach ($request->input('tabelTransaksiRtnPembelianFaktur') as $item) {
                try {
                    $allIterationsSuccessful = true; // Membuat variabel untuk melacak kesuksesan setiap iterasi
                    $barcode = $item['BARCODE'];
                    $hargaP = $item['HARGA'];
                    $cariKode = Stock::where('KODE_TOKO', $barcode)->first();
                    if ($cariKode) {
                        $kodeProgram = $cariKode->KODE;
                    }

                    $detail = [
                        'FAKTUR' => $faktur,
                        'TGL' => $tgl,
                        'KODE' => $kodeProgram,
                        'BARCODE' => $barcode,
                        'QTY' => round($item['RETUR']),
                        'HARGA' => round($hargaP),
                        'SATUAN' => $item['SATUAN'],
                        'DISCOUNT' => round($item['DISCOUNT']),
                        'PPN' => round($item['PPN']),
                        'JUMLAH' => round($item['JUMLAH'])
                    ];
                    RtnPembelian::create($detail);
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }
            TotRtnPembelian::where('FAKTUR', $faktur)->update($totrtnpembelianfaktur);
            Upd::updRekeningReturPembelian($faktur);
            Upd::updKartuStockReturPembelian($faktur);
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

    public function delete(Request $request)
    {
        try {
            $totrtnpembelianfaktur = TotRtnPembelian::findOrFail($request->FAKTUR);
            $totrtnpembelianfaktur->delete();
            $rtnpembelianfaktur = RtnPembelian::findOrFail($request->FAKTUR);
            $rtnpembelianfaktur->delete();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil menghapus data',
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

    public function print(Request $request)
    {
        $faktur = $request->FAKTUR;
        $totrtnpembelian = TotRtnPembelian::with('supplier')
            ->with('gudang')
            ->where('FAKTUR', $faktur)
            ->first();
        $rtnpembelian = RtnPembelian::with('stock')
            ->where('FAKTUR', $faktur)
            ->get();
        return response()->json([
            'totrtnpembelian' => $totrtnpembelian,
            'rtnpembelian' => $rtnpembelian,
        ]);
        // return view('pembelian.printReturPembelian', compact('totrtnpembelian', 'rtnpembelian'));
    }
}
