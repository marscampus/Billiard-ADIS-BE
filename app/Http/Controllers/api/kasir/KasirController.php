<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\Assist;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuStock;
use App\Models\fun\Shift;
use App\Models\fun\Username;
use App\Models\kasir\Kasir;
use App\Models\kasir\KasirTmp;
use App\Models\kasir\SesiJual;
use App\Models\kasir\TotKasir;
use App\Models\kasir\TotKasirTmp;
use App\Models\master\DiskonPeriode;
use App\Models\master\Member;
use App\Models\master\MutasiMember;
use App\Models\master\PerubahanHargaStock;
use App\Models\master\Stock;
use App\Models\master\StockKode;
use App\Models\penjualan\Penjualan;
use App\Models\penjualan\TotPenjualan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KasirController extends Controller
{
    public function getBarcode(Request $request)
    {
        $KODE_TOKO = $request->KODE_TOKO;
        // dd($KODE_TOKO);
        $jml = 1;
        try {
            // dd('masuk');
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

                    // Mengambil TANGGAL_PERUBAHAN dari modal PerubahanHargaStock
                    $tanggal_perubahan = PerubahanHargaStock::where('KODE', $KODE)
                        ->orderBy('DATETIME', 'desc')
                        ->value('TANGGAL_PERUBAHAN');

                    // Mengecek apakah ada diskon periode
                    $diskonPeriode = DiskonPeriode::where('BARCODE', 'LIKE', '%' . $KODE_TOKO . '%')
                        ->orderBy('TGL_AKHIR', 'desc')
                        ->first();

                    $diskonPeriodeStatus = 'TIDAK ADA';
                    $hargaJual = $query[0]->HJ; // Default harga jual

                    if ($diskonPeriode) {
                        $tanggalSekarang = Carbon::now()->format('Y-m-d');
                        if ($diskonPeriode->TGL_AKHIR >= $tanggalSekarang) {
                            $fakturPenjualan = Penjualan::where('BARCODE', 'LIKE', '%' . $KODE_TOKO . '%')
                                ->where('HARGA', $diskonPeriode->HJ_DISKON)
                                ->pluck('FAKTUR');

                            // Menyaring FAKTUR yang memiliki KODESESI tidak kosong pada TotPenjualan
                            $fakturDenganKodeSesi = $fakturPenjualan->filter(function ($faktur) {
                                $totPenjualan = TotPenjualan::where('FAKTUR', $faktur)->first();
                                return $totPenjualan && !empty($totPenjualan->KODESESI);
                            });
                            // Mengubah koleksi menjadi array untuk digunakan dalam whereIn
                            $fakturDenganKodeSesiArray = $fakturDenganKodeSesi->toArray();

                            // Menghitung jumlah item yang telah dijual dengan harga diskon berdasarkan FAKTUR yang difilter
                            $totalTerjual = Penjualan::whereIn('FAKTUR', $fakturDenganKodeSesiArray)
                                ->where('HARGA', $diskonPeriode->HJ_DISKON)
                                ->sum('QTY');
                            // dd($totalTerjual);

                            $totalTerjual = $totalTerjual ?? 0;
                            $sisaKuotaPeriode = abs($totalTerjual - $diskonPeriode->KUOTA_QTY);

                            // Kondisi mengitung jika Belum mencapai KUOTA_QTY maka pake HJ_DISKON
                            if ($totalTerjual < $diskonPeriode->KUOTA_QTY) {
                                $hargaJual = $diskonPeriode->HJ_DISKON;
                                $diskonPeriodeStatus = 'ADA';
                            }
                        }
                    }

                    $data[] = [
                        'KODE' => $KODE,
                        'BKP' => $query[0]->BKP,
                        'BARCODE' => $query[0]->KODE_TOKO,
                        'NAMA' => $query[0]->NAMA,
                        'SATUAN' => $query[0]->SATUAN,
                        'HARGABELI' => $query[0]->HB,
                        'HJ' => $hargaJual,
                        'TGLEXP' => $query[0]->EXPIRED,
                        'DISCOUNT' => empty($query[0]->DISCOUNT) ? '0' : $query[0]->DISCOUNT,
                        'PAJAK' => empty($query[0]->PAJAK) ? '0' : $query[0]->PAJAK,
                        'TERIMA' => $jml,
                        'TGLPERUBAHANHJ' => $tanggal_perubahan,
                        'DISKONPERIODE' => $diskonPeriodeStatus,
                        'KUOTADISKONTERJUAL' => $totalTerjual ?? 0,
                        'SISAKUOTADISKON' => $sisaKuotaPeriode ?? 0,
                    ];
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getNoNota(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        try {
            $response = GetterSetter::getLastFaktur($KODE, $LEN);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $response,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getShift(Request $request)
    {
        $userEmailKasir = $request->USEREMAIL_KASIR;
        $userEmailSupervisor = $request->USEREMAIL_SUPERVISOR;

        try {
            $fullNameKasir = DB::table('users')->where('email', $userEmailKasir)->first()->name ?? null;
            $fullNameSupervisor = DB::table('users')->where('email', $userEmailSupervisor)->first()->name ?? null;


            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'fullNameKasir' => $fullNameKasir,
                'fullNameSupervisor' => $fullNameSupervisor,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getMember(Request $request)
    {
        try {
            $query = DB::table('member AS c')
                ->leftJoin('mutasimember AS m', 'm.member', '=', 'c.kode')
                ->select(
                    'c.KODE',
                    'c.NAMA',
                    'c.ALAMAT',
                    DB::raw('FORMAT(IFNULL(SUM(m.debet - m.kredit), 0), 0) AS `SALDO VOUCHER`'),
                    DB::raw('FORMAT(IFNULL(SUM(m.pointdebet - m.pointkredit), 0), 0) AS `TOTAL POINT`'),
                    DB::raw('FORMAT(IFNULL(SUM(m.nominalpointdebet - m.nominalpointkredit), 0), 0) AS `SALDO POINT`')
                )
                ->where('c.kode', '<>', GetterSetter::getDBConfig("MemberDefault"))
                ->groupBy('c.kode', 'c.Nama', 'c.ALAMAT')
                ->orderBy('c.Nama');

            $result = $query->paginate(10);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $result,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function tampilFaktur()
    {
        try {

            $faktur = DB::table('totkasir AS t')
                ->leftJoin('member AS c', 'c.kode', '=', 't.member')
                ->select('t.Faktur', DB::raw('ifnull(c.Nama, "") AS MEMBER'), DB::raw('FORMAT(t.Total, 0) as Total'))
                ->whereNotExists(function ($query) {
                    $query->select('faktur')
                        ->from('totrtnpenjualan AS d')
                        ->whereColumn('d.FAKTUR', 't.FAKTUR');
                })
                // ->where('t.username', '=', Assist::getDBConfig('userLogin'))// GET CONFIG
                ->where('t.tgl', '=', Carbon::now()->toDateString())
                ->orderByDesc('t.id')
                ->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $faktur,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function getCaraPemesanan()
    {
        try {

            $r = DB::table('pemesanan')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $r,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function editFaktur(Request $request)
    {
        try {
            $faktur = $request->FAKTUR;
            $totkasir = TotKasir::where('FAKTUR', $faktur)->get();

            $data = [];
            foreach ($totkasir as $t) {
                $totalBayar = $t->TOTAL;
                $subTotalItem = $t->SUBTOTAL;
                $kodeMember = '';
                $namaMember = '';
                $discNominal = 0;
                $ppnNominal = 0;

                if ($t->MEMBER != GetterSetter::getDBConfig('MemberDefault')) {
                    $kodeMember = $t->MEMBER;
                    $member = Member::where('KODE', $kodeMember)->first();
                    if ($member) {
                        $namaMember = $member->NAMA;
                    }
                }

                $discNominal = $t->DISCOUNT;
                if ($t->ADMIN > 0) {
                    $ppnNominal = $t->ADMIN;
                }

                $data['TOTALBAYAR'] = $totalBayar;
                $data['SUBTOTALITEM'] = $subTotalItem;
                $data['KODEMEMBER'] = $kodeMember;
                $data['NAMAMEMBER'] = $namaMember;
                $data['DISCNOMINAL'] = $discNominal;
                $data['PPNNOMINAL'] = $ppnNominal;

                $kasir = Kasir::with('stock')
                    ->with('satuan')
                    ->where('FAKTUR', $faktur)
                    ->get();

                $kasirData = [];
                foreach ($kasir as $k) {
                    $barcode = $k->BARCODE;
                    $namaBarang = $k->stock->NAMA;
                    $sisa = GetterSetter::getSaldoStock($k->KODE, $k->GUDANG, $k->TGL);
                    $qty = $k->stock->QTY;
                    $satuan = $k->stock->SATUAN;
                    $harga = $k->HARGA;
                    $discProduk = $k->DISCOUNT;
                    $discNominalProduk = $harga * $discProduk / 100;
                    $nett = ($harga - $discNominalProduk) * $qty;

                    $kasirData[] = [
                        'BARCODE' => $barcode,
                        'NAMABARANG' => $namaBarang,
                        'SISA' => $sisa,
                        'QTY' => $qty,
                        'SATUAN' => $satuan,
                        'HARGA' => $harga,
                        'DISCPRODUK' => $discProduk,
                        'DISCNOMINALPRODUK' => $discNominalProduk,
                        'NETT' => $nett
                    ];
                }

                $data['kasir'] = $kasirData;
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function tampilDaftarTunda()
    {
        try {
            $totkasir_tmp = TotKasirTmp::with('member')
                // ->where('USERNAME', 'ARADHEA') // GET CONFIG
                // ->where('TGL', Carbon::now()->toDateString())
                ->orderBy('ID', 'DESC')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $totkasir_tmp,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function daftarTundaFaktur(Request $request)
    {
        try {
            $faktur = $request->FAKTUR;
            $totkasir_tmp = TotKasirTmp::where('FAKTUR', $faktur)->get();
            $data = [];

            foreach ($totkasir_tmp as $tmp) {
                $totalBayar = $tmp->TOTAL;
                $subTotalItem = $tmp->SUBTOTAL;
                $kodeMember = '';
                $namaMember = '';
                $discNominal = 0;
                $ppnNominal = 0;

                if ($tmp->MEMBER != GetterSetter::getDBConfig('MemberDefault')) {
                    $kodeMember = $tmp->MEMBER;
                    $member = Member::where('KODE', $kodeMember)->first();
                    if ($member) {
                        $namaMember = $member->NAMA;
                    }
                }

                $discNominal = $tmp->DISCOUNT;
                if ($tmp->ADMIN > 0) {
                    $ppnNominal = $tmp->ADMIN;
                }
                $data['FAKTUR'] = $tmp->FAKTUR;
                $data['TOTALBAYAR'] = $totalBayar;
                $data['SUBTOTALITEM'] = $subTotalItem;
                $data['KODEMEMBER'] = $kodeMember;
                $data['NAMAMEMBER'] = $namaMember;
                $data['DISCNOMINAL'] = $discNominal;
                $data['PPNNOMINAL'] = $ppnNominal;

                $kasir_tmp = KasirTmp::with('stock')
                    ->with('satuan')
                    ->where('FAKTUR', $faktur)
                    ->get();
                $kasirData = [];
                foreach ($kasir_tmp as $k) {
                    $barcode = $k->BARCODE;
                    $namaBarang = $k->stock->NAMA;
                    $sisa = GetterSetter::getSaldoStock($k->KODE, $k->GUDANG, $k->TGL);
                    $qty = $k->QTY;
                    $satuan = $k->SATUAN;
                    $harga = $k->HARGA;
                    $discProduk = $k->DISCOUNT;
                    $discNominalProduk = $harga * $discProduk / 100;
                    $nett = ($harga - $discNominalProduk) * $qty;

                    $kasirData[] = [
                        'BARCODE' => $barcode,
                        'NAMABARANG' => $namaBarang,
                        'SISA' => $sisa,
                        'QTY' => $qty,
                        'SATUAN' => $satuan,
                        'HARGA' => $harga,
                        'DISCPRODUK' => $discProduk,
                        'DISCNOMINALPRODUK' => $discNominalProduk,
                        'NETT' => $nett
                    ];
                }
                $data['kasir_tmp'] = $kasirData;
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function tundaTransaksi(Request $request)
    {
        $cUser = Func::dataAuth($request);
        // dd($cUser);
        try {
            $vaValidator = validator::make($request->all(), [
                'FAKTUR' => 'required|max:20',
                'TGL' => 'date',
                'MEMBER' => 'max:50',
                'GUDANG' => 'max:4',
                'SUBTOTAL' => 'numeric|min:0',
                'DISCOUNT' => 'numeric|min:0',
                'ADMIN' => 'numeric|min:0',
                'PEMBULATAN' => 'numeric|min:0',
                'INFAQ' => 'numeric|min:0',
                'TOTAL' => 'numeric|min:0',
                'TUNAI' => 'numeric|min:0',
                'VOUCHER' => 'numeric|min:0',
                'BAYARFAKTUR' => 'numeric|min:0',
                'ADMINISTRASI' => 'numeric|min:0',
                'KARTU' => 'max:4',
                'NOKARTU' => 'max:30',
                'NOTRACE' => 'max:30',
                'DATETIME' => 'date',
                'USERNAME' => 'max:20',
                'BAYAR' => 'numeric|min:0',
                'PENARIKANTUNAI' => 'numeric|min:0',
                'CABANG' => 'max:3',
                'KODE' => 'max:20',
                'BARCODE' => 'max:20',
                'QTY' => 'numeric|min:0',
                'HARGA' => 'numeric|min:0',
                'SATUAN' => 'max:4',
                'JUMLAH' => 'numeric|min:0',
                'KETERANGAN' => 'max:30',
                'HP' => 'numeric|min:0',
                'STATUS' => 'max:1'
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
            $totkasir_tmp = TotKasirTmp::where('FAKTUR', $faktur)->delete();
            $kasir_tmp = KasirTmp::where('FAKTUR', $faktur)->delete();
            $totkasir_tmp = TotKasirTmp::create([
                'FAKTUR' => $faktur,
                'TGL' => Carbon::now()->toDateString(),
                'MEMBER' => empty($request->MEMBER) ? GetterSetter::getDBConfig('MemberDefault') : $request->MEMBER,
                'GUDANG' => '001', // GET CONFIG
                'DISCOUNT' => $request->DISCOUNT,
                'ADMIN' => $request->ADMIN,
                'SUBTOTAL' => $request->SUBTOTAL,
                'TOTAL' => $request->TOTAL,
                'DATETIME' => Carbon::now(),
                'USERNAME' => $cUser, // GET CONFIG
                'CABANG' => '001' // GET CONFIG
            ]);
            $kode = null; // Define the $kode variable with a default value
            foreach ($request->input('tabelTransaksiKasirTmp') as $item) {
                $barcode = $item['KODE_TOKO'];
                $cariKode = Stock::where('KODE_TOKO', $barcode)->first();
                if ($cariKode) {
                    $kode = $cariKode->KODE;
                }
                $kasir_tmp = KasirTmp::create([
                    'FAKTUR' => $faktur,
                    'TGL' => $tgl,
                    'KODE' => $kode,
                    'BARCODE' => $barcode,
                    'QTY' => $item['QTY'],
                    'HARGA' => $item['HARGA'],
                    'GUDANG' => '001', // GET CONFIG
                    'SATUAN' => $item['SATUAN'],
                    'DISCOUNTBARANG' => $item['DISCOUNTBARANG'],
                    'KETERANGAN' => 'Kasir ' . Carbon::now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s'),
                    'JUMLAH' => $item['JUMLAH'],
                    'USERNAME' => $cUser, // GET CONFIG
                ]);
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function reprintStruk(Request $request)
    {
        try {
            $faktur = $request->FAKTUR;
            $totkasir = TotKasir::where('FAKTUR', $faktur)->get();
            $data = [];

            foreach ($totkasir as $t) {
                $totalBayar = $t->TOTAL;
                $subTotalItem = $t->SUBTOTAL;
                $kodeMember = '';
                $namaMember = '';
                $discNominal = 0;
                $ppnNominal = 0;

                if ($t->MEMBER != GetterSetter::getDBConfig('MemberDefault')) {
                    $kodeMember = $t->MEMBER;
                    $member = Member::where('KODE', $kodeMember)->first();
                    if ($member) {
                        $namaMember = $member->NAMA;
                    }
                }

                $discNominal = $t->DISCOUNT;
                if ($t->ADMIN > 0) {
                    $ppnNominal = $t->ADMIN;
                }
                $data['FAKTUR'] = $t->FAKTUR;
                $data['TOTALBAYAR'] = $totalBayar;
                $data['SUBTOTALITEM'] = $subTotalItem;
                $data['KODEMEMBER'] = $kodeMember;
                $data['NAMAMEMBER'] = $namaMember;
                $data['DISCNOMINAL'] = $discNominal;
                $data['PPNNOMINAL'] = $ppnNominal;

                $kasir = Kasir::with('stock')
                    ->with('satuan')
                    ->where('FAKTUR', $faktur)
                    ->get();
                $kasirData = [];
                foreach ($kasir as $k) {
                    $barcode = $k->BARCODE;
                    $namaBarang = $k->stock->NAMA;
                    $sisa = GetterSetter::getSaldoStock($k->KODE, $k->GUDANG, $k->TGL);
                    $qty = $k->QTY;
                    $satuan = $k->SATUAN;
                    $harga = $k->HARGA;
                    $discProduk = $k->DISCOUNT;
                    $discNominalProduk = $harga * $discProduk / 100;
                    $nett = ($harga - $discNominalProduk) * $qty;

                    $kasirData[] = [
                        'BARCODE' => $barcode,
                        'NAMABARANG' => $namaBarang,
                        'SISA' => $sisa,
                        'QTY' => $qty,
                        'SATUAN' => $satuan,
                        'HARGA' => $harga,
                        'DISCPRODUK' => $discProduk,
                        'DISCNOMINALPRODUK' => $discNominalProduk,
                        'NETT' => $nett
                    ];
                }
                $data['kasir'] = $kasirData;
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // Tangani error sesuai kebutuhan
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function printStruk(Request $request)
    {
        $faktur = $request->FAKTUR;

        $totkasir = TotKasir::with('member')
            ->where('FAKTUR', $faktur)
            ->first();

        $kasir = Kasir::with('stock')
            ->where('FAKTUR', $faktur)
            ->get();
        // foreach ($kasir as $item) {
        //     dd($item->QTY);
        // }
        return view('kasir.printStruk', compact('faktur', 'totkasir', 'kasir'));
    }

    // ------------------------------------------------------------------< Emm >
    public function store(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {

            $faktur = GetterSetter::getLastFaktur('PJ', 6);
            // ------------------------------------------------------------------------------------< TotPenjualan >
            $totalHpp = 0;
            $nTotalDisc = round($request->DISCOUNT + $request->DISCOUNT2);
            $nTotalPajak = round($request->PAJAK + $request->PAJAK2);
            $nTotal = ($request->TOTAL - $nTotalDisc) + $nTotalPajak;
            $dataTotPenjualan = new TotPenjualan([
                'FAKTUR' => $faktur,
                'KODESESI' => $request->KODESESI, //FE dari sesi_jual - FAKTUR
                'KODESESI_RETUR' => '',
                'TGL' => $request->TGL, //FE dari sesi_jual - TGL
                'GUDANG' => $request->GUDANG, //FE dari sesi_jual - TOKO
                'DISCOUNT' => round($request->DISCOUNT),
                'DISCOUNT2' => round($request->DISCOUNT2),
                'PAJAK' => round($request->PAJAK),
                'PAJAK2' => round($request->PAJAK2),
                'SUBTOTAL' => round($request->TOTAL),
                'TOTAL' => round($nTotal),
                'CARABAYAR' => $request->CARABAYAR,
                'TUNAI' => round($request->TUNAI), // TUNAI
                'BAYARKARTU' => round($request->BAYARKARTU), //DEBIT
                'BIAYAKARTU' => round($request->BIAYAKARTU),
                'AMBILKARTU' => round($request->AMBILKARTU), // Tarik Tunai
                'EPAYMENT' => round($request->EPAYMENT), // EPAYMENT
                'NAMAKARTU' => $request->NAMAKARTU,
                'NOMORKARTU' => $request->NOMORKARTU,
                'NAMAPEMILIK' => $request->NAMAPEMILIK,
                'TIPEEPAYMENT' => $request->TIPEEPAYMENT,
                'KEMBALIAN' => round($request->KEMBALIAN),
                'MEMBER' => $request->MEMBER,
                'DONASI' => $request->DONASI,
                'MEJA' => $request->MEJA,
                'PEMESANAN' => $request->PEMESANAN,
                'PELANGGANLUAR' => $request->PELANGGAN,
                'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
                'USERNAME' => $cUser
            ]);
            // dd($dataTotPenjualan->TOTAL);
            // ------------------------------------------------------------------------------------< Mutasi Member Point >
            // $nominalPoint = GetterSetter::getDBConfig('rek_nominalPoint_toko');
            // $kelipatanPoint = GetterSetter::getDBConfig('rek_kelipatanPoint_toko');
            // if ($nominalPoint < 1 || $kelipatanPoint < 1) {
            //     return response()->json([
            //         'status' => self::$status['GAGAL'],
            //         'message' => 'Point belum di setting di config',
            //         'datetime' => date('Y-m-d H:i:s'),
            //     ], 400);
            // }
            // if ($request->filled('MEMBER')) {
            //     if ($dataTotPenjualan->TOTAL >= $kelipatanPoint) {
            //         $pointDebet = floor($dataTotPenjualan->TOTAL / $kelipatanPoint);
            //         $nominalPointDebet = $pointDebet * $nominalPoint;

            //         if ($pointDebet > 0) {
            //             $dataMutasiPoint = new MutasiMember([
            //                 'FAKTUR' => $faktur,
            //                 'JUMLAH' => $dataTotPenjualan->TOTAL,
            //                 'MEMBER' => $request->MEMBER,
            //                 'POINTDEBET' => $pointDebet,
            //                 'NOMINALPOINTDEBET' => $nominalPointDebet,
            //                 'POINTKREDIT' => 0,
            //                 'NOMINALPOINTKREDIT' => 0,
            //                 'TGL' => $dataTotPenjualan->TGL,
            //                 'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
            //                 'USERNAME' => $cUser,
            //                 // 'USERNAME' => $request->USERNAME,
            //             ]);
            //             $dataMutasiPoint->save();
            //         }
            //     }

            //     if ($request->has('POINTKREDIT')) {
            //         $pointKredit = $request->POINTKREDIT;
            //         $nominalPointKredit = $pointKredit * $nominalPoint;

            //         if ($pointKredit > 0) {
            //             $dataMutasiPoint = new MutasiMember([
            //                 'FAKTUR' => $faktur,
            //                 'JUMLAH' => $dataTotPenjualan->TOTAL,
            //                 'MEMBER' => $request->MEMBER,
            //                 'POINTDEBET' => 0,
            //                 'NOMINALPOINTDEBET' => 0,
            //                 'POINTKREDIT' => $pointKredit,
            //                 'NOMINALPOINTKREDIT' => $nominalPointKredit,
            //                 'TGL' => $dataTotPenjualan->TGL,
            //                 'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
            //                 // 'USERNAME' => $request->USERNAME,
            //                 'USERNAME' => $cUser
            //             ]);
            //             $dataMutasiPoint->save();
            //         }
            //     }
            // }
            // ------------------------------------------------------------------------------------< Penjualan >
            $varGudang = $dataTotPenjualan->GUDANG;
            $varTgl = $dataTotPenjualan->TGL;
            foreach ($request->detail_penjualan as $detail) {
                $varKode = $detail['KODE'];
                // ------------------------------------------------------------------------------------< StockHP >
                $varLastHP = GetterSetter::getLastHP($varKode, $varTgl);
                $totalHpp += $varLastHP * $detail['QTY'];
                $dataPenjualan = new Penjualan([
                    'FAKTUR' => $faktur,
                    'KODE' => $detail['KODE'],
                    'BARCODE' => $detail['BARCODE'],
                    'NAMA' => $detail['NAMA'],
                    'QTY' => $detail['QTY'],
                    'TGL' => $dataTotPenjualan->TGL, // Ambil TGL dari dataTotPenjualan
                    'SATUAN' => $detail['SATUAN'],
                    'PPN' => $detail['PPN'],
                    'DISCOUNT' => $detail['DISCOUNT'],
                    'HARGADISC' => $detail['HARGADISC'],
                    'HARGA' => $detail['HARGA'],
                    'JUMLAH' => $detail['JUMLAH'],
                    // 'KETERANGAN' => $detail['KETERANGAN'],
                    'KETERANGAN' => 'Penjualan ' . $detail['NAMA'],
                    'HP' => $varLastHP * $detail['QTY']
                ]);
                $dataPenjualan->save();
            }
            $dataTotPenjualan->TOTALHPP = $totalHpp;
            $dataTotPenjualan->save();
            // ------------------------------------------------------------------------------------< Kartu Stock >
            foreach ($request->kartu_stock as $detail) {
                Upd::updKartuStockPenjualan($faktur);
            }
            GetterSetter::setLastKodeRegister('PJ');
            $vaAntrian = DB::table('totpenjualan')->count();
            $vaArray = [
                'Faktur' => $faktur,
                'Antrian' => $vaAntrian
            ];
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Menyimpan Data',
                'data' => $vaArray,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage() . $th->getLine(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
