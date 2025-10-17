<?php

namespace App\Helpers;

use App\Models\fun\Cabang;
use App\Models\fun\Config;
use App\Models\fun\NomorFaktur;
use App\Models\fun\StockHP;
use App\Models\fun\TglTransaksi;
use App\Models\fun\UrutFaktur;
use App\Models\fun\Username;
use App\Models\master\PerubahanHargaStock;
use App\Models\master\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetterSetter
{

    public static function getSaldoStock($cKode, $cGudang, $dTgl)
    {
        $nSaldoStock = 0;

        try {
            $whereGudang = "";
            if (!empty($cGudang)) {
                $whereGudang = " AND GUDANG = '" . $cGudang . "'";
            }

            $saldo = DB::table('kartustock')
                ->select(DB::raw('SUM(DEBET-KREDIT) as Saldo'))
                ->where('KODE', '=', $cKode)
                ->where('TGL', '<=', $dTgl)
                // ->whereRaw($whereGudang)
                ->first();

            if ($saldo) {
                $nSaldoStock = $saldo->Saldo;
            }
        } catch (\Exception $ex) {
            return $ex;
        }
        return $nSaldoStock;
    }

    public static function getLastHP($kode, $tglperubahan)
    {
        $stock = Stock::where('KODE', $kode)->first();
        // dd($stock->HJ);
        $lastHP = StockHP::where('Kode', $kode)
            ->where('Tgl', '<=', $tglperubahan) // Hanya data sebelum atau sama dengan tglPerubahan yang diberikan
            ->orderBy('Tgl', 'desc') // Urutkan berdasarkan tanggal perubahan secara descending (terbaru)
            ->first();
        $HP = $lastHP ? $lastHP->HP : $stock->HJ;

        return $HP;
    }

    public static function getHargaBeli($kode)
    {
        $countData = PerubahanHargaStock::where('KODE', $kode)
            ->count();

        $retValHb = 0;
        if ($countData > 0) {
            $retValHb = PerubahanHargaStock::where('KODE', $kode)
                ->orderBy('ID', 'desc')
                ->limit(1)
                ->value('HB');
        } else {
            $retValHb = Stock::select('HB')
                ->where('KODE', $kode)
                ->value('HB');
        }

        return $retValHb;
    }

    public static function getHargaJual($kode)
    {
        $countData = PerubahanHargaStock::where('KODE', $kode)
            ->count();

        $retValHj = 0;
        if ($countData > 0) {
            $retValHj = PerubahanHargaStock::where('KODE', $kode)
                ->orderBy('ID', 'desc')
                ->limit(1)
                ->value('HJ');
        } else {
            $retValHj = Stock::select('HJ')
                ->where('KODE', $kode)
                ->value('HJ');
        }
        return $retValHj;
    }

    public static function getKe($dTglRealisasi, $dTgl, $nLama)
    {
        ini_set('max_execution_time', '0');
        $nTglRealisasi = Func::Tgl2Time($dTglRealisasi);
        $nTgl = Func::Tgl2Time($dTgl);
        $nKe = 0;
        $x = 0;

        while ($x <= $nTgl) {
            $nKe++;
            $x = Func::nextMonth($nTglRealisasi, $nKe);
        }

        $nKe--;
        return min(max($nKe, 0), $nLama);
    }

    public static function getLastKodeRegister($key, $len)
    {
        $valueReturn = '';
        $ID = 0;
        try {
            $query = DB::table('nomorfaktur')->where('KODE', str_replace(' ', '', $key))
                ->first();

            if ($query) {
                $ID = $query->ID;
                $ID++;
            } else {
                $value = str_replace(' ', '', $key);
                DB::table('nomorfaktur')->insert(['KODE' => $value]);
                $query = DB::table('nomorfaktur')->where('KODE', $value)->first();

                if ($query) {
                    $ID = $query->ID;
                    $ID++;
                }
            }

            $valueReturn = (string) $ID;
            $valueReturn = str_pad($valueReturn, $len, '0', STR_PAD_LEFT);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $valueReturn;
    }

    public static function setLastKodeRegisterStock($kode)
    {
        try {
            // $tahunBulan = str_replace("-", "", date("Ym"));
            $noFaktur = NomorFaktur::where('KODE', $kode)->first();
            if ($noFaktur) {
                $id = $noFaktur->ID;
                $id++;
                $noFaktur = NomorFaktur::where('KODE', $kode)->update([
                    'ID' => $id
                ]);
            } else {
                $id = 1;
                $noFaktur = NomorFaktur::create([
                    'KODE' => $kode,
                    'ID' => $id
                ]);
            }
        } catch (\Throwable $ex) {
            // dd($ex);
            throw $ex;
        }
        return response()->json(['status' => 'success']);
    }

    public static function setLastKodeRegister($kode)
    {
        try {
            $tahunBulan = str_replace("-", "", date("Ym"));
            $noFaktur = DB::table('nomorfaktur')->where('KODE', $kode . $tahunBulan)->first();
            if ($noFaktur) {
                $id = $noFaktur->ID;
                $id++;
                DB::table('nomorfaktur')->where('KODE', $kode . $tahunBulan)->update([
                    'ID' => $id
                ]);
            } else {
                $id = 1;
                $noFaktur = DB::table('nomorfaktur')->insert([
                    'KODE' => $kode,
                    'ID' => $id
                ]);
            }
        } catch (\Throwable $ex) {
            // dd($ex);
            throw $ex;
        }
        return response()->json(['status' => 'success']);
    }


    public static function getSatuanStock($KODE, $SATUAN)
    {
        try {
            $valueReturn = [];
            $cKey = '';
            $result = Stock::select('SATUAN', 'SATUAN2', 'SATUAN3', 'HB', 'HB2', 'HB3', 'HJ', 'HJ2', 'HJ3', 'ISI', 'ISI2')
                ->where('kode', $KODE)
                ->orWhere('kode_toko', $KODE)
                ->first();

            if ($result) {
                if ($SATUAN === $result->SATUAN || $SATUAN === $result->SATUAN2 || $SATUAN === $result->SATUAN3) {
                    if ($SATUAN === $result->SATUAN) {
                        $valueReturn['Satuan'] = 1;
                    } elseif ($SATUAN === $result->SATUAN2) {
                        $valueReturn['Satuan'] = 2;
                        $cKey = '2';
                    } elseif ($SATUAN === $result->SATUAN3) {
                        $valueReturn['Satuan'] = 3;
                        $cKey = '3';
                    }

                    $valueReturn['HB'] = $result->{'HB' . $cKey};
                    $valueReturn['HJ'] = $result->{'HJ' . $cKey};
                    $valueReturn['Isi'] = $result->Isi;
                    $valueReturn['Isi2'] = $result->Isi2;
                }
            }
            return $valueReturn;
        } catch (\Throwable $ex) {
            throw $ex;
        }
    }

    public static function getUrutFaktur(Request $request)
    {
        $cUser = Func::getEmail($request);
        $faktur = $request->Faktur;
        $valueReturn = [
            'USERNAME' => $cUser,
            'DATETIME' => Carbon::now(),
            'ID' => '',
        ];

        // Data Setelah 12 Bulan Bisa di Hapus biar tidak terlalu besar
        $dTglAwal = now()->subMonths(12)->format('Y-m-d');
        UrutFaktur::where('tgl', '=', $dTglAwal)->delete();

        try {
            $result = UrutFaktur::select('ID', 'UserName', 'DateTime')
                ->where('Faktur', $faktur)
                ->first();

            if ($result) {
                $valueReturn['USERNAME'] = $result->UserName;
                $valueReturn['DATETIME'] = $result->DateTime;
                $valueReturn['ID'] = strval($result->ID);
            } else {
                $value = [
                    'TGL' => now()->format('Y-m-d'),
                    'FAKTUR' => $faktur,
                    'DATETIME' => $valueReturn['DATETIME'],
                    'USERNAME' => $valueReturn['USERNAME'],
                ];

                UrutFaktur::insert($value);

                $result = UrutFaktur::selectRaw('IFNULL(MAX(ID), 1) as ID')
                    ->first();

                $valueReturn['ID'] = strval($result->ID);
            }
        } catch (\Exception $ex) {
            // Handle the exception
            throw $ex;
        }

        return $valueReturn;
    }

    public static function getKodeKamar($key, $len)
    {
        $valueReturn = '';
        $ID = 0;
        try {
            // Remove spaces from the key for consistency
            $key = str_replace(' ', '', $key);

            // Fetch the existing record from the database based on the key
            $query = DB::table('nomorfaktur')->where('KODE', $key)->first();

            if ($query) {
                $ID = $query->ID;
                $ID++;
            } else {
                // If no record found, insert the key into the table
                DB::table('nomorfaktur')->insert(['KODE' => $key]);
                // Fetch the inserted record to get the ID
                $query = DB::table('nomorfaktur')->where('KODE', $key)->first();
                if ($query) {
                    $ID = $query->ID;
                    $ID++;
                }
            }

            // Format the numeric part of the ID
            $numericID = str_pad($ID, $len - strlen($key), '0', STR_PAD_LEFT);
            // Combine the key with the padded numeric ID
            $valueReturn = $key . $numericID;
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $valueReturn;
    }


    public static function getLastFaktur($key, $len)
    {
        try {
            $instance = new self();
            $valueReturn = "";
            $tgl = str_replace("-", "", date("Ymd"));
            $tahunBulan = str_replace("-", "", date("Ym"));
            $valueReturn = $instance->getLastKodeRegister($key . $tahunBulan, $len);
            $key = str_replace(" ", "", $key) . $tgl;
            $valueReturn = $key . $valueReturn;
            return $valueReturn;
        } catch (\Throwable $ex) {
            dd($ex);
            throw $ex;
        }
    }

    public static function setLastFaktur($key)
    {
        try {
            $result = DB::table('nomorfaktur')->where('KODE', $key)->first();
            // dd($result);
            if ($result) {
                $id = $result->ID;
                $id++;

                DB::table('nomorfaktur')->where('KODE', $key)->update([
                    'ID' => $id
                ]);
            } else {
                $id = 1;
                $result = DB::table('nomorfaktur')->insert([
                    'KODE' => $key,
                    'ID' => $id
                ]);
            }
        } catch (\Exception $ex) {
            // dd($ex);
            throw $ex;
        }
        return response()->json(['status' => 'success']);
    }

    public static function setKodeKamar($key)
    {
        $valueReturn = '';
        $ID = 0;
        $len = 5; // Default length for the formatted code

        try {
            // Remove spaces from the key for consistency
            $key = str_replace(' ', '', $key);

            // Fetch the existing record from the database based on the key
            $query = DB::table('nomorfaktur')->where('KODE', $key)->first();

            if ($query) {
                // Increment the existing ID
                $ID = $query->ID + 1;

                // Update the record with the new ID
                DB::table('nomorfaktur')->where('KODE', $key)->update(['ID' => $ID]);
            } else {
                // If no record found, initialize ID and insert the key into the table
                $ID = 1;
                DB::table('nomorfaktur')->insert(['KODE' => $key, 'ID' => $ID]);
            }

            // Format the numeric part of the ID
            $numericID = str_pad($ID, $len - strlen($key), '0', STR_PAD_LEFT);

            // Combine the key with the padded numeric ID
            $valueReturn = $key . $numericID;
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $valueReturn;
    }

    public static function getKodeFaktur($key, $len)
    {
        $valueReturn = '';
        $ID = 0;
        try {
            $key = str_replace(' ', '', $key);
            $today = date('Ymd');
            $query = DB::table('nomorfaktur')->where('KODE', $key . $today)->first();
            if ($query) {
                $ID = $query->ID + 1;
            } else {
                $ID = 1;
            }
            $numericID = str_pad($ID, $len, '0', STR_PAD_LEFT);
            $valueReturn = $key . $today . $numericID;
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
        }
        return $valueReturn;
    }

    public static function setKodeFaktur($key)
    {
        $valueReturn = '';
        $ID = 0;

        try {
            $key = str_replace(' ', '', $key);
            $today = date('Ymd');
            $query = DB::table('nomorfaktur')->where('KODE', 'like', $key . $today . '%')->first();
            if ($query) {
                $ID = $query->ID + 1;
                DB::table('nomorfaktur')->where('KODE', $key . $today)->update(['ID' => $ID]);
            } else {
                $ID = 1;
                DB::table('nomorfaktur')->insert(['KODE' => $key . $today, 'ID' => $ID]);
            }
            $valueReturn = $key . $today;
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
        return $valueReturn;
    }

    public static function getDBConfig(...$key)
    {
        try {
            $result = '';

            $query = DB::table('config')->whereIn('kode', $key);
            if (count($key) > 1) {
                $query = $query->get();
                $result = $query;
            } else {
                $query = $query->first();
                $result = $query->keterangan;
            }
            return $result;
        } catch (\Throwable $ex) {
            throw $ex;
        }
    }

    public static function getRekeningCaraBayar($cCaraBayar)
    {
        $cRekening = '';
        $vaData = DB::table('pembayaran')
            ->select('rekening')
            ->where('kode', '=', $cCaraBayar)
            ->first();
        if ($vaData) {
            $cRekening = $vaData->rekening;
        }
        return $cRekening;
    }

    public static function getKeterangan($KODE, $FIELD, $TABLE)
    {
        $table = strtolower($TABLE);
        $keterangan = [];
        $query = DB::table($table)
            ->select($FIELD . ' as Keterangan')
            ->where('Kode', $KODE)
            ->first();

        return $query->Keterangan;
    }


    // public static function setDBConfig($KEY, $VALUE)
    // {
    //     try {
    //         $result = Config::where('Kode', $KEY)->first();
    //         if (!$result) {
    //             Config::insert(['Kode' => $KEY]);
    //         }
    //         $where = ['Kode' => $KEY];
    //         $data = ['Keterangan' => $VALUE];
    //         Config::where($where)->update($data);
    //     } catch (\Throwable $ex) {
    //         throw $ex;
    //     }
    //     return response()->json(['status' => 'success']);
    // }

    public static function getSaldoAwalLabarugi($dTgl, $cRekening, $cRekening2 = '', $lLike = true)
    {
        $dTgl = Func::Date2String($dTgl);

        if (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "7" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3") {
            $cSum = "b.kredit - b.debet";
        } else {
            $cSum = "b.debet - b.kredit";
        }

        if (!$lLike) {
            $like = "=";
            $like2 = "";
        } else {
            $like = "like";
            $like2 = "%";
        }

        if ($cRekening2 !== '') {
            $cLike = "b.rekening >= '" . $cRekening . "' and b.rekening <= '" . $cRekening2 . "'";
        } else {
            $cLike = "b.rekening " . $like . " '" . $cRekening . $like2 . "'";
        }

        $dbData = DB::table('bukubesar as b')
            // ->leftJoin('cabang as c', 'c.kode', '=', 'b.cabang')
            ->select(DB::raw("SUM($cSum) as Saldo"))
            ->where('b.tgl', '<=', $dTgl)
            ->whereRaw($cLike)
            ->first();

        $nSaldo = $dbData->Saldo ?? 0;

        // Menghilangkan bagian $lRekonsiliasi, $cCabang, $lPenihilan, dan $cJenisGabungan

        return $nSaldo;
    }

    public static function getSaldoAwalTnpGab($dTgl, $cRekening, $cRekening2 = '', $lLike = true)
    {
        $nSaldo = 0;
        $dTgl = Func::Date2String($dTgl);
        $nTahun = substr($dTgl, 0, 4);

        $cSum = (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3")
            ? DB::raw("IFNULL(SUM(b.kredit) - SUM(b.debet), 0) as Saldo")
            : DB::raw("IFNULL(SUM(b.debet) - SUM(b.kredit), 0) as Saldo");

        $like = $lLike ? 'LIKE' : '=';
        $like2 = $lLike ? '%' : '';

        $cLike = $cRekening2
            ? "b.Rekening BETWEEN '$cRekening' AND '$cRekening2'"
            : "b.Rekening $like '$cRekening$like2'";

        $queryBukuBesar = DB::table('bukubesar as b')
            ->where('b.tgl', '<=', $dTgl)
            ->whereRaw($cLike);

        $saldo = $queryBukuBesar->select($cSum)->first();

        $nSaldo = $saldo ? $saldo->Saldo : 0;

        return $nSaldo;
    }

    public static function getSaldoMutasi($dTglAwal, $dTglAkhir, $cRekening, $cRekening2 = '')
    {
        $dTglAwal = Func::Date2String($dTglAwal);
        $dTglAkhir = Func::Date2String($dTglAkhir);

        if (substr($cRekening, 0, 1) == "4" || substr($cRekening, 0, 1) == "7" || substr($cRekening, 0, 1) == "2" || substr($cRekening, 0, 1) == "3") {
            $cSum = "b.kredit - b.debet";
        } else {
            $cSum = "b.debet - b.kredit";
        }

        if ($cRekening2) {
            $cLike = "b.rekening >= '" . $cRekening . "' and b.rekening <= '" . $cRekening2 . "'";
        } else {
            $cLike = "b.rekening like '" . $cRekening . "%'";
        }

        $dbData = DB::table('bukubesar as b')
            ->select(columns: DB::raw("SUM($cSum) as Saldo"))
            ->whereBetween('b.tgl', [$dTglAwal, $dTglAkhir])
            ->whereRaw($cLike)
            ->first();

        $nSaldo = $dbData->Saldo ?? 0;

        return $nSaldo;
    }
}
