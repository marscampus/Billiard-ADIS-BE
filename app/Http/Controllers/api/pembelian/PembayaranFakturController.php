<?php

namespace App\Http\Controllers\api\pembelian;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuHutang;
use App\Models\master\Gudang;
use App\Models\master\Supplier;
use App\Models\pembelian\TotPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PembayaranFakturController extends Controller
{
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
            $startDate = $request->TglAwal;
            $endDate = $request->TglAkhir;
            $vaData = DB::table('kartuhutang as kh')
                ->select(
                    'kh.FAKTUR',
                    'kh.FKT',
                    'kh.TGL',
                    'kh.JTHTMP',
                    's.Nama as SUPPLIER',
                    'g.Keterangan as GUDANG',
                    'kh.KETERANGAN',
                    'kh.KREDIT'
                )
                ->leftJoin('gudang as g', 'g.Kode', '=', 'kh.Gudang')
                ->leftJoin('supplier as s', 's.Kode', '=', 'kh.Supplier')
                ->whereBetween('kh.Tgl', [$startDate, $endDate])
                ->where('kh.FKT', 'LIKE', 'PH%')->orderByDesc('kh.TGL')
                ->get();
            $vaResult = [
                'data' => $vaData,
                'total_data' => count($vaData)
            ];
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

    public function getDataBySupplier(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        $cSupplier = $vaRequestData['Supplier'];
        $vaResult = [];
        $isLunas = false;
        unset($vaRequestData['auth']);
        try {
            $vaData = DB::table('totpembelian as t')
                ->select(
                    't.Faktur',
                    't.Total',
                    't.PO',
                    't.Supplier',
                    't.Tgl',
                    't.JthTmp',
                    'tp.SubTotal as JumlahPO',
                    'tp.Discount as DiscountPO',
                    'tp.Pajak as PajakPO',
                    'tp.Total as TotalPO',
                    't.Total',
                    't.Pembayaran'
                )
                ->leftJoin('totpo as tp', 'tp.Faktur', '=', 't.PO')
                ->where('t.Supplier', '=', $cSupplier)
                ->orderByDesc('t.Tgl')
                ->get();
            foreach ($vaData as $d) {
                $nTotalPembelian = $d->Total;
                $nTotalRetur = 0;
                // Cari Total Retur
                $vaTotRetur = DB::table('totrtnpembelian')
                    ->select(
                        DB::raw('IFNULL(SUM(Total), 0) as TotalRetur')
                    )
                    ->where('FakturPembelian', '=', $d->Faktur)
                    ->first();
                if ($vaTotRetur) {
                    $nTotalRetur = $vaTotRetur->TotalRetur;
                }

                $nSisa = 0;
                $nSisa = $nTotalPembelian - $nTotalRetur;

                $vaResult[] = [
                    'FAKTURPO' => $d->PO,
                    'FAKTURPEMBELIAN' => $d->Faktur,
                    'SUPPLIER' => $d->Supplier,
                    'TGL' => $d->Tgl,
                    'JTHTMP' => $d->JthTmp,
                    'JUMLAHPO' => $d->JumlahPO,
                    'DISCOUNTPO' => $d->DiscountPO,
                    'PAJAKPO' => $d->PajakPO,
                    'TOTALPO' => $d->TotalPO,
                    'TOTALTERIMA' => $d->Total,
                    'TOTALRETUR' => $nTotalRetur,
                    'PEMBAYARAN' => $isLunas == false ? abs($nSisa) : 0,
                    'SISA' => $nSisa,
                    'VALSISA' => $nSisa,
                    "TIPE" => $d->Pembayaran === 'T' ? 'TUNAI' : 'KREDIT'
                ];
            }
            // JIKA REQUEST SUKSES

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $vaResult,
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
        try {
            $cFaktur = $request->FAKTUR;
            $cRekening = $request->REKENING;
            foreach ($request->input('tabelPembayaranFaktur') as $item) {
                $cFakturPB = $item['FAKTURPEMBELIAN'];
                $nTotal = $item['PEMBAYARAN'];
                Upd::updPelunasanHutang($cFaktur, $cFakturPB, $nTotal);
            }
            Upd::updRekeningPelunasanHutang($cFaktur, $cFakturPB, $cRekening);
            GetterSetter::setLastKodeRegister('PH');
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil menyimpan data',
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
            $fkt = $request->FKT;
            KartuHutang::where('FKT', $fkt)->delete();
            // return response()->json(['status' => 'success']);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil menghapus data',
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
