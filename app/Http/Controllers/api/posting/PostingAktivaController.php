<?php

namespace App\Http\Controllers\api\posting;

use Carbon\Carbon;
use App\Helpers\Upd;
use App\Helpers\Func;
use Illuminate\Http\Request;
use App\Helpers\GetterSetter;
use Illuminate\Support\Facades\DB;
use App\Helpers\PerhitunganPinjaman;
use App\Http\Controllers\Controller;

class PostingAktivaController extends Controller
{
    public function data(Request $request)
    {
        $originalMaxExecutionTime = ini_get('max_execution_time');
        try {
            ini_set('max_execution_time', '0');
            $dBulan = $request['Bulan'];
            $dTahun = $request['Tahun'];
            $nTime = mktime(0, 0, 0, $dBulan + 1, 0, $dTahun);
            $dTgl = date('Y-m-d', $nTime);
            // $dTglBlnIni = date('Y-m-d', mktime(0, 0, 0, $dBulan + 1, $dTahun));
            $vaData = DB::table('aktiva as a')
                ->select(
                    'a.Kode',
                    'a.Nama',
                    'a.Unit',
                    'a.TglPerolehan',
                    'a.HargaPerolehan',
                    'a.Golongan',
                    'a.Lama',
                    'a.CabangEntry as Cabang',
                    'a.Residu',
                    'a.TarifPenyusutan',
                    'a.PenyusutanPerBulan',
                    'a.JenisPenyusutan',
                    'g.Keterangan as NamaGolongan',
                    'a.Status'
                )
                ->leftJoin('golonganaktiva as g', 'g.Kode', '=', 'a.Golongan')
                ->where('a.TglPerolehan', '<=', $dTgl)
                ->where('a.TglWriteOff', '>', $dTgl)
                ->where('a.TglPenyusutan', '<=', $dTgl)
                ->where('g.Kode', '<>', 'BDD')
                ->orderBy('a.Golongan')
                ->orderBy('a.Kode')
                ->get();


            $nTotalHargaPerolehan = 0;
            $nTotalPenyusutanAwal = 0;
            $nTotalPenyusutanBulanIni = 0;
            $nTotalPenyusutanAkhir = 0;
            $nTotalNilaiBuku = 0;
            $optJenisLaporan = '';
            $vaResult = [];
            foreach ($vaData as $d) {
                $va = PerhitunganPinjaman::getPenyusutan($d->Kode, $dTgl);
                $nPenyusutanAkhir = $va['Akhir'];
                $nNilaiBuku = $d->HargaPerolehan - $nPenyusutanAkhir;

                if ($nNilaiBuku > 0) {
                    $nNilaiBukuAkhir = $nNilaiBuku;
                    $nNilaiBulanIni = $va['BulanIni'];
                    $nPenyusutanAkhir = $va['Akhir'];
                    $nPenyusutanAwal = $va['Awal'];
                } else {
                    $nNilaiBukuAkhir = Func::String2Number($d->Unit);

                    $nNilaiBulanIni = $va['BulanIni'];
                    $nPenyusutanAkhir = $va['Akhir'] - Func::String2Number($d->Unit);
                    $nPenyusutanAwal = $va['Awal'] - Func::String2Number($d->Unit);
                }

                if ($d->Status == '2') {
                    $nNilaiBukuAkhir = 0;
                }

                $bFilter = true;
                if ($optJenisLaporan == 'Aktif' && $nNilaiBukuAkhir == 0 && $nNilaiBulanIni == 0) {
                    $bFilter = false;
                }
                if ($optJenisLaporan == 'TidakAktif' && $nNilaiBukuAkhir > 1) {
                    $bFilter = false;
                }

                if ($bFilter) {
                    $nTotalHargaPerolehan += $d->HargaPerolehan;
                    $nTotalPenyusutanAwal += $nPenyusutanAwal;
                    $nTotalPenyusutanBulanIni += $va['BulanIni'];
                    $nTotalPenyusutanAkhir += $nPenyusutanAkhir;
                    $nTotalNilaiBuku += $nNilaiBukuAkhir;

                    $cCabang = $d->Cabang;
                    $cGolongan = $d->Golongan;

                    if (empty($vaResult[$cCabang][$cGolongan])) {
                        $cKeterangan = GetterSetter::getKeterangan($cGolongan, 'Keterangan', 'golonganaktiva');
                        // return $cKeterangan;
                        $vaResult[$cCabang][$cGolongan] = [
                            "Cabang" => $cCabang,
                            "Golongan" => $cGolongan,
                            "Keterangan" => $cKeterangan,
                            "Awal" => 0,
                            "Penyusutan" => 0,
                            "Akhir" => 0,
                            "NilaiBuku" => 0,
                        ];
                    }

                    $vaResult[$cCabang][$cGolongan]['Awal'] += $nPenyusutanAwal;
                    $vaResult[$cCabang][$cGolongan]['Penyusutan'] += $nNilaiBulanIni;
                    $vaResult[$cCabang][$cGolongan]['Akhir'] += $nPenyusutanAkhir;
                    $vaResult[$cCabang][$cGolongan]['NilaiBuku'] += $nNilaiBukuAkhir;
                }
            }
            $finalResult = [];
            foreach ($vaResult as $cabangData) {
                foreach ($cabangData as $golonganData) {
                    $finalResult[] = $golonganData;
                }
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $finalResult,
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        } finally {
            ini_set('max_execution_time', $originalMaxExecutionTime);
        }
    }

    public function store(Request $request)
    {
        try {
            $cUser = Func::dataAuth($request);
            $dBulan = $request['Bulan'];
            $dTahun = $request['Tahun'];
            $nTime = mktime(0, 0, 0, $dBulan + 1, 0, $dTahun);
            $dTgl = date('Y-m-d', $nTime);
            $dTglTransaksi = Carbon::now()->format('Y-m-d');
            foreach ($request->input('updJurnal') as $data) {
                $cCabangEntry = $data['Cabang'];
                $cGolongan = $data['Golongan'];
                $nPenyusutan = $data['Penyusutan'];

                if (!$nPenyusutan || $nPenyusutan < 1) {
                    return response()->json([
                        'status' => self::$status['GAGAL'],
                        'message' => 'Penyusutan Sudah Habis',
                        'datetime' => date('Y-m-d H:i:s')
                    ], 400);
                }

                $exist = DB::table('jurnal')
                    ->where('keterangan', 'like', 'AKM PENYUSUTAN%')
                    ->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(keterangan, ' ', -2), ' ', 1) = ?", [$dTgl])
                    ->exists();

                if ($exist) {
                    return response()->json([
                        'status' => self::$status['GAGAL'],
                        'message' => 'Penyusutan Sudah Dilakukan Dibulan Ini',
                        'datetime' => date('Y-m-d H:i:s')
                    ], 400);
                }
                $nAkhir = Func::String2Number($data['Akhir']);
                $nNilaiBuku = Func::String2Number($data['NilaiBuku']);
                $cKeterangan = "AKM PENYUSUTAN " . $dTgl . " " . $data['Keterangan'];
                $cRekeningAkuntansi = "";
                $cRekeningBiaya = "";
                $vaData = DB::table('golonganaktiva')
                    ->select(
                        'RekeningDebet',
                        'RekeningKredit'
                    )
                    ->where('Kode', '=', $cGolongan)
                    ->orderBy('Kode')
                    ->first();

                if ($vaData) {
                    $cRekeningAkuntansi = $vaData->RekeningDebet;
                    $cRekeningBiaya = $vaData->RekeningKredit;
                }
                if ($nPenyusutan > 0) {
                    $cFaktur = GetterSetter::getLastFaktur('JR', 6);
                    $nSaldoNeraca = 0;
                    $nSaldoNeraca = GetterSetter::getSaldoAwalTnpGab($dTglTransaksi, $cRekeningAkuntansi, '', false);
                    $nDebet = 0;
                    $nKredit = 0;
                    if ($nSaldoNeraca < 0) {
                        $nDebet = abs($nSaldoNeraca);
                    } else {
                        $nKredit = abs($nSaldoNeraca);
                    }

                    $nDebet = 0;
                    $nKredit = 0;
                    $nSelisih = $nAkhir + $nSaldoNeraca;
                    if ($cGolongan == "BDD") {
                        $nSelisih = $nSaldoNeraca - $nNilaiBuku;
                    }
                    if ($nSelisih < 0) {
                        $nKredit = abs($nSelisih);
                    } else {
                        $nDebet = abs($nSelisih);
                    }
                    Upd::updJurnal($cFaktur, $dTgl, $cRekeningBiaya, $cKeterangan, $nPenyusutan, 0);
                    Upd::updJurnal($cFaktur, $dTgl, $cRekeningAkuntansi, $cKeterangan, 0, $nPenyusutan);
                }
                GetterSetter::setLastKodeRegister('JR');
            }
            // JIKA REQUEST SUKSES
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
}
