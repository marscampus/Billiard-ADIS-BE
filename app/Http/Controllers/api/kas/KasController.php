<?php

namespace App\Http\Controllers\api\kas;

use Carbon\Carbon;
use App\Helpers\Func;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class KasController extends Controller
{
    public function getFaktur(Request $request)
    {
        $KODE = $request->KODE;
        $LEN = $request->LEN;
        try {
            $response = GetterSetter::getKodeFaktur($KODE, $LEN);
            //code...
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Ambil Data',
                'data' => $response,
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function data(Request $request)
    {
        try {
            $vaRequestData = $request->json()->all();

            $dTglAwal = $vaRequestData['TglAwal'];
            $dTglAkhir = $vaRequestData['TglAkhir'];

            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.Faktur',
                    'j.Tgl',
                    'j.Rekening',
                    DB::raw('IFNULL(SUM(j.Debet), 0) as Penerimaan'),
                    DB::raw('IFNULL(SUM(j.Kredit), 0) as Pengeluaran'),
                    'j.Keterangan'
                )
                ->whereBetween('j.Tgl', [$dTglAwal, $dTglAkhir])
                // ->whereNot('j.Faktur', 'like', "JR%")
                ->groupBy('j.Faktur', 'j.Tgl', 'j.Keterangan', 'j.Rekening', 'j.Kredit', 'j.Debet')
                ->havingRaw("(j.Faktur LIKE 'KM%' AND SUM(j.Kredit) = 0) OR (j.Faktur LIKE 'KK%' AND SUM(j.Debet) = 0)")
                ->get();

            $vaResult = $vaData->map(function ($d, $index) use (&$nTotalPengeluaran, &$nTotalPenerimaan) {
                $nTotalPengeluaran += $d->Pengeluaran;
                $nTotalPenerimaan += $d->Penerimaan;

                return [
                    'No' => $index + 1,
                    'Faktur' => $d->Faktur,
                    'Tgl' => $d->Tgl,
                    'Rekening' => $d->Rekening,
                    'Penerimaan' => $d->Penerimaan,
                    'Pengeluaran' => $d->Pengeluaran,
                    'Keterangan' => $d->Keterangan
                ];
            })->toArray();

            $vaTotal = [
                'TotalPengeluaran' => $nTotalPengeluaran,
                'TotalPenerimaan' => $nTotalPenerimaan
            ];

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil',
                'data' => $vaResult,
                'total_data' => count($vaResult),
                'totals' => $vaTotal,
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function getDataByFakturDebet(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $Kas = DB::table('jurnal')->select('jurnal.ID', 'jurnal.Faktur', 'jurnal.Tgl', 'jurnal.rekening', 'rekening.keterangan AS KeteranganRekening', 'jurnal.Debet AS Jumlah', 'jurnal.Keterangan')
                ->join('rekening', 'jurnal.rekening', '=', 'rekening.kode')
                ->where('jurnal.Faktur', '=', $Faktur)
                ->where('jurnal.Debet', '!=', 0)
                ->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Ambil Data',
                'data' => $Kas,
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function getDataByFakturKredit(Request $request)
    {
        $Faktur = $request->Faktur;
        try {
            $Kas = DB::table('jurnal')->select('jurnal.ID', 'jurnal.Faktur', 'jurnal.Tgl', 'jurnal.rekening', 'rekening.keterangan AS KeteranganRekening', 'jurnal.Kredit AS Jumlah', 'jurnal.Keterangan')
                ->join('rekening', 'jurnal.rekening', '=', 'rekening.kode')
                ->where('jurnal.Faktur', '=', $Faktur)
                ->where('jurnal.Kredit', '!=', 0)
                ->get();
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Ambil Data',
                'data' => $Kas,
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $vaRequestData = $request->json()->all();
        $cUser = $vaRequestData['auth']['email'];

        try {
            // Validasi data input
            $validator = Validator::make($vaRequestData, [
                'Faktur' => 'required|max:15|unique:jurnal,Faktur',
                'Tgl' => 'required|date',
                'Rekening' => 'required',
                'detail' => 'required|array',
                'detail.*.Rekening' => 'required',
                'detail.*.Jumlah' => 'required|numeric|min:1',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'unique' => ':attribute sudah ada di database.',
                'numeric' => 'Kolom :attribute harus berupa angka.',
                'min' => 'Kolom :attribute harus lebih dari 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => $validator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $cFaktur = $vaRequestData['Faktur'];
            $dTgl = $vaRequestData['Tgl'];
            $cPrefix = substr($cFaktur, 0, 2);
            $cFakturBaru = GetterSetter::getKodeFaktur($cPrefix, 2);

            // Data header
            $headerData = [
                'Faktur' => $cFakturBaru,
                'Tgl' => $dTgl,
                'Rekening' => $vaRequestData['Rekening'],
                'Keterangan' => $vaRequestData['Keterangan'],
                ($cPrefix === 'KM' ? 'Debet' : 'Kredit') => $vaRequestData['Total'],
                'CabangEntry' => '',
                'UserName' => $cUser,
                'DateTime' => now(),
            ];

            // Simpan header
            DB::table('jurnal')->insert($headerData);

            // Data detail
            $detailData = array_map(function ($item) use ($cFakturBaru, $dTgl, $cUser, $cPrefix) {
                return [
                    'Faktur' => $cFakturBaru,
                    'Tgl' => $dTgl,
                    'Rekening' => $item['Rekening'],
                    'Keterangan' => $item['Keterangan'],
                    ($cPrefix === 'KM' ? 'Kredit' : 'Debet') => $item['Jumlah'],
                    'CabangEntry' => '',
                    'UserName' => $cUser,
                    'DateTime' => now(),
                ];
            }, $vaRequestData['detail']);

            // Simpan detail
            DB::table('jurnal')->insert($detailData);

            GetterSetter::setKodeFaktur($cPrefix);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Create Data',
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }


    public function getDataEditPenerimaanKas(Request $request)
    {
        $cFaktur = $request->input('Faktur');

        try {
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.Faktur',
                    'j.Tgl',
                    'j.Rekening',
                    'r.Keterangan as KeteranganRekening',
                    'j.Keterangan',
                    'j.Debet',
                    'j.Kredit'
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'j.Rekening')
                ->where('j.Faktur', $cFaktur)
                ->get();

            $header = $vaData->firstWhere('Debet', '>', 0);
            $detail = $vaData->where('Kredit', '>', 0)->map(function ($item) {
                return [
                    'Rekening' => $item->Rekening,
                    'KeteranganRekening' => $item->KeteranganRekening,
                    'Keterangan' => $item->Keterangan,
                    'Jumlah' => $item->Kredit
                ];
            })->values();

            if ($header) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil',
                    'Faktur' => $header->Faktur,
                    'Tgl' => $header->Tgl,
                    'RekeningDebet' => $header->Rekening,
                    'KetRekeningDebet' => $header->KeteranganRekening,
                    'Keterangan' => $header->Keterangan,
                    'Total' => $header->Debet,
                    'detail' => $detail,
                    'datetime' => now()->format('Y-m-d H:i:s')
                ]);
            }

            return response()->json([
                'status' => self::$status['NOT_FOUND'],
                'message' => 'Data Tidak Ditemukan atau Error',
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function getDataEditPengeluaranKas(Request $request)
    {
        $cFaktur = $request->input('Faktur');

        try {
            $vaData = DB::table('jurnal as j')
                ->select(
                    'j.Faktur',
                    'j.Tgl',
                    'j.Rekening',
                    'r.Keterangan as KeteranganRekening',
                    'j.Keterangan',
                    'j.Debet',
                    'j.Kredit'
                )
                ->leftJoin('rekening as r', 'r.Kode', '=', 'j.Rekening')
                ->where('j.Faktur', $cFaktur)
                ->get();

            $header = $vaData->firstWhere('Kredit', '>', 0);
            $detail = $vaData->where('Debet', '>', 0)->map(function ($item) {
                return [
                    'Rekening' => $item->Rekening,
                    'KeteranganRekening' => $item->KeteranganRekening,
                    'Keterangan' => $item->Keterangan,
                    'Jumlah' => $item->Debet
                ];
            })->values();

            if ($header) {
                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil',
                    'Faktur' => $header->Faktur,
                    'Tgl' => $header->Tgl,
                    'RekeningDebet' => $header->Rekening,
                    'KetRekeningDebet' => $header->KeteranganRekening,
                    'Keterangan' => $header->Keterangan,
                    'Total' => $header->Kredit,
                    'detail' => $detail,
                    'datetime' => now()->format('Y-m-d H:i:s')
                ]);
            }

            return response()->json([
                'status' => self::$status['NOT_FOUND'],
                'message' => 'Data Tidak Ditemukan atau Error',
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }

    public function update(Request $request)
    {
        $vaRequestData = $request->json()->all();
        $cUser = $vaRequestData['auth']['email'];

        try {
            // Validasi data input
            $validator = Validator::make($vaRequestData, [
                'Faktur' => 'required|max:15',
                'Tgl' => 'required|date',
                'Rekening' => 'required',
                'detail' => 'required|array',
                'detail.*.Rekening' => 'required',
                'detail.*.Jumlah' => 'required|numeric|min:1',
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'numeric' => 'Kolom :attribute harus berupa angka.',
                'min' => 'Kolom :attribute harus lebih dari 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => $validator->errors()->first(),
                    'datetime' => now()->format('Y-m-d H:i:s')
                ], 422);
            }

            $cFaktur = $vaRequestData['Faktur'];
            $dTgl = $vaRequestData['Tgl'];

            // Hapus data jurnal yang ada sesuai dengan faktur
            DB::table('jurnal')->where('Faktur', '=', $cFaktur)->delete();

            $prefix = substr($cFaktur, 0, 2);
            $headerData = [
                'Faktur' => $cFaktur,
                'Tgl' => $dTgl,
                'Rekening' => $vaRequestData['Rekening'],
                'Keterangan' => $vaRequestData['Keterangan'],
                ($prefix === 'KM' ? 'Debet' : 'Kredit') => $vaRequestData['Total'],
                'CabangEntry' => '',
                'UserName' => $cUser,
                'DateTime' => now(),
            ];

            // Simpan header
            DB::table('jurnal')->insert($headerData);

            // Data detail
            $detailData = array_map(function ($item) use ($cFaktur, $dTgl, $cUser, $prefix) {
                return [
                    'Faktur' => $cFaktur,
                    'Tgl' => $dTgl,
                    'Rekening' => $item['Rekening'],
                    'Keterangan' => $item['Keterangan'],
                    ($prefix === 'KM' ? 'Kredit' : 'Debet') => $item['Jumlah'],
                    'CabangEntry' => '',
                    'UserName' => $cUser,
                    'DateTime' => now(),
                ];
            }, $vaRequestData['detail']);

            // Simpan detail
            DB::table('jurnal')->insert($detailData);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Data berhasil diperbarui',
                'datetime' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 400);
        }
    }


    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            $Faktur = $request->Faktur;

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
