<?php

namespace App\Http\Controllers\api\pembelian;

use App\Helpers\ApiResponse;
use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\BukuBesar;
use App\Models\fun\Jurnal;
use App\Models\fun\KartuHutang;
use App\Models\fun\KartuStock;
use App\Models\master\PerubahanHargaStock;
use App\Models\master\Stock;
use App\Models\master\Supplier;
use App\Models\pembelian\Pembelian;
use App\Models\pembelian\Po;
use App\Models\pembelian\TotPembelian;
use App\Models\pembelian\TotPo;
use App\Models\pembelian\TotRtnPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PembelianPenerimaanBarangController extends Controller
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
            $vaData = DB::table('totpembelian as tp')
                ->select(
                    'tp.FAKTUR',
                    'tp.PO',
                    'tp.KETERANGAN',
                    'tp.TGL',
                    'tp.JTHTMP',
                    's.NAMA',
                    'tp.TOTAL'
                )
                ->leftJoin('supplier as s', 's.KODE', '=', 'tp.SUPPLIER')
                ->whereBetween('tp.TGL', [$startDate, $endDate])->orderByDesc('tp.FAKTUR')
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

    public function getDataFakturPO(Request $request)
    {
        try {
            // Ambil data utama
            $vaData = DB::table('totpo as t')
                ->select('t.faktur', 't.fakturasli', 't.tgl', 't.tgldo', 't.jthtmp', 't.keterangan', 't.supplier', 's.nama', 's.alamat', 't.persdisc', 't.ppn', 't.total')
                ->selectRaw('IFNULL(SUM(p.qty), 0) AS terimabrg')
                ->selectRaw('(SELECT IFNULL(SUM(qty), 0) FROM po WHERE faktur = t.faktur) AS pobrg')
                ->leftJoin('supplier as s', 's.kode', '=', 't.supplier')
                ->leftJoin('totpembelian as tp', 'tp.po', '=', 't.faktur')
                ->leftJoin('pembelian as p', 'p.faktur', '=', 'tp.faktur')
                ->where('t.status', '!=', '1')
                ->groupBy('t.faktur')
                ->get();

            // Iterasi untuk mendapatkan data detail terkait
            $response = $vaData->map(function ($totpo) {
                $cFaktur = $totpo->faktur;

                // Ambil data detail berdasarkan faktur
                $vaData2 = DB::table('po as p')
                    ->select('p.kode', 'p.barcode', 's.nama', 'p.qty', 'p.satuan', 'p.discount', 'p.ppn', 'p.jumlah', 'p.harga', 'p.tglexp', 's.hj')
                    ->leftJoin('stock as s', 's.kode', '=', 'p.kode')
                    ->where('p.faktur', '=', $cFaktur)
                    ->get();

                // Iterasi untuk menambahkan informasi Terima Barang ke setiap item
                $details = $vaData2->map(function ($item) use ($cFaktur) {
                    $vaTerimaBarang = DB::table('pembelian as p')
                        ->select(DB::raw('IFNULL(SUM(p.qty), 0) as terimabrg'))
                        ->leftJoin('totpembelian as t', 't.faktur', '=', 'p.faktur')
                        ->where('t.po', '=', $cFaktur)
                        ->where('p.kode', '=', $item->kode)
                        ->first();
                    $nTerimaBarang = $vaTerimaBarang->terimabrg;
                    $nTerima = $item->Terima ?? 0;
                    return [
                        'kode' => $item->kode,
                        'barcode' => $item->barcode,
                        'nama' => $item->nama,
                        'qtypo' => intval($item->qty),
                        'terimabarang' => intval($nTerimaBarang),
                        'terima' => intval($nTerima),
                        'satuan' => $item->satuan,
                        'hb' => $item->harga,
                        'discount' => $item->discount,
                        'ppn' => $item->ppn,
                        'hj' => $item->hj,
                        'tglexp' => $item->tglexp,
                    ];
                });

                // Gabungkan data utama dengan data detail
                return [
                    'faktur' => $totpo->faktur,
                    'fakturasli' => $totpo->fakturasli,
                    'tgl' => $totpo->tgl,
                    'tgldelivery' => $totpo->tgldo,
                    'jthtmp' => $totpo->jthtmp,
                    'supplier' => $totpo->supplier,
                    'nama' => $totpo->nama,
                    'alamat' => $totpo->alamat,
                    'keterangan' => $totpo->keterangan,
                    'persdisc' => $totpo->persdisc,
                    'ppn' => $totpo->ppn,
                    'total' => $totpo->total,
                    'terimabrg' => $totpo->terimabrg,
                    'pobrg' => $totpo->pobrg,
                    'detail' => $details,
                ];
            });

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $response,
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

    public function getDataByFakturPO(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cUser = $vaRequestData['auth']['name'];
            unset($vaRequestData['page']);
            unset($vaRequestData['auth']);
            $cFaktur = $vaRequestData['Faktur'];
            $vaExists = DB::table('totrtnpembelian')
                ->where('Faktur', '=', $cFaktur)
                ->exists();
            if ($vaExists) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'DATA SEBAGIAN TELAH DIRETUR, TIDAK DAPAT DIPROSES',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
            $vaDataTotPO = DB::table('totpo as tp')
                ->select(
                    'tp.Faktur',
                    'tp.FakturAsli',
                    'tp.Tgl',
                    'tp.JthTmp',
                    'tp.TglDO',
                    'tp.PersDisc',
                    'tp.PPN',
                    'tp.Keterangan',
                    'tp.Supplier',
                    's.Nama',
                    's.Alamat'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                ->where('Faktur', '=', $cFaktur)
                ->first();

            $vaDataPO = DB::table('po as p')
                ->select(
                    'p.Kode',
                    'p.Harga',
                    's.Kode_Toko',
                    'p.Qty',
                    'p.Satuan',
                    'p.Discount',
                    'p.PPN',
                    'p.Jumlah',
                    's.HJ',
                    's.Nama',
                    'p.TglExp'
                )
                ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
                ->where('p.Faktur', '=', $cFaktur)
                ->orderByDesc('p.Faktur')
                ->get();

            if ($vaDataTotPO && count($vaDataPO) > 0) {
                $vaArray = [
                    "FAKTUR" => $cFaktur,
                    "FAKTURASLI" => $vaDataTotPO->FakturAsli,
                    "TGL" => $vaDataTotPO->Tgl,
                    "JTHTMP" => $vaDataTotPO->JthTmp,
                    "TGLDELIVERY" => $vaDataTotPO->TglDO,
                    "PERSDISC" => $vaDataTotPO->PersDisc,
                    "PPN" => $vaDataTotPO->PPN,
                    "KETERANGAN" => $vaDataTotPO->Keterangan,
                    "SUPPLIER" => $vaDataTotPO->Supplier,
                    "NAMA" => $vaDataTotPO->Nama,
                    "ALAMAT" => $vaDataTotPO->Alamat,
                ];

                foreach ($vaDataPO as $po) {
                    $nHarga = $po->Harga;
                    $cKode = $po->Kode;
                    $vaTerimaBarang = DB::table('pembelian as p')
                        ->select(
                            DB::raw('IFNULL(SUM(p.Qty), 0) as TerimaBrg')
                        )
                        ->leftJoin('totpembelian as t', 't.Faktur', '=', 'p.Faktur')
                        ->where('t.PO', '=', $cFaktur)
                        ->where('p.Kode', '=', $cKode)
                        ->first();
                    $nTerimaBarang = $vaTerimaBarang->TerimaBrg;
                    $nQty = $po->Qty;
                    $nTerima = $po->Terima ?? 0;
                    $vaArray['pembelian'][] = [
                        "KODE" => $cKode,
                        "BARCODE" => $po->Kode_Toko,
                        "NAMA" => $po->Nama,
                        "QTYPO" => $nQty,
                        "TERIMABARANG" => $nTerimaBarang,
                        "TERIMA" => $nTerima,
                        "SATUAN" => $po->Satuan,
                        "HARGABELI" => $nHarga,
                        "TOTAL" => $nHarga * $nTerima,
                        "DISCOUNT" => $po->Discount,
                        "PPN" => $po->PPN,
                        "JUMLAH" => $po->Jumlah,
                        "HJ" => $po->HJ,
                        "TGLEXP" => $po->TglExp
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
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
                    if ($query[0]->STATUS_HAPUS == '0') {
                        $data[] = [
                            'KODE' => $KODE,
                            'BARCODE' => $query[0]->KODE_TOKO,
                            'NAMA' => $query[0]->NAMA,
                            'SATUAN' => $query[0]->SATUAN,
                            'HARGABELI' => $query[0]->HB,
                            'HJ' => $query[0]->HJ,
                            'TGLEXP' => $query[0]->EXPIRED,
                            'DISCOUNT' => empty($query[0]->DISCOUNT) ? '0' : $query[0]->DISCOUNT,
                            'PPN' => empty($query[0]->PPN) ? '0' : $query[0]->PPN,
                            'TERIMA' => $jml
                        ];
                    } else {
                        return response()->json(['status' => 'BARANG TIDAK DITEMUKAN']);
                    }
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
            $vaValidator = Validator::make($request->all(), [
                'faktur' => 'required|string|unique:totpembelian,faktur|max:20',
                'po' => 'max:20',
                'fakturasli' => 'max:20',
                'tgl' => 'date',
                'jthtmp' => 'date',
                'supplier' => 'required|string|max:6',
                'keterangan' => 'max:100',
                'gudang' => 'required|max:4',
                'pembayaran' => 'required|max:1'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $cFaktur = GetterSetter::getLastFaktur('PB', 6);
            $tgl = $request->tgl;
            $cFakturPO = $request->po;
            $kode = "";
            $totpembelian = [
                'FAKTUR' => $cFaktur,
                'PO' => $cFakturPO,
                'FAKTURASLI' => $request->fakturasli,
                'TGL' => $tgl,
                'TGLDO' => $request->tgldelivery,
                'JTHTMP' => $request->jthtmp,
                'GUDANG' => $request->gudang,
                'SUPPLIER' => $request->supplier,
                'SUBTOTAL' => $request->subtotal,
                'PPN' => $request->ppn,
                'PERSDISC' => $request->persdisc,
                'PAJAK' => $request->pajak,
                'DISCOUNT' => $request->discount,
                'DISCOUNT2' => $request->discount2 ?? 0,
                'TOTAL' => $request->total,
                'DATETIME' => Carbon::now(),
                'USERNAME' => $cUser, //GET CONFIG,
                'KETERANGAN' => $request->keterangan,
                'PEMBAYARAN' => $request->pembayaran,
                'CABANGENTRY' => ''
            ];
            TotPembelian::create($totpembelian);

            foreach ($request->input('tabelTransaksiPembelian') as $item) {
                try {
                    $barcode = $item['barcode'];
                    $harga = $item['harga'];
                    $hj = $item['hj'];
                    $tglPerubahan = Carbon::now()->format('Y-m-d');
                    $stock = Stock::where('KODE_TOKO', $barcode)->first();
                    $hbLama = $stock->HB;
                    $hjLama = $stock->HJ;
                    if ($stock) {
                        $kode = $stock->KODE;
                        $stock->HB = $harga;
                        $stock->HJ = $hj;
                    }

                    $pembelian = [
                        'FAKTUR' => $cFaktur,
                        'TGL' => $tgl,
                        'KODE' => $kode,
                        'BARCODE' => $barcode,
                        'QTY' => $item['terima'], // save QTY didapat dari kolom TERIMA di tabel FE
                        'HARGA' => $harga,
                        'HJ' => $hj,
                        'SATUAN' => $item['satuan'],
                        'DISCOUNT' => $item['discount'],
                        'JUMLAH' => $item['jumlah'],
                        'PPN' => $item['ppn'],
                        'TGLEXP' => $item['tglexp']
                    ];
                    Pembelian::create($pembelian);

                    $perubahanHarga = [
                        'FAKTUR' => $cFaktur,
                        'KODE' => $kode,
                        'KETERANGAN' => "Perubahan harga oleh " . $cUser . " tanggal " . $tglPerubahan,
                        'TANGGAL_PERUBAHAN' => $tglPerubahan,
                        'HBLAMA' => $hbLama,
                        'HB' => $harga,
                        'HJLAMA' => $hjLama,
                        'HJ' => $hj,
                        'USERNAME' => $cUser,
                        'DATETIME' => Carbon::now()->format('Y-m-d H:i:s')
                    ];
                    PerubahanHargaStock::create($perubahanHarga);
                    Upd::UpdStockHP($kode, $tglPerubahan);
                } catch (\Throwable $th) {
                    $allIterationsSuccessful = false; // Jika terjadi kesalahan, ubah variabel menjadi false
                    break; // Hentikan iterasi
                }
            }

            $vaData3 = DB::table('totpo as t')
                ->selectRaw('IFNULL(SUM(p.qty), 0) AS terimabrg')
                ->selectRaw('(SELECT IFNULL(SUM(qty), 0) FROM po WHERE faktur = t.faktur) AS pobrg')
                ->leftJoin('totpembelian as tp', 'tp.po', '=', 't.faktur')
                ->leftJoin('pembelian as p', 'p.faktur', '=', 'tp.faktur')
                ->where('t.faktur', '=', $cFakturPO)
                ->groupBy('t.faktur')
                ->get();
            foreach ($vaData3 as $d3) {
                $nTerimaBarang = intval($d3->terimabrg);
                $nPoBarang = intval($d3->pobrg);
                if ($nTerimaBarang >= $nPoBarang) {
                    DB::table('totpo')->where('faktur', '=', $cFakturPO)->update(['status' => '1']);
                }
            }
            Upd::updKartuStockPembelian($cFaktur);
            Upd::updRekeningPembelian($cFaktur);
            GetterSetter::setLastKodeRegister('PB');

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

    public function getDataEdit(Request $request)
    {
        $cFaktur = $request->faktur;
        try {
            $vaResult = [];

            $vaData = DB::table('totpembelian as t')
                ->select(
                    't.po',
                    't.fakturasli',
                    't.tgl',
                    'tp.tgl as tglpo',
                    'tp.tgldo',
                    'tp.jthtmp',
                    't.pembayaran',
                    't.gudang',
                    'g.keterangan as ketgudang',
                    't.supplier',
                    's.nama',
                    's.alamat',
                    't.persdisc',
                    't.discount',
                    't.discount2',
                    't.ppn',
                    't.pajak',
                    't.subtotal',
                    't.total',
                    't.keterangan'
                )
                ->leftJoin('supplier as s', 's.kode', '=', 't.supplier')
                ->leftJoin('totpo as tp', 'tp.faktur', '=', 't.po')
                ->leftJoin('gudang as g', 'g.kode', '=', 't.gudang')
                ->where('t.faktur', '=', $cFaktur)
                ->first();

            if ($vaData) {
                $vaPo = DB::table('po')
                    ->select('kode', 'qty')
                    ->where('faktur', '=', $vaData->po)
                    ->get();
                $vaData2 = DB::table('pembelian as p')
                    ->select(
                        'p.barcode',
                        'p.kode',
                        'p.qty',
                        'p.satuan',
                        'p.harga',
                        'p.hj',
                        'p.discount',
                        'p.ppn',
                        'p.jumlah',
                        'p.tglexp'
                    )
                    ->where('p.faktur', '=', $cFaktur)
                    ->get();
                $tabelTransaksiPembelian = $vaData2->map(function ($item) use ($vaData, $vaPo, $cFaktur) {
                    $qtyPo = $vaPo->firstWhere('kode', $item->kode)->qty ?? 0;
                    $terimaBarang = $this->terimaBarang($vaData->po, $cFaktur, $item->kode);

                    return [
                        'barcode' => $item->barcode,
                        'kode' => $item->kode,
                        'qtypo' => intval($qtyPo),
                        'terimabarang' => intval($terimaBarang),
                        'terima' => intval($item->qty),
                        'hb' => $item->harga,
                        'hj' => $item->hj,
                        'satuan' => $item->satuan,
                        'discount' => intval($item->discount),
                        'ppn' => $item->ppn,
                        'jumlah' => $item->jumlah,
                        'tglexp' => $item->tglexp,
                    ];
                });

                $vaResult = [
                    'faktur' => $cFaktur,
                    'po' => $vaData->po,
                    'fakturasli' => $vaData->fakturasli,
                    'tgl' => $vaData->tgl,
                    'tglpo' => $vaData->tglpo,
                    'tgldo' => $vaData->tgldo,
                    'jthtmp' => $vaData->jthtmp,
                    'pembayaran' => $vaData->pembayaran,
                    'gudang' => $vaData->gudang,
                    'ketgudang' => $vaData->ketgudang,
                    'supplier' => $vaData->supplier,
                    'nama' => $vaData->nama,
                    'alamat' => $vaData->alamat,
                    'persdisc' => intval($vaData->persdisc),
                    'discount' => $vaData->discount,
                    'pembulatan' => $vaData->discount2,
                    'ppn' => intval($vaData->ppn),
                    'pajak' => $vaData->pajak,
                    'subtotal' => $vaData->subtotal,
                    'total' => $vaData->total,
                    'keterangan' => $vaData->keterangan,
                    'tabelTransaksiPembelian' => $tabelTransaksiPembelian,
                ];
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaResult,
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


    public function terimaBarang($fakturPO, $fakturPembelian, $kodeProgram)
    {
        $nTerimaBarang = 0;
        $terimaBrg =
            DB::table('pembelian AS p')
                ->leftJoin('totpembelian AS t', 't.FAKTUR', '=', 'p.FAKTUR')
                ->where('t.PO', $fakturPO)
                ->where('t.FAKTUR', '<>', $fakturPembelian)
                ->where('p.KODE', $kodeProgram)
                ->select(DB::raw('IFNULL(SUM(p.QTY), 0) AS TerimaBrg'))
                ->first();
        if ($terimaBrg) {
            $nTerimaBarang = $terimaBrg->TerimaBrg;
        }
        return $nTerimaBarang;
    }

    public function update(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $messages = config('validate.validation');
            $vaValidator = Validator::make($request->all(), [
                'faktur' => 'required|string|max:20',
                'po' => 'required|string|max:20',
                'fakturasli' => 'max:20',
                'tgl' => 'date',
                'tglpo' => 'date',
                'tgldo' => 'date',
                'jthtmp' => 'date',
                'supplier' => 'required|string|max:6',
                'keterangan' => 'max:100',
                'gudang' => 'required|max:4',
                'pembayaran' => 'required|max:1'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $cFaktur = $request->faktur;
            $tgl = $request->tgl;
            $totpembelian = [
                'FAKTUR' => $cFaktur,
                'PO' => $request->po,
                'FAKTURASLI' => $request->fakturasli,
                'TGL' => $tgl,
                'JTHTMP' => $request->jthtmp,
                'SUPPLIER' => $request->supplier,
                'SUBTOTAL' => $request->subtotal,
                'PPN' => $request->ppn,
                'PERSDISC' => $request->persdisc,
                'PAJAK' => $request->pajak,
                'DISCOUNT' => $request->discount,
                'DISCOUNT2' => $request->discount2 ?? 0,
                'TOTAL' => $request->total,
                'DATETIME' => Carbon::now(),
                'USERNAME' => $cUser, //GET CONFIG,
                'KETERANGAN' => $request->keterangan,
                'PEMBAYARAN' => $request->pembayaran,
                'CABANGENTRY' => ''
            ];
            Pembelian::where('faktur', '=', $cFaktur)->delete();
            foreach ($request->input('tabelTransaksiPembelian') as $item) {
                try {
                    $barcode = $item['barcode'];
                    $harga = $item['harga'];
                    $hj = $item['hj'];
                    $tglPerubahan = Carbon::now()->format('Y-m-d');
                    $stock = Stock::where('KODE_TOKO', $barcode)->first();
                    $hbLama = $stock->HB;
                    $hjLama = $stock->HJ;
                    if ($stock) {
                        $kode = $stock->KODE;
                        $stock->HB = $harga;
                        $stock->HJ = $hj;
                    }

                    $pembelian = [
                        'FAKTUR' => $cFaktur,
                        'TGL' => $tgl,
                        'KODE' => $kode,
                        'BARCODE' => $barcode,
                        'QTY' => $item['terima'], // save QTY didapat dari kolom TERIMA di tabel FE
                        'HARGA' => $harga,
                        'HJ' => $hj,
                        'SATUAN' => $item['satuan'],
                        'DISCOUNT' => $item['discount'],
                        'JUMLAH' => $item['jumlah'],
                        'PPN' => $item['ppn'],
                        'TGLEXP' => $item['tglexp']
                    ];
                    Pembelian::create($pembelian);

                    $perubahanHarga = [
                        'FAKTUR' => $cFaktur,
                        'KODE' => $kode,
                        'KETERANGAN' => "Perubahan harga oleh " . $cUser . " tanggal " . $tglPerubahan,
                        'TANGGAL_PERUBAHAN' => $tglPerubahan,
                        'HBLAMA' => $hbLama,
                        'HB' => $harga,
                        'HJLAMA' => $hjLama,
                        'HJ' => $hj,
                        'USERNAME' => $cUser,
                        'DATETIME' => Carbon::now()->format('Y-m-d H:i:s')
                    ];
                    PerubahanHargaStock::create($perubahanHarga);
                    Upd::UpdStockHP($kode, $tglPerubahan);
                } catch (\Throwable $th) {
                    break; // Hentikan iterasi
                }
            }
            TotPembelian::where('FAKTUR', $cFaktur)->update($totpembelian);
            Upd::updKartuStockPembelian($cFaktur);
            Upd::updRekeningPembelian($cFaktur);
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
            $faktur = $request->FAKTUR;
            $existsRetur = TotRtnPembelian::where('FAKTURPEMBELIAN', $faktur)
                ->exists();
            if ($existsRetur) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Menghapus data Data',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
            $vaData = DB::table('totpembelian')
                ->select('po')
                ->where('faktur', '=', $faktur)
                ->first();
            DB::table('totpo')->where('faktur', '=', $vaData->po)->update(['status' => '0']);
            TotPembelian::where('FAKTUR', $faktur)->delete();
            Pembelian::where('FAKTUR', $faktur)->delete();
            KartuHutang::where('FAKTUR', $faktur)->delete();
            KartuStock::where('FAKTUR', $faktur)->delete();
            BukuBesar::where('FAKTUR', $faktur)->delete();
            Jurnal::where('FAKTUR', $faktur)->delete();
            // return response()->json(['status' => 'success']);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menghapus data Data',
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
        $totpembelian = TotPembelian::with('supplier')
            ->with('gudang')
            ->where('FAKTUR', $faktur)
            ->first();
        $pembelian = Pembelian::with('stock')
            ->where('FAKTUR', $faktur)
            ->get();
        return view('pembelian.printPenerimaanBarang', compact('totpembelian', 'pembelian'));
    }
}
