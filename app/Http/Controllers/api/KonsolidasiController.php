<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KonsolidasiController extends Controller
{
    public function konsolidasi(Request $request)
    {
        try {

            $vaData = [];

            array_push($vaData, [
                'kas' => '',
                'kewajiban' => '',
                'modal' => '',
                'pendapatan' => '',
                'biaya' => '',,
                'tanggal' => '',
                'buc' => ''
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'rc' => 200,
                'data' => $vaData,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
