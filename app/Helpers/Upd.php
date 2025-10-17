<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Helpers\Func;
use App\Models\fun\Jurnal;
use App\Models\fun\StockHP;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use App\Models\fun\BukuBesar;
use App\Models\fun\KartuStock;
use App\Models\fun\KartuHutang;
use Illuminate\Support\Facades\DB;

class Upd
{
    const kr_reservasi = "RV";
    const kr_invoice = "IV";
    const kr_jurnal = "JR";

    const KR_SALDOAWAL = "SA";
    const KR_PEMBELIAN = "PB";
    const KR_PENJUALAN = "PJ";
    const KR_RETUR_PEMBELIAN = "RB";
    const KR_RETUR_PENJUALAN = "RJ";
    const KR_PENJUALAN_KASIR = "CS";
    const KR_PENYESUAIAN = "AD";
    const KR_PACKING = "PK";
    const KR_MUTASISTOKDARI = "BA";
    const KR_MUTASISTOKKE = "BK";
    const KR_PELUNASAN_HUTANG = "PP";
    const KR_PELUNASAN_PIUTANG = "PA";
    const KR_STOCKOPNAME = "SO";
    const KR_KARTUHUTANG = "KH";
    const KR_KARTUPIUTANG = "KP";
    const KR_BAHANBAKUKELUAR = "PM";
    const KR_BAHANBAKUMASUK = "PN";
    const KR_JURNAL_LAIN = "JL";

    public static function updKartuStockPenjualan($cFaktur)
    {
        try {
            $instance = new self();
            KartuStock::where('FAKTUR', $cFaktur)->delete();
            $vaData = DB::table('penjualan as p')
                ->select(
                    'tp.Tgl',
                    'tp.Gudang',
                    'p.Kode',
                    'p.Satuan',
                    'p.Qty',
                    'p.Harga',
                    'p.Discount',
                    'tp.PersDisc',
                    'tp.PersDisc2',
                    'tp.PPN',
                    'm.Nama',
                    's.Nama as NamaBarang'
                )
                ->leftJoin('totpenjualan as tp', 'tp.Faktur', '=', 'p.Faktur')
                ->leftJoin('member as m', 'm.Kode', '=', 'tp.Member')
                ->leftJoin('stock as s', 's.Kode', '=', 'p.Kode')
                ->where('p.Faktur', '=', $cFaktur)
                ->get();
            foreach ($vaData as $d) {
                $instance->updKartuStock(
                    self::KR_PENJUALAN,
                    $cFaktur,
                    $d->Tgl,
                    $d->Gudang,
                    $d->Kode,
                    $d->Satuan,
                    $d->Qty,
                    "K",
                    "Penjualan " . $d->NamaBarang,
                    $d->Harga,
                    $d->Discount,
                    $d->PersDisc,
                    $d->PersDisc2,
                    $d->PPN
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public static function updKartuStockReturPenjualan($cFaktur)
    {
        try {
            $vaData = DB::table('penjualan as p')
                ->select(
                    'tp.Tgl',
                    'tp.Gudang',
                    'p.Kode',
                    'p.Qty',
                    'p.Harga',
                    'p.Satuan'
                )
                ->leftJoin('totpenjualan as tp', 'tp.Faktur', '=', 'p.Faktur')
                ->where('p.Faktur', '=', $cFaktur)
                ->get();
            foreach ($vaData as $d) {
                self::updKartuStock(
                    self::KR_RETUR_PENJUALAN,
                    $cFaktur,
                    $d->Tgl,
                    $d->Gudang,
                    $d->Kode,
                    $d->Satuan,
                    $d->Qty,
                    'D',
                    'Retur Penjualan dengan Faktur ' . $cFaktur,
                    $d->Harga,
                    0,
                    0,
                    0,
                    0
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updPelunasanHutang($cFaktur, $cFakturPembelian, $nTotal)
    {
        try {
            $vaData = DB::table('totpembelian as tp')
                ->select(
                    'tp.JthTmp',
                    'tp.Supplier',
                    'tp.Gudang',
                    's.Nama'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                ->where('Faktur', '=', $cFakturPembelian)
                ->first();
            // KARTU HUTANG
            self::updKartuHutang(
                self::KR_PELUNASAN_HUTANG,
                $cFakturPembelian,
                $cFaktur,
                $vaData->JthTmp,
                'S',
                $vaData->Supplier,
                $vaData->Gudang,
                'Pembayaran Faktur ' . $cFakturPembelian,
                0,
                $nTotal
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updRekeningPelunasanHutang($cFaktur, $cFakturPembelian, $cRekening)
    {
        try {
            BukuBesar::where('Faktur', '=', $cFaktur)->delete();
            Jurnal::where('Faktur', $cFaktur)->delete();
            $vaData = DB::table('totpembelian as tp')
                ->select(
                    'tp.JthTmp',
                    'tp.Supplier',
                    'tp.Gudang',
                    's.Nama'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                ->where('Faktur', '=', $cFakturPembelian)
                ->first();
            $vaData2 = DB::table('kartuhutang')
                ->select(DB::raw('IFNULL(SUM(Kredit),0) as Total'), 'Tgl')
                ->where('FKT', '=', $cFaktur)
                ->get();
            foreach ($vaData2 as $d) {
                $nGrandTotal = $d->Total;
                $dTgl = $d->Tgl;
            }
            // BUKU BESAR
            self::updBukuBesar(
                $cFaktur,
                $vaData->Gudang,
                $dTgl,
                GetterSetter::getDBConfig('rek_hutangDagang_toko_toko'),
                'Pembelian an . ' . $vaData->Nama . " " . $cFaktur,
                $nGrandTotal,
                0,
                self::KR_PELUNASAN_HUTANG,
                'N'
            );
            self::updBukuBesar(
                $cFaktur,
                $vaData->Gudang,
                $dTgl,
                $cRekening,
                'Pelunasan Hutang an . ' . $vaData->Nama . " " . $cFaktur,
                0,
                $nGrandTotal,
                self::KR_PELUNASAN_HUTANG,
                'N'
            );
            // JURNAL
            self::updJurnal(
                $cFaktur,
                $dTgl,
                GetterSetter::getDBConfig('rek_hutangDagang_toko_toko'),
                'Pembelian an . ' . $vaData->Nama . " " . $cFaktur,
                $nGrandTotal,
                0
            );
            self::updJurnal(
                $cFaktur,
                $dTgl,
                $cRekening,
                'Pelunasan Hutang an . ' . $vaData->Nama . " " . $cFaktur,
                0,
                $nGrandTotal
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    // DP
    public static function updRekeningReservasi($cKodeReservasi)
    {
        try {
            // Hapus data lama di buku besar dan jurnal untuk kode reservasi tersebut
            DB::table('bukubesar')->where('Faktur', '=', $cKodeReservasi)->delete();
            DB::table('jurnal')->where('Faktur', '=', $cKodeReservasi)->delete();

            $vaData = DB::table('reservasi')
                ->select('tgl', 'dp', 'cara_bayar')
                ->where('kode_reservasi', '=', $cKodeReservasi)
                ->first();

            $invoice = DB::table('invoice')
                ->select('kode_reservasi')
                ->where('kode_reservasi', $cKodeReservasi)
                ->where('status_bayar', '1')
                ->get();

            $lunas = [];
            foreach ($invoice as $v) {
                $lunas[] = $v->kode_reservasi;
            }

            // Hapus data yang sudah lunas dari jurnal dan buku besar
            DB::table('jurnal')->whereIn('Faktur', $lunas)->delete();
            DB::table('bukubesar')->whereIn('Faktur', $lunas)->delete();

            // Jika data reservasi ditemukan dan invoice belum lunas, lakukan insert
            if ($vaData && !in_array($cKodeReservasi, $lunas)) {
                $cRekBank = GetterSetter::getRekeningCaraBayar($vaData->cara_bayar);
                $cRekTitipan = GetterSetter::getDBConfig('rek_booking');
                $cKas = ($vaData->cara_bayar == '01') ? 'T' : 'N';

                // Insert ke buku besar
                self::updBukuBesar(
                    $cKodeReservasi,
                    '',
                    $vaData->tgl,
                    $cRekBank,
                    'Pembayaran Uang Muka',
                    $vaData->dp,
                    0,
                    self::kr_reservasi,
                    $cKas
                );
                self::updBukuBesar(
                    $cKodeReservasi,
                    '',
                    $vaData->tgl,
                    $cRekTitipan,
                    'Pembayaran Uang Muka',
                    0,
                    $vaData->dp,
                    self::kr_reservasi,
                    $cKas
                );

                // Insert ke jurnal
                self::updJurnal(
                    $cKodeReservasi,
                    $vaData->tgl,
                    $cRekBank,
                    'Pembayaran Uang Muka',
                    $vaData->dp,
                    0
                );
                self::updJurnal(
                    $cKodeReservasi,
                    $vaData->tgl,
                    $cRekTitipan,
                    'Pembayaran Uang Muka',
                    0,
                    $vaData->dp
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    // Invoice
    public static function updRekeningInvoice($cKodeInvoice)
    {
        try {
            DB::table(table: 'bukubesar')->where('Faktur', '=', $cKodeInvoice)->delete();
            DB::table('jurnal')->where('Faktur', '=', $cKodeInvoice)->delete();
            $vaData = DB::table('invoice')
                ->select(
                    'tgl',
                    'total_harga', // ini harga setelah dikurangi dp dan diskon dan ditambah ppn
                    'total_kamar', // harga real yang seharusnya
                    'cara_bayar',
                    'dp',
                    'disc',
                    'ppn',
                    'status_bayar'
                )
                ->where('kode_invoice', '=', $cKodeInvoice)
                ->first();
            if ($vaData) {
                $cRekBank = GetterSetter::getRekeningCaraBayar($vaData->cara_bayar);
                $cRekTitipan = GetterSetter::getDBConfig('rek_booking');
                $cRekSewa = GetterSetter::getDBConfig('rek_sewa');
                $cRekDiskon = GetterSetter::getDBConfig('rek_diskon');
                $cRekPpn = GetterSetter::getDBConfig('rek_ppn');
                $cKas = ($vaData->cara_bayar == '01') ? 'T' : 'N';
                $nYangHarusDibayar = '';
                if ($vaData->status_bayar == 1) {

                    $nYangHarusDibayar = $vaData->total_kamar; // seharusnya ini nanti kalau sudah lunas maka dia tidak dikurangi dp dan semuanya dianggap pedapatan
                } else {
                    $nYangHarusDibayar = $vaData->total_kamar - $vaData->dp; // seharusnya ini nanti kalau sudah lunas maka dia tidak dikurangi dp dan semuanya dianggap pedapatan

                }
                $nNominalDisc = $vaData->disc / 100 * $nYangHarusDibayar;
                $nHargaSetelahDiskon = $nYangHarusDibayar - $nNominalDisc;
                $nNominalPPN = $vaData->ppn / 100 * $nHargaSetelahDiskon;
                $nHargaSetelahPPn = $nHargaSetelahDiskon + $nNominalPPN;
                // ================================================BUKU BESAR
                self::updBukuBesar(
                    $cKodeInvoice,
                    '',
                    $vaData->tgl,
                    $cRekBank,
                    'Pelunasan Invoice',
                    $nHargaSetelahDiskon + $nNominalPPN,
                    0,
                    self::kr_invoice,
                    $cKas
                );
                if ($vaData->disc > 0) {
                    self::updBukuBesar(
                        $cKodeInvoice,
                        '',
                        $vaData->tgl,
                        $cRekDiskon,
                        'Diskon Pelunasan Invoice',
                        $nNominalDisc,
                        0,
                        self::kr_invoice,
                        $cKas
                    );
                }
                if ($vaData->ppn > 0) {
                    self::updBukuBesar(
                        $cKodeInvoice,
                        '',
                        $vaData->tgl,
                        $cRekPpn,
                        'PPn Pelunasan Invoice',
                        0,
                        $nNominalPPN,
                        self::kr_invoice,
                        $cKas
                    );
                }
                self::updBukuBesar(
                    $cKodeInvoice,
                    '',
                    $vaData->tgl,
                    $cRekSewa,
                    'Pelunasan Invoice',
                    0,
                    $nYangHarusDibayar,
                    self::kr_invoice,
                    $cKas
                );
                // ================================================JURNAL
                self::updJurnal(
                    $cKodeInvoice,
                    $vaData->tgl,
                    $cRekBank,
                    'Pelunasan Invoice',
                    $nHargaSetelahDiskon + $nNominalPPN,
                    0
                );
                if ($vaData->disc > 0) {
                    self::updJurnal(
                        $cKodeInvoice,
                        $vaData->tgl,
                        $cRekDiskon,
                        'Diskon Pelunasan Invoice',
                        $nNominalDisc,
                        0
                    );
                }
                if ($vaData->ppn > 0) {
                    self::updJurnal(
                        $cKodeInvoice,
                        $vaData->tgl,
                        $cRekDiskon,
                        'PPn Pelunasan Invoice',
                        0,
                        $nNominalPPN
                    );
                }
                self::updJurnal(
                    $cKodeInvoice,
                    $vaData->tgl,
                    $cRekSewa,
                    'Pelunasan Invoice',
                    0,
                    $nYangHarusDibayar
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updBukuBesar(
        $FAKTUR,
        $CABANG,
        $TGL,
        $REKENING,
        $KETERANGAN,
        $DEBET,
        $KREDIT,
        $STATUS,
        $KAS
    ) {
        $request = request();
        $cUser = Func::dataAuth($request);
        try {
            if (empty($KAS)) {
                $KAS = "N";
            }
            if ($DEBET > 0 || $KREDIT > 0) {
                $vaInsert = [
                    'Status' => $STATUS,
                    'Faktur' => $FAKTUR,
                    'Cabang' => $CABANG,
                    'Tgl' => $TGL,
                    'Rekening' => $REKENING,
                    'Keterangan' => $KETERANGAN,
                    'Debet' => $DEBET,
                    'Kredit' => $KREDIT,
                    'Kas' => $KAS,
                    'DateTime' => Carbon::now()->format('Y-m-d H:i:s'),
                    'UserName' => $cUser,
                ];
                DB::table('bukubesar')->insert($vaInsert);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updJurnal(
        $cFaktur,
        $dTgl,
        $cRekening,
        $cKeterangan,
        $nDebet,
        $nKredit
    ) {
        try {
            $request = request();
            $cUser = Func::dataAuth($request);
            if ($nDebet > 0 || $nKredit > 0) {
                $data = [
                    'Faktur' => $cFaktur,
                    'Tgl' => $dTgl,
                    'Rekening' => $cRekening,
                    'CabangEntry' => '',
                    'Keterangan' => $cKeterangan,
                    'Debet' => $nDebet,
                    'Kredit' => $nKredit,
                    'DateTime' => Carbon::now()->format('Y-m-d H:i:s'),
                    'UserName' => $cUser
                ];
                DB::table('jurnal')->insert($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updKartuStockProduk($cKode, $nStockAwal)
    {
        try {
            KartuStock::where('Kode', '=', $cKode)->delete();
            $cFaktur = GetterSetter::getLastFaktur('SA', 6);
            $vaData = DB::table('stock')
                ->select(
                    'Gudang',
                    'HB',
                    'Tgl_Masuk',
                    'Satuan',
                    'Nama',
                    'Discount',
                    'Pajak'
                )
                ->where('Kode', '=', $cKode)
                ->first();
            if ($vaData) {
                $cGudang = $vaData->Gudang;
                $dTgl = $vaData->Tgl_Masuk;
                $nHB = $vaData->HB;
                $cSatuan = $vaData->Satuan;
                $cNama = $vaData->Nama;
                $nDiscount = $vaData->Discount;
                $nPajak = $vaData->Pajak;
                $nNominal = $nStockAwal * $nHB;
                // UPD KARTU STOCK
                self::updKartuStock(
                    self::KR_SALDOAWAL,
                    $cFaktur,
                    $dTgl,
                    $cGudang,
                    $cKode,
                    $cSatuan,
                    $nStockAwal,
                    'D',
                    'Stock Awal ' . strtoupper(trim($cNama)),
                    $nHB,
                    $nDiscount,
                    0,
                    0,
                    $nPajak
                );
                // UPD BUKU BESAR
                self::updBukuBesar(
                    $cFaktur,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_hpp_toko'),
                    'Stock Awal ' . strtoupper(trim($cNama)),
                    $nNominal,
                    0,
                    Upd::KR_SALDOAWAL,
                    'N'
                );
                self::updBukuBesar(
                    $cFaktur,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                    'Stock Awal ' . strtoupper(trim($cNama)),
                    0,
                    $nNominal,
                    Upd::KR_SALDOAWAL,
                    'N'
                );
                // UPD JURNAL
                self::updJurnal(
                    $cFaktur,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_hpp_toko'),
                    'HPP Bulan Ini ' . Func::EOM($dTgl),
                    $nNominal,
                    0
                );
                self::updJurnal(
                    $cFaktur,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_asetNilaiPersediaan_toko'),
                    'HPP Bulan Ini ' . Func::EOM($dTgl),
                    0,
                    $nNominal
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        GetterSetter::setLastKodeRegister('SA');
    }
    public static function updKartuStock(
        $STATUS,
        $FAKTUR,
        $TGL,
        $GUDANG,
        $KODE,
        $SATUAN,
        $QTY,
        $DK,
        $KETERANGAN,
        $HARGA,
        $DISCITEM,
        $DISCFAKTUR1,
        $DISCFAKTUR2,
        $PPN
    ) {
        $request = request();
        $cUser = Func::dataAuth($request);
        $QTY = floatval($QTY);
        try {
            $nIsi = 1;
            $vaSatuan = GetterSetter::GetSatuanStock($KODE, $SATUAN);
            if (isset($vaSatuan['Satuan'])) {
                if ((int) $vaSatuan['Satuan'] == 2) {
                    $nIsi = (int) $vaSatuan['Isi'];
                } else if ((int) $vaSatuan['Satuan'] == 3) {
                    $nIsi = (int) $vaSatuan['Isi'] * (int) $vaSatuan['Isi2'];
                }
            }
            $nDebet = $QTY * $nIsi;
            $nHP = round(Func::Devide($HARGA, $nIsi), 2);
            $nHP *= (1 - (intval($DISCITEM) / 100));
            $nHP *= (1 - (intval($DISCFAKTUR1) / 100));
            $nHP *= (1 - (intval($DISCFAKTUR2) / 100));
            $nHP = max($nHP, 0);
            $nKredit = 0;
            if ($DK == "K") {
                $nDebet = 0;
                $nKredit = $QTY * $nIsi;
            }
            if ($nDebet != 0 || $nKredit != 0) {
                $vaFaktur = GetterSetter::GetUrutFaktur($request);
                $va = [
                    'STATUS' => $STATUS,
                    'FAKTUR' => $FAKTUR,
                    'URUT' => $vaFaktur['ID'],
                    'TGL' => Func::Date2String($TGL),
                    'GUDANG' => $GUDANG,
                    'KODE' => $KODE,
                    'SATUAN' => $SATUAN,
                    'QTY' => Func::String2Number($QTY),
                    'DEBET' => Func::String2Number($nDebet),
                    'KREDIT' => Func::String2Number($nKredit),
                    'KETERANGAN' => $KETERANGAN,
                    'HARGA' => $HARGA,
                    'DISCITEM' => Func::String2Number($DISCITEM),
                    'DISCFAKTUR1' => Func::String2Number($DISCFAKTUR1),
                    'DISCFAKTUR2' => Func::String2Number($DISCFAKTUR2),
                    'PPN' => Func::String2Number($PPN),
                    'HP' => $nHP,
                    'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
                    'USERNAME' => $cUser // GET CONFIG
                ];
                KartuStock::create($va);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function UpdStockHP($kode, $tglPerubahan)
    {
        try {
            // dd($kode);
            $nHargaBeliAwal = 0;
            $nHargaBeliAkhir = 0;
            $nTotPersediaanAwal = 0;
            $nTotPersediaanAkhir = 0;
            $nTotStockAwal = 0;
            $nTotStockAkhir = 0;
            $nHargaJualAwal = 0;
            $nHargaJualAkhir = 0;

            $perubahanHarga = DB::table('perubahanhargastock')
                ->where('KODE', $kode)
                ->orderBy('ID', 'desc')
                ->first();
            if ($perubahanHarga) {
                $nHargaBeliAwal = $perubahanHarga->HBLAMA;
                $nHargaBeliAkhir = $perubahanHarga->HB;
                $nHargaJualAwal = $perubahanHarga->HJLAMA;
                $nHargaJualAkhir = $perubahanHarga->HJ;
            }

            $pembelianAwal = DB::table('pembelian')
                ->where('KODE', $kode)
                ->where('HARGA', $nHargaBeliAwal)
                ->select(DB::raw('IFNULL(SUM(QTY), 0) as QTY'))
                ->first();
            // dd($nHargaBeliAwal);
            if ($pembelianAwal) {
                $nJmlStock = $pembelianAwal->QTY * $nHargaBeliAwal;
                $nTotStockAwal = $pembelianAwal->QTY;
                $nTotPersediaanAwal = $nJmlStock;
            }
            // dd($nJmlStock);
            $pembelianAkhir = DB::table('pembelian')
                ->where('KODE', $kode)
                ->where('HARGA', $nHargaBeliAkhir)
                ->select(DB::raw('IFNULL(SUM(QTY), 0) as QTY'))
                ->first();

            if ($pembelianAkhir) {
                $nJmlStock = $pembelianAkhir->QTY * $nHargaBeliAkhir;
                $nTotStockAkhir = $pembelianAkhir->QTY;
                $nTotPersediaanAkhir = $nJmlStock;
            }

            // dd($nTotPersediaanAwal);
            $nHargaPokok = ($nTotPersediaanAwal + $nTotPersediaanAkhir) / ($nTotStockAwal + $nTotStockAkhir);
            $nHargaPokok = round($nHargaPokok);

            if ($nHargaPokok <= 0) {
                $nHargaPokok = round($nHargaBeliAkhir);
            }

            $vaData = [
                "Kode" => $kode,
                "Tgl" => $tglPerubahan,
                "HP" => $nHargaPokok,
                "HargaBeliAwal" => $nHargaBeliAwal,
                "HargaBeliAkhir" => $nHargaBeliAkhir,
                "HargaJualAwal" => $nHargaJualAwal,
                "HargaJualAkhir" => $nHargaJualAkhir
            ];
            // dd($vaData);
            StockHP::create($vaData);
        } catch (\Throwable $th) {
            // dd($th);
            throw $th;
        }
    }

    public static function updKartuStockPembelian($cFaktur)
    {
        try {
            $instance = new self();
            KartuStock::where('FAKTUR', $cFaktur)->delete();
            $vaData = DB::table('pembelian as p')
                ->select(
                    'tp.Faktur',
                    'tp.Tgl',
                    'tp.Gudang',
                    'p.Kode',
                    'p.Satuan',
                    'p.Qty',
                    's.Nama as NamaSupplier',
                    'p.Harga',
                    'p.Discount'
                )
                ->leftJoin('totpembelian as tp', 'tp.Faktur', '=', 'p.Faktur')
                ->leftJoin('supplier as s', 's.Kode', '=', 'tp.Supplier')
                ->where('tp.Faktur', '=', $cFaktur)
                ->get();
            foreach ($vaData as $row) {
                $instance->updKartuStock(
                    self::KR_PEMBELIAN,
                    $cFaktur,
                    $row->Tgl,
                    $row->Gudang,
                    $row->Kode,
                    $row->Satuan,
                    $row->Qty,
                    "D",
                    "Pembelian an. " . $row->NamaSupplier,
                    $row->Harga,
                    $row->Discount,
                    0,
                    0,
                    0
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updRekeningPembelian($cFaktur, $lUpdJurnal = true)
    {
        try {
            $instance = new self();
            KartuHutang::where('FKT', $cFaktur)->delete();
            BukuBesar::where('FAKTUR', $cFaktur)->delete();
            Jurnal::where('Faktur', $cFaktur)->delete();
            $vaData = DB::table('totpembelian as t')
                ->select(
                    't.Tgl',
                    't.JthTmp',
                    't.SubTotal',
                    't.Pajak',
                    't.Discount',
                    't.Discount2',
                    't.Tunai',
                    't.Hutang',
                    't.Supplier',
                    't.Gudang',
                    's.Nama as NamaSupplier',
                    't.Faktur',
                    't.PO',
                    't.Total',
                    't.Pembayaran',
                    't.Keterangan',
                    't.PPN'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 't.Supplier')
                ->where('t.Faktur', '=', $cFaktur)
                ->first();
            if ($vaData) {
                $cRekeningPembayaran = GetterSetter::getDBConfig('rek_pembelianKredit_toko');
                if ($vaData->Pembayaran === "T") {
                    $cRekeningPembayaran = GetterSetter::getDBConfig('rek_pembelianTunai_toko');
                }
                // UPD KARTU HUTANG
                $instance->updKartuHutang(
                    self::KR_PEMBELIAN,
                    $cFaktur,
                    $vaData->PO,
                    $vaData->JthTmp,
                    'S',
                    $vaData->Supplier,
                    $vaData->Gudang,
                    'Pembelian an. ' . $vaData->NamaSupplier,
                    $vaData->Total,
                    0,
                );
                // UPD BUKU BESAR
                // Pembelian (Total)
                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    $cRekeningPembayaran,
                    'Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                    $vaData->SubTotal,
                    0,
                    self::KR_PEMBELIAN,
                    'N'
                );
                // PPN (Pajak)
                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_ppnHutangDagang_toko'),
                    'PPN Pembelian  an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                    $vaData->Pajak,
                    0,
                    self::KR_PEMBELIAN,
                    'N'
                );
                // Hutang Dagang
                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_hutangDagang_toko'),
                    'Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                    0,
                    $vaData->Total,
                    self::KR_PEMBELIAN,
                    'N'
                );
                // Discount
                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_discHutangDagang_toko'),
                    'Disc. Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                    0,
                    $vaData->Discount + $vaData->Discount2,
                    self::KR_PEMBELIAN,
                    'N'
                );
                // UPD JURNAL
                if ($lUpdJurnal == true) {
                    // Pembelian (Total)
                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        $cRekeningPembayaran,
                        'Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                        $vaData->SubTotal,
                        0
                    );
                    // PPN (Pajak)
                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_ppnHutangDagang_toko'),
                        'PPN Pembelian  an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                        $vaData->Pajak,
                        0
                    );
                    // Hutang Dagang
                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_hutangDagang_toko'),
                        'Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                        0,
                        $vaData->Total
                    );
                    // Discount
                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_discHutangDagang_toko'),
                        'Disc. Pembelian an. ' . $vaData->NamaSupplier . ' ' . $vaData->Keterangan,
                        0,
                        $vaData->Discount + $vaData->Discount2
                    );
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updKartuHutang(
        $STATUS,
        $FAKTUR,
        $FKT,
        $JTHTMP,
        $SC,
        $SUPPLIER,
        $GUDANG,
        $KETERANGAN,
        $DEBET,
        $KREDIT
    ) {
        $request = request();
        $cUser = Func::dataAuth($request);
        try {
            $vaFaktur = GetterSetter::getUrutFaktur($request);
            if ($DEBET > 0 || $KREDIT > 0) {
                $vaInsert = [
                    'STATUS' => $STATUS,
                    'FAKTUR' => $FAKTUR,
                    'URUT' => $vaFaktur['ID'],
                    'TGL' => Carbon::now()->format('Y-m-d'),
                    'GUDANG' => $GUDANG,
                    'SC' => $SC,
                    'SUPPLIER' => $SUPPLIER,
                    'KETERANGAN' => $KETERANGAN,
                    'DEBET' => $DEBET,
                    'KREDIT' => $KREDIT,
                    'FKT' => $FKT,
                    'JTHTMP' => $JTHTMP, //->isEmpty() ? '1900-01-01' : $JTHTMP,
                    'DATETIME' => Carbon::now()->format('Y-m-d H:i:s'),
                    'USERNAME' => $cUser
                ];
                // dd($vaInsert);
                KartuHutang::create($vaInsert);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updRekeningReturPembelian($cFaktur, $lUpdJurnal = true)
    {
        try {
            $instance = new self();
            KartuHutang::where('FKT', $cFaktur)->delete();
            BukuBesar::where('FAKTUR', $cFaktur)->delete();
            Jurnal::where('Faktur', $cFaktur)->delete();
            $vaData = DB::table('totrtnpembelian as tr')
                ->select(
                    'tr.Gudang',
                    'tp.Pembayaran',
                    'tr.Tgl',
                    's.Nama',
                    'tr.Keterangan',
                    'tr.Total',
                    DB::raw('IFNULL(SUM(tr.Discount + tr.Discount2), 0) as Discount'),
                    'tr.SubTotal',
                    'tr.Pajak',
                    'tr.JthTmp',
                    'tr.FakturPembelian',
                    'tr.Supplier'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 'tr.Supplier')
                ->leftJoin('totpembelian as tp', 'tp.Faktur', '=', 'tr.FakturPembelian')
                ->where('tr.Faktur', '=', $cFaktur)
                ->groupBy('tr.Faktur')
                ->first();
            if ($vaData) {
                $cRekeningPembayaran = GetterSetter::getDBConfig('rek_pembelianKredit_toko');
                if ($vaData->Pembayaran === 'T') {
                    $cRekeningPembayaran = GetterSetter::getDBConfig('rek_pembelianTunai_toko');
                }
                // UPD BUKU  BESAR
                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_hutangDagang_toko'),
                    'Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                    $vaData->Total,
                    0,
                    Upd::KR_RETUR_PEMBELIAN,
                    'N'
                );

                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_discHutangDagang_toko'),
                    'Disc. Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                    $vaData->Discount,
                    0,
                    Upd::KR_RETUR_PEMBELIAN,
                    'N'
                );

                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    $cRekeningPembayaran,
                    'Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                    0,
                    $vaData->SubTotal,
                    Upd::KR_RETUR_PEMBELIAN,
                    'N'
                );

                $instance->updBukuBesar(
                    $cFaktur,
                    $vaData->Gudang,
                    $vaData->Tgl,
                    GetterSetter::getDBConfig('rek_ppnHutangDagang_toko'),
                    'PPN Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                    0,
                    $vaData->Pajak,
                    Upd::KR_RETUR_PEMBELIAN,
                    'N'
                );

                // KARTU HUTANG
                $instance->updKartuHutang(
                    self::KR_RETUR_PEMBELIAN,
                    $cFaktur,
                    $vaData->FakturPembelian,
                    $vaData->JthTmp,
                    'S',
                    $vaData->Supplier,
                    $vaData->Gudang,
                    'Retur Pembelian an. ' . $vaData->Nama,
                    0,
                    $vaData->Total
                );

                // UPD JURNAL
                if ($lUpdJurnal == true) {
                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_hutangDagang_toko'),
                        'Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                        $vaData->Total,
                        0
                    );

                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_discHutangDagang_toko'),
                        'Disc. Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                        $vaData->Discount,
                        0
                    );

                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        $cRekeningPembayaran,
                        'Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                        0,
                        $vaData->SubTotal
                    );

                    $instance->updJurnal(
                        $cFaktur,
                        $vaData->Tgl,
                        GetterSetter::getDBConfig('rek_ppnHutangDagang_toko'),
                        'PPN Retur Pembelian an. ' . $vaData->Nama . ' ' . $vaData->Keterangan,
                        0,
                        $vaData->Pajak
                    );
                }
            }
            // Mengembalikan respons JSON dengan status 'success' jika berhasil
        } catch (\Throwable $th) {
            // Mengembalikan respons JSON dengan status 'error' dan pesan kesalahan jika terjadi kesalahan
            throw $th;
        }
    }

    public static function updKartuStockReturPembelian($cFaktur)
    {
        try {
            $instance = new self();
            KartuStock::where('FAKTUR', $cFaktur)->delete();
            KartuHutang::where('FAKTUR', $cFaktur)->delete();
            $vaData = DB::table('rtnpembelian as rp')
                ->select(
                    'tr.Tgl',
                    'tr.Gudang',
                    'rp.Kode',
                    'rp.Satuan',
                    'rp.Qty',
                    'rp.Harga',
                    'rp.Discount',
                    'tr.PersDisc',
                    'tr.PersDisc2',
                    'tr.PPN',
                    's.Nama'
                )
                ->leftJoin('totrtnpembelian as tr', 'tr.Faktur', '=', 'rp.Faktur')
                ->leftJoin('supplier as s', 's.Kode', '=', 'tr.Supplier')
                ->where('rp.Faktur', '=', $cFaktur)
                ->get();
            foreach ($vaData as $d) {
                $dTgl = $d->Tgl;
                $cGudang = $d->Gudang;
                $cKode = $d->Kode;
                $cSatuan = $d->Satuan;
                $nQty = intval($d->Qty ?? 0);
                $nHarga = intval($d->Harga);
                $nDiscount = intval($d->Discount ?? 0);
                $nPersDisc = intval($d->PersDic ?? 0);
                $nPersDisc2 = intval($d->PersDic2 ?? 0);
                $nPPN = intval($d->PPN ?? 0);
                $cSupplier = $d->Nama;
                $instance->updKartuStock(
                    self::KR_RETUR_PEMBELIAN,
                    $cFaktur,
                    $dTgl,
                    $cGudang,
                    $cKode,
                    $cSatuan,
                    $nQty,
                    'K',
                    "Retur Pembelian ke " . $cSupplier,
                    $nHarga,
                    $nDiscount,
                    $nPersDisc,
                    $nPersDisc2,
                    $nPPN,
                );
            }
            $vaData2 = DB::table('totrtnpembelian as tr')
                ->select(
                    'tr.Tgl',
                    'tr.FakturPembelian',
                    'tr.JthTmp',
                    'tr.Supplier',
                    's.Nama',
                    'tr.Gudang',
                    'tr.Hutang'
                )
                ->leftJoin('supplier as s', 's.Kode', '=', 'tr.Supplier')
                ->where('tr.Faktur', '=', $cFaktur)
                ->first();
            if ($vaData2) {
                $dTgl = $vaData2->Tgl;
                $cFakturPembelian = $vaData2->FakturPembelian;
                $dJthTmp = $vaData2->JthTmp;
                $cSupplier = $vaData2->Supplier;
                $cGudang = $vaData2->Gudang;
                $cNama = $vaData2->Nama;
                $nHutang = $vaData2->Hutang;
            }
            $instance->updKartuHutang(
                self::KR_RETUR_PEMBELIAN,
                $cFaktur,
                $cFakturPembelian,
                $dJthTmp,
                $cGudang,
                $cSupplier,
                'S',
                'Retur Pembelian ke ' . $cNama,
                0,
                $nHutang
            );
        } catch (\Throwable $th) {

            throw $th;
        }
    }

    public static function UpdRekeningKasir($cKodeSesi, $lUpdJurnal = true)
    {
        try {
            $instance = new self();
            BukuBesar::where('Faktur', $cKodeSesi)->delete();
            Jurnal::where('Faktur', $cKodeSesi)->delete();
            $nTotPointDebet = 0;
            $nTotPointKredit = 0;
            $nPointDebet = 0;
            $nPointKredit = 0;
            $vaTotalKeseluruhan = DB::table('totpenjualan as tp')
                ->select(
                    DB::raw('IFNULL(SUM(tp.Total), 0) as totalKeseluruhan'),
                    DB::raw('IFNULL(SUM(tp.BayarKartu), 0) as totalBayarKartu'),
                    DB::raw('IFNULL(SUM(tp.Epayment), 0) as totalEpayment'),
                    DB::raw('IFNULL(SUM(tp.Pajak + tp.Pajak2), 0) as totalPPN'),
                    DB::raw('IFNULL(SUM(tp.Discount + tp.Discount2), 0) as totalDiscount')
                )
                ->leftJoin('sesi_jual as sj', 'sj.SesiJual', '=', 'tp.KodeSesi')
                ->where('tp.KodeSesi', '=', $cKodeSesi)
                ->where('tp.KodeSesi_Retur', '=', '')
                ->first();
            // Mendapatkan Gudang dan Kas Awal
            $vaGudangKasAwal = DB::table('totpenjualan as tp')
                ->select('tp.Gudang', 'sj.KasAwal')
                ->leftJoin('sesi_jual as sj', 'sj.SesiJual', '=', 'tp.KodeSesi')
                ->where('tp.KodeSesi', '=', $cKodeSesi)
                ->where('tp.KodeSesi_Retur', '=', '')
                ->first();

            // Mengakumulasi NominalPointDebet dan NominalPointKredit untuk setiap Faktur
            $fakturData = DB::table('totpenjualan')
                ->select('Faktur')
                ->where('KodeSesi', '=', $cKodeSesi)
                ->where('KodeSesi_Retur', '=', '')
                ->get();
            foreach ($fakturData as $d) {
                $cFaktur = $d->Faktur;
                $nPointDebet = DB::table('mutasimember')
                    ->where('Faktur', '=', $cFaktur)
                    ->sum('NominalPointDebet');
                if ($nPointDebet) {
                    $nTotPointDebet += $nPointDebet;
                }
                $nPointKredit = DB::table('mutasimember')
                    ->where('Faktur', '=', $cFaktur)
                    ->sum('NominalPointKredit');
                if ($nPointKredit) {
                    $nTotPointKredit += $nPointKredit;
                }
            }

            // Mencari Penjualan Tunai
            $vaTunai = DB::table('totpenjualan')
                ->select(
                    DB::raw('IFNULL(SUM(Total), 0) as totalTunai'),
                    DB::raw('IFNULL(SUM(Discount + Discount2), 0) as totalDiscTunai'),
                    DB::raw('IFNULL(SUM(Pajak + Pajak2), 0) as totalPPNTunai')
                )
                ->where('KodeSesi', '=', $cKodeSesi)
                ->where('KodeSesi_Retur', '=', '')
                ->where('CaraBayar', '=', 'Tunai')
                ->first();

            // Mencari Penjualan Non-Tunai
            $vaNonTunai = DB::table('totpenjualan')
                ->select(
                    DB::raw('IFNULL(SUM(Total), 0) as totalNonTunai'),
                    DB::raw('IFNULL(SUM(Discount + Discount2), 0) as totalDiscNonTunai'),
                    DB::raw('IFNULL(SUM(Pajak + Pajak2), 0) as totalPPNNonTunai'),
                    DB::raw('IFNULL(SUM(Kembalian), 0) as totalAmbilKartu')
                )
                ->where('KodeSesi', '=', $cKodeSesi)
                ->where('KodeSesi_Retur', '=', '')
                ->where('CaraBayar', '!=', 'Tunai')
                ->first();

            //  Total Uang Fisik
            $nTotalJurnalUangPecahan1 = DB::table('jurnal_uangpecahan')
                ->where('Faktur', '=', $cKodeSesi)
                ->sum(DB::raw('NOMINAL * QTY'));

            $nTotalDonasi = DB::table('totpenjualan')
                ->where('KodeSesi', '=', $cKodeSesi)
                ->sum('Donasi');

            // Total
            $nTotalUangFisik = DB::table('sesi_jual')
                ->select('totaltunai', 'tgl')
                ->where('sesijual', '=', $cKodeSesi)
                ->first();
            $nTotalJurnalUangPecahan = $nTotalUangFisik->totaltunai;
            $dTgl = $nTotalUangFisik->tgl;
            // Mengambil Variabel
            $cGudang = '';
            $nKasAwal = 0;
            $nTotalKeseluruhan = 0;
            $nTotalBayarKartu = 0;
            $nTotalEPayment = 0;
            $nTotalPPNKeseluruhan = 0;
            $nTotalDiscKeseluruhan = 0;
            $nTotalTunai = 0;
            $nTotalDiscTunai = 0;
            $nTotalPPNTunai = 0;
            $nTotalNonTunai = 0;
            $nTotalDiscNonTunai = 0;
            $nTotalPPNNonTunai = 0;
            $nTotalAmbilKartu = 0;

            // Gudang dan Kas Awal
            if ($vaGudangKasAwal) {
                $cGudang = $vaGudangKasAwal->Gudang;
                $nKasAwal = $vaGudangKasAwal->KasAwal;
            }

            // Total Keseluruhan
            if ($vaTotalKeseluruhan) {
                $nTotalKeseluruhan = $vaTotalKeseluruhan->totalKeseluruhan;
                $nTotalBayarKartu = $vaTotalKeseluruhan->totalBayarKartu;
                $nTotalEPayment = $vaTotalKeseluruhan->totalEpayment;
                $nTotalPPNKeseluruhan = $vaTotalKeseluruhan->totalPPN;
                $nTotalDiscKeseluruhan = $vaTotalKeseluruhan->totalDiscount;
            }

            //  Total Tunai
            if ($vaTunai) {
                $nTotalTunai = $vaTunai->totalTunai;
                $nTotalDiscTunai = $vaTunai->totalDiscTunai;
                $nTotalPPNTunai = $vaTunai->totalPPNTunai;
            }

            // Total Non Tunai
            if ($vaNonTunai) {
                $nTotalNonTunai = $vaNonTunai->totalNonTunai;
                $nTotalDiscNonTunai = $vaNonTunai->totalDiscNonTunai;
                $nTotalPPNNonTunai = $vaNonTunai->totalPPNNonTunai;
                $nTotalAmbilKartu = $vaNonTunai->totalAmbilKartu;
            }
            // Kebutuhan Penjurnalan
            $nKas = $nTotalJurnalUangPecahan - $nKasAwal;
            $nPenjualanTunai = $nTotalTunai - $nTotalDiscTunai + $nTotalPPNTunai;
            $nPenjualanNonTunai = $nTotalNonTunai - $nTotalDiscNonTunai + $nTotalPPNNonTunai;
            $nSelisihPenjualan = $nTotalJurnalUangPecahan - (($nKasAwal + $nTotalKeseluruhan + $nTotalDonasi) - ($nTotalBayarKartu + $nTotalAmbilKartu + $nTotalEPayment));
            // Mulai Penjurnalan
            // Upd Buku Besar
            if ($nKas > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_kas_toko'),
                    "Kas " . $cKodeSesi,
                    $nKas,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nTotalDiscKeseluruhan > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_disc_toko'),
                    "Discount " . $cKodeSesi,
                    $nTotalDiscKeseluruhan,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nTotalEPayment > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_epayment_toko'),
                    "EPayment " . $cKodeSesi,
                    $nTotalEPayment,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nTotalBayarKartu > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_bank_toko'),
                    "Non Tunai " . $cKodeSesi,
                    $nTotalBayarKartu,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nPointDebet > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_biayaPoint_toko'),
                    "Biaya Dapat Point " . $cKodeSesi,
                    $nPointDebet,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );

                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_point_toko'),
                    "Dapat Point " . $cKodeSesi,
                    0,
                    $nPointDebet,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nPointKredit > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_point_toko'),
                    "Bayar Point " . $cKodeSesi,
                    $nPointKredit,
                    0,
                    Upd::KR_PENJUALAN,
                    ''
                );

                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_biayaPoint_toko'),
                    "Biaya Bayar Point " . $cKodeSesi,
                    0,
                    $nPointKredit,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nPenjualanTunai > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_pendapatanPenjualanTunai_toko'),
                    "Penjualan Tunai " . $cKodeSesi,
                    0,
                    $nPenjualanTunai,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }

            if ($nPenjualanNonTunai > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_pendapatanPenjualanNonTunai_toko'),
                    "Penjualan Non Tunai " . $cKodeSesi,
                    0,
                    $nPenjualanNonTunai,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nTotalPPNKeseluruhan > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_ppn_toko'),
                    "PPN " . $cKodeSesi,
                    0,
                    $nTotalPPNKeseluruhan,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }
            if ($nSelisihPenjualan !== 0) {
                if ($nSelisihPenjualan < 0) {
                    $instance->updBukuBesar(
                        $cKodeSesi,
                        $cGudang,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_selisihPenjualan_toko'),
                        "Selisih " . $cKodeSesi,
                        abs($nSelisihPenjualan),
                        0,
                        Upd::KR_PENJUALAN,
                        ''
                    );
                } else if ($nSelisihPenjualan > 0) {
                    $instance->updBukuBesar(
                        $cKodeSesi,
                        $cGudang,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_selisihPenjualan_toko'),
                        "Selisih " . $cKodeSesi,
                        0,
                        $nSelisihPenjualan,
                        Upd::KR_PENJUALAN,
                        ''
                    );
                }
            }

            if ($nTotalDonasi > 0) {
                $instance->updBukuBesar(
                    $cKodeSesi,
                    $cGudang,
                    $dTgl,
                    GetterSetter::getDBConfig('rek_donasi_toko'),
                    "Total Donasi " . $cKodeSesi,
                    0,
                    $nTotalDonasi,
                    Upd::KR_PENJUALAN,
                    ''
                );
            }

            if ($lUpdJurnal == true) {

                //   Upd Jurnal
                if ($nKas > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_kas_toko'),
                        "Kas " . $cKodeSesi,
                        $nKas,
                        0
                    );
                }
                if ($nTotalDiscKeseluruhan > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_disc_toko'),
                        "Discount " . $cKodeSesi,
                        $nTotalDiscKeseluruhan,
                        0
                    );
                }
                if ($nTotalEPayment > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_epayment_toko'),
                        "EPayment " . $cKodeSesi,
                        $nTotalEPayment,
                        0
                    );
                }
                if ($nTotalBayarKartu > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_bank_toko'),
                        "Non Tunai " . $cKodeSesi,
                        $nTotalBayarKartu,
                        0
                    );
                }
                if ($nPointDebet > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_point_toko'),
                        "Dapat Point " . $cKodeSesi,
                        0,
                        $nPointDebet
                    );

                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_biayaPoint_toko'),
                        "Biaya Dapat Point " . $cKodeSesi,
                        $nPointDebet,
                        0
                    );
                }
                if ($nPointKredit > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_point_toko'),
                        "Bayar Point " . $cKodeSesi,
                        $nPointKredit,
                        0
                    );

                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_biayaPoint_toko'),
                        "Biaya Bayar Point " . $cKodeSesi,
                        0,
                        $nPointKredit
                    );
                }
                if ($nPenjualanTunai > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_pendapatanPenjualanTunai_toko'),
                        "Penjualan Tunai " . $cKodeSesi,
                        0,
                        $nPenjualanTunai,
                    );
                }
                if ($nPenjualanNonTunai > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_pendapatanPenjualanNonTunai_toko'),
                        "Penjualan Non Tunai " . $cKodeSesi,
                        0,
                        $nPenjualanNonTunai
                    );
                }
                if ($nTotalPPNKeseluruhan > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_ppn_toko'),
                        "PPN " . $cKodeSesi,
                        0,
                        $nTotalPPNKeseluruhan
                    );
                }
                if ($nSelisihPenjualan !== 0) {
                    if ($nSelisihPenjualan < 0) {
                        $instance->updJurnal(
                            $cKodeSesi,
                            $dTgl,
                            GetterSetter::getDBConfig('rek_selisihPenjualan_toko'),
                            "Selisih " . $cKodeSesi,
                            abs($nSelisihPenjualan),
                            0
                        );
                    } else if ($nSelisihPenjualan > 0) {
                        $instance->updJurnal(
                            $cKodeSesi,
                            $dTgl,
                            GetterSetter::getDBConfig('rek_selisihPenjualan_toko'),
                            "Selisih " . $cKodeSesi,
                            0,
                            $nSelisihPenjualan
                        );
                    }
                }

                if ($nTotalDonasi > 0) {
                    $instance->updJurnal(
                        $cKodeSesi,
                        $dTgl,
                        GetterSetter::getDBConfig('rek_donasi_toko'),
                        "Total Donasi " . $cKodeSesi,
                        0,
                        $nTotalDonasi
                    );
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updRekeningAktivaDanJurnalLain($dTglAwal, $dTglAkhir)
    {
        try {
            DB::table('bukubesar')
                ->where('faktur', 'LIKE', 'JR%')
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->delete();

            $vaData = DB::table('jurnal')
                ->select(
                    'faktur',
                    'tgl',
                    'rekening',
                    'keterangan',
                    'debet',
                    'kredit'
                )
                ->where('faktur', 'LIKE', 'JR%')
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->get();



            foreach ($vaData as $d) {
                self::updBukuBesar(
                    $d->faktur,
                    '',
                    $d->tgl,
                    $d->rekening,
                    $d->keterangan,
                    $d->debet,
                    $d->kredit,
                    self::KR_JURNAL_LAIN,
                    'N'
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function updRekeningTransaksiKas($dTglAwal, $dTglAkhir)
    {
        try {

            DB::table('bukubesar')
                ->whereRaw("(faktur LIKE 'KK%' OR faktur LIKE 'KM%')")
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->delete();
            $vaData = DB::table('jurnal')
                ->select(
                    'faktur',
                    'tgl',
                    'rekening',
                    'keterangan',
                    'debet',
                    'kredit'
                )
                ->whereRaw("(faktur LIKE 'KK%' OR faktur LIKE 'KM%')")
                ->whereBetween('tgl', [$dTglAwal, $dTglAkhir])
                ->get();

            foreach ($vaData as $d) {
                self::updBukuBesar(
                    $d->faktur,
                    '',
                    $d->tgl,
                    $d->rekening,
                    $d->keterangan,
                    $d->debet,
                    $d->kredit,
                    self::KR_JURNAL_LAIN,
                    'N'
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
