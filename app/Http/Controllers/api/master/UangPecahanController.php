<?php

namespace App\Http\Controllers\api\master;

use App\Helpers\ApiResponse;
use App\Helpers\Func;
use App\Http\Controllers\Controller;
use App\Models\master\UangPecahan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UangPecahanController extends Controller
{

    function data(Request $request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        $cUser = $vaRequestData['auth']['name'];
        unset($vaRequestData['auth']);
        try {
            $nLimit = 10;
            $vaData = DB::table('uangpecahan')
                ->select(
                    'KODE',
                    'STATUS',
                    'NOMINAL'
                )
                ->orderBy('KODE', 'ASC')
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

    function store(Request $request)
    {
        try {
            $messages = config('validate.validation');
            $validator = validator::make($request->all(), [
                'KODE' => 'required|max:4',
                'STATUS' => 'max:1',
                'NOMINAL' => 'numeric|digits_between:1,16'
            ], $messages);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $message = implode(' ', $errors);
                return response()->json(array_merge(ApiResponse::INVALID_REQUEST, ['messageValidator' => $message]));
            }

            $kode = $request->KODE;
            $existingData = UangPecahan::where('KODE', $kode)->first();
            if ($existingData) {
                return response()->json(ApiResponse::ALREADY_EXIST);
            }
            $status = $request->STATUS;
            $nominal = $request->NOMINAL;
            $uangPecahan = UangPecahan::create([
                'KODE' => $kode,
                'STATUS' => $status,
                'NOMINAL' => $nominal
            ]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => 'error']);
            return response()->json(ApiResponse::PROCESSING_ERROR);
        }
        // return response()->json(['status' => 'success']);
        return response()->json(ApiResponse::SUCCESS);
    }

    function update(Request $request)
    {
        $KODE = $request->KODE;
        try {
            $messages = config('validate.validation');
            $validator = validator::make($request->all(), [
                'KODE' => 'required|max:4',
                'STATUS' => 'max:1',
                'NOMINAL' => 'numeric|digits_between:1,16'
            ], $messages);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $message = implode(' ', $errors);
                return response()->json(array_merge(ApiResponse::INVALID_REQUEST, ['messageValidator' => $message]));
            }
            $uangPecahan = uangPecahan::where('KODE', $KODE)->update([
                'STATUS' => $request->STATUS,
                'NOMINAL' => $request->NOMINAL
            ]);
        } catch (\Throwable $th) {
            // return response()->json(['status' => 'error']);
            return response()->json(ApiResponse::PROCESSING_ERROR);
        }
        // return response()->json(['status' => 'success']);
        return response()->json(ApiResponse::SUCCESS);
    }

    function delete(Request $request)
    {
        try {
            $uangPecahan = UangPecahan::findOrFail($request->KODE);
            // dd($uangPecahan);
            $uangPecahan->delete();
            // return response()->json(['status' => 'success']);
            return response()->json(ApiResponse::SUCCESS);
        } catch (\Throwable $th) {
            // dd($th);
            // return response()->json(['status' => 'error']);
            return response()->json(ApiResponse::PROCESSING_ERROR);
        }
    }
}
