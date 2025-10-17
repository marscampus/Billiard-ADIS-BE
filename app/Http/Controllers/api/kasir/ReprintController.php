<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\fun\KartuStock;
use App\Models\master\MutasiMember;
use App\Models\penjualan\Penjualan;
use App\Models\penjualan\TotPenjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReprintController extends Controller
{
    public function getFaktur(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $cKodeSesi = $vaRequestData['KODESESI'];
            $today = Carbon::today()->toDateString();
            $vaData = DB::table('totpenjualan as tp')
                ->select(
                    'tp.FAKTUR',
                    'tp.TGL',
                    'tp.DISCOUNT',
                    'tp.PAJAK',
                    'tp.TOTAL',
                    'tp.CARABAYAR',
                    'tp.TUNAI',
                    'tp.EPAYMENT',
                    'tp.BAYARKARTU',
                    'u.email AS USERNAME'
                )
                ->leftJoin('users as u', 'u.email', '=', 'tp.UserName')
                ->whereDate('tp.DATETIME', '=', $today)
                ->where('tp.KodeSesi', '=', $cKodeSesi)->orderBy('tp.TGL', 'desc')
                ->get();
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaData,
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
    public function cariFaktur(Request $request)   // --------------------< Detail by FAKTUR >
    {
        try {
            $request->validate([
                'Faktur' => 'required|string',
            ]);

            $cFaktur = $request->Faktur;
            $vaHeader = DB::table('totpenjualan as t')
                ->select(
                    't.username',
                    't.datetime',
                    't.carabayar',
                    't.tunai',
                    't.bayarkartu',
                    't.biayakartu',
                    't.ambilkartu',
                    't.epayment',
                    't.namakartu',
                    't.nomorkartu',
                    't.namapemilik',
                    't.tipeepayment',
                    't.kembalian',
                    'm.nama as member',
                    't.total'
                )
                ->leftJoin('member as m', 'm.kode', '=', 't.member')
                ->where('faktur', '=', $cFaktur)
                ->first();
            $vaArray = [
                'header' => null,
                'detail' => []
            ];
            if ($vaHeader) {
                $vaArray = [
                    'FAKTUR' => $cFaktur,
                    'ANTRIAN' => 0,
                    'KASIR' => $vaHeader->username,
                    'LOGOPERUSAHAAN' => GetterSetter::getDBConfig('logoPerusahaan'),
                    'NAMAPERUSAHAAN' => GetterSetter::getDBConfig('namaPerusahaan'),
                    'ALAMATPERUSAHAAN' => GetterSetter::getDBConfig('alamatPerusahaan'),
                    'TELP' => GetterSetter::getDBConfig('noPengaduan'),
                    'TANGGAL' => $vaHeader->datetime,
                    'CARABAYAR' => $vaHeader->carabayar,
                    'TUNAI' => $vaHeader->tunai,
                    'BAYARKARTU' => $vaHeader->bayarkartu,
                    'BIAYAKARTU' => $vaHeader->biayakartu,
                    'AMBILKARTU' => $vaHeader->ambilkartu,
                    'EPAYMENT' => $vaHeader->epayment,
                    'NAMAKARTU' => $vaHeader->namakartu,
                    'NOMORKARTU' => $vaHeader->nomorkartu,
                    'NAMAPEMILIK' => $vaHeader->namapemilik,
                    'TIPEEPAYMENT' => $vaHeader->tipeepayment,
                    'KEMBALIAN' => $vaHeader->kembalian,
                    'MEMBER' => $vaHeader->member,
                    'TOTAL' => $vaHeader->total
                ];
                $vaDetail = DB::table('penjualan as p')
                    ->select(
                        'p.kode',
                        's.kode_toko',
                        's.nama',
                        'p.harga',
                        'p.qty',
                        'p.discount',
                        'p.Jumlah'
                    )
                    ->leftJoin('stock as s', 's.kode', '=', 'p.kode')
                    ->where('p.faktur', '=', $cFaktur)
                    ->get();
                foreach ($vaDetail as $d) {
                    $items[] = [
                        'KODE' => $d->kode,
                        'BARCODE' => $d->kode_toko,
                        'NAMA' => $d->nama,
                        'QTY' => $d->qty,
                        'HJ' => $d->harga,
                        'DISCOUNT' => $d->discount,
                        'SUBTOTAL' => $d->Jumlah
                    ];
                }
                $vaArray['items'] = $items;
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => $vaArray,
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

    public function haveMember(Request $request)
    {
        try {
            $member = $request->MEMBER;
            $faktur = $request->FAKTUR;
            $data = MutasiMember::where('MEMBER', $member)->where('FAKTUR', $faktur)->first();

            if ($data) {
                // Mendapatkan NAMA_MEMBER dari Member menggunakan subquery
                $namaMember = DB::table('member')
                    ->where('KODE', $data->MEMBER)
                    ->value('NAMA');

                // Menambahkan NAMA_MEMBER ke $data
                $data->NAMA_MEMBER = $namaMember;
            }
            // dd($data);
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Sukses',
                'data' => $data,
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $th) {
            // JIKA GENERAL ERROR
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
