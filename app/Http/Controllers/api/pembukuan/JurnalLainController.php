<?php

namespace App\Http\Controllers\api\pembukuan;

use App\Helpers\Func;
use App\Helpers\GetterSetter;
use App\Helpers\Upd;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class JurnalLainController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.ID',
                    'j.Faktur',
                    'j.Rekening',
                    'j.Tgl',
                    'r.Keterangan as NamaPerkiraan',
                    'j.Keterangan',
                    'j.Debet',
                    'j.Kredit',
                    'j.UserName'
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'j.Rekening')
                ->whereBetween('j.Tgl', [$request->TglAwal, $request->TglAkhir])
                ->where('j.Faktur', 'like', "JR%")
                ->orderByDesc('j.ID')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'data' => $vaData,
                'total_data' => count($vaData),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function store(Request $request)
    {
        $cUser = Func::dataAuth($request);
        try {
            $vaArrayJurnal = [];
            $vaArrayBukuBesar = [];

            if ($request->tabelJurnalLain) {
                foreach ($request->tabelJurnalLain as $data) {
                    $vaArray = [
                        'FAKTUR' => $request->Faktur,
                        'TGL' => $request->Tgl,
                        // 'CABANG' => GetterSetter::getGudang($cEmail),
                        'REKENING' => $data['Rekening'],
                        'KETERANGAN' => $data['Keterangan'],
                        'DEBET' => $data['Debet'],
                        'KREDIT' => $data['Kredit'],
                        'DATETIME' => Carbon::now(),
                        'USERNAME' => $cUser
                    ];
                    $vaArrayJurnal[] = $vaArray;

                    // Data untuk tabel bukubesar (menggunakan data yang sama + status)
                    $dataBukuBesar = $vaArray; // salin data jurnal
                    $dataBukuBesar['status'] = Upd::kr_jurnal; // tambahkan status
                    $vaArrayBukuBesar[] = $dataBukuBesar;
                }
            }

            if (empty($vaArray)) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Tabel Jurnal Kosong',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            DB::table('jurnal')->insert($vaArrayJurnal);
            DB::table('bukubesar')->insert($vaArrayBukuBesar);
            GetterSetter::setKodeFaktur('JR');

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            $Faktur = $request->faktur;

            $deletedJurnal = DB::table('jurnal')->where('Faktur', $Faktur)->delete();
            $deletedBukuBesar = DB::table('bukubesar')->where('Faktur', $Faktur)->delete();

            if (!$deletedJurnal || !$deletedBukuBesar) {
                DB::rollBack();
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Hapus Data',
                    'datetime' => now()->format('Y-m-d H:i:s')
                ], 400);
            }

            DB::commit();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Hapus Data',
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }
}
