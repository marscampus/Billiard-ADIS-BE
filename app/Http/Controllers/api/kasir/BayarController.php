<?php

namespace App\Http\Controllers\api\kasir;

use App\Helpers\GetterSetter;
use App\Http\Controllers\Controller;
use App\Models\kasir\Kasir;
use App\Models\kasir\KasirTmp;
use App\Models\kasir\TotKasir;
use App\Models\kasir\TotKasirTmp;
use Illuminate\Http\Request;

class BayarController extends Controller
{
    public function getDataEdit(Request $request)
    {
        try {

            $faktur = $request->FAKTUR;
            $kartu = '';
            $nokartu = '';
            $notrace = '';
            $administrasi = '';
            $totkasir = TotKasir::where('FAKTUR', $faktur)->first();
            if ($totkasir) {
                $total = $totkasir->TOTAL;
                $bayar = $totkasir->BAYAR;
                $tunai = $totkasir->TUNAI;
                $voucher = $totkasir->VOUCHER;
                $bayarkartu = $totkasir->BAYARKARTU;
                $infaq = $totkasir->INFAQ;
                if ($bayarkartu > 0) {
                    $kartu = $totkasir->KARTU;
                    $nokartu = $totkasir->NOKARTU;
                    $notrace = $totkasir->NOTRACE;
                    $administrasi = $totkasir->ADMINISTRASI;
                }
                $result = [
                    'TOTAL' => $total,
                    'BAYAR' => $bayar,
                    'TUNAI' => $tunai,
                    'VOUCHER' => $voucher,
                    'BAYARKARTU' => $bayarkartu,
                    'INFAQ' => $infaq,
                    'KARTU' => $kartu,
                    'NOKARTU' => $nokartu,
                    'NOTRACE' => $notrace,
                    'ADMINISTRASI' => $administrasi
                ];

                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mendapatkan Data',
                    'data' => $result,
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Data Tidak Ada',
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


}
