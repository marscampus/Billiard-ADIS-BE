<?php

namespace App\Helpers;

use App\Models\fun\Config;
use App\Models\fun\NomorFaktur;
use App\Models\fun\TglTransaksi;
use App\Models\fun\UrutFaktur;
use App\Models\Log;
use App\Models\master\PerubahanHargaStock;
use App\Models\master\Stock;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Func
{
    public static function dataAuth($request)
    {
        $vaRequestData = json_decode(json_encode($request->json()->all()), true);
        // dd($vaRequestData['auth']);
        $cUser = $vaRequestData['auth']['email'];
        unset($vaRequestData['auth']);
        // dd($cUser);
        return $cUser;
    }

    public static function Devide($A, $B)
    {
        $nRetval = 0;

        if (empty($A) || empty($B) || $A == 0 || $B == 0) {
            $nRetval = 0;
        } else {
            $nRetval = $A / $B;
        }

        return $nRetval;
    }


    public static function Date2String($dTgl)
    {
        $cRetval = substr($dTgl, 0, 10);
        $va = explode("-", $dTgl);
        // Jika Array 1 Bukan Tahun maka akan berisi 2 Digit
        if (strlen($va[0]) == 2) {
            $cRetval = $va[2] . "-" . $va[1] . "-" . $va[0];
        }
        return $cRetval;
    }

    public static function String2Date($dTgl)
    {
        $cRetval = substr($dTgl, 0, 10);
        $va = explode("-", $dTgl);

        // Jika Array 1 Bukan Tahun maka akan berisi 2 Digit
        if (strlen($va[0]) == 2) {
            $cRetval = $va[2] . "-" . $va[1] . "-" . $va[0];
        }

        $date = DateTime::createFromFormat('Y-m-d', $cRetval);
        if ($date) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    public static function String2Number($cString)
    {
        return str_replace(",", "", $cString);
    }

    public static function getZFormat($value)
    {
        $valueReturn = strval($value);
        $valueReturn = number_format(floatval($valueReturn), 2);
        return $valueReturn;
    }

    public static function getZFormatWithDecimal($value, $decimal)
    {
        $valueReturn = strval($value);
        $valueReturn = number_format(floatval($valueReturn), $decimal);
        return $valueReturn;
    }

    public static function formatDate($value)
    {
        return date('d-m-Y', strtotime($value));
    }
    public static function Tgl2Time($dTgl)
    {
        if (empty($dTgl)) {
            return 0;
        }

        $instance = new self();
        $dTgl = $instance->String2Date($dTgl);

        // Ubah format tanggal menjadi Y-m-d jika belum dalam format tersebut
        $va = explode("-", $dTgl);
        if (count($va) !== 3) {
            return 0; // Format tanggal tidak valid
        }

        // Pastikan nilai bulan dan hari berada dalam rentang yang valid
        $year = intval($va[0]);
        $month = intval($va[1]);
        $day = intval($va[2]);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return 0; // Nilai bulan atau hari tidak valid
        }

        // Gunakan Carbon untuk membuat waktu berdasarkan tanggal yang sudah dipecah
        $time = Carbon::create($year, $month, $day, 0, 0, 0);

        return $time->timestamp;
    }
    public static function Tgl2TimeLama($dTgl)
    {
        if (empty($dTgl)) {
            return 0;
        }
        $instance = new self();
        $dTgl = $instance->String2Date($dTgl); // Anda perlu mengimplementasikan fungsi String2Date terlebih dahulu
        $va = explode("-", $dTgl); // Gunakan explode() untuk memisahkan tanggal menjadi array

        // Gunakan Carbon untuk membuat waktu berdasarkan tanggal yang sudah dipecah
        $time = Carbon::create($va[2], $va[1], $va[0], 0, 0, 0);

        return $time->timestamp; // Kembalikan waktu dalam bentuk UNIX timestamp
    }

    public static function replaceKarakterKhusus($cString)
    {
        $cString = str_replace(":", "", $cString);
        $cString = str_replace("=", "", $cString);
        $cString = str_replace(";", "", $cString);
        $cString = str_replace("'", "", $cString);
        $cString = str_replace(",", " ", $cString);
        $cString = str_replace(".", " ", $cString);
        $cString = str_replace("+", "", $cString);
        $cString = str_replace("/", " ", $cString);
        $cString = str_replace("&", "dan", $cString);
        $cString = str_replace(PHP_EOL, " ", $cString); // Mengganti chr(10) dengan PHP_EOL

        // Gunakan preg_replace untuk mengganti multiple whitespace menjadi satu whitespace
        $cString = preg_replace('/\s+/', ' ', $cString);

        // Gunakan trim untuk menghapus whitespace di awal dan akhir string
        // $cString = trim(String2SQL($cString)); // Pastikan Anda telah mengimplementasikan String2SQL()
        $cString = trim($cString);

        return $cString;
    }

    public static function writeLog($controller, $func, $reqData, $retVal, $user)
    {
        $array = [
            "Controller" => $controller,
            "Function" => $func,
            "Tgl" => Carbon::now()->format('Y-m-d'),
            "Request" => json_encode($reqData, JSON_PRETTY_PRINT),
            "Response" => json_encode($retVal, JSON_PRETTY_PRINT),
            "User" => $user,
            "DateTime" => Carbon::now()
        ];
        Log::create($array);
    }

    public static function getUserName(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cEmail = $vaRequestData['auth']['email'];
            // dd($cEmail);
            // Log::info($cEmail);
            unset($vaRequestData['auth']);
            $vaData = DB::table('username')
                ->select(
                    'USERNAME'
                )
                ->where('USERNAME', '=', $cEmail)
                ->first();
            if ($vaData) {
                $cUserName = $vaData->USERNAME;
            }
            return $cUserName;
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
    }

    public static function getEmail(Request $request)
    {
        try {
            $vaRequestData = json_decode(json_encode($request->json()->all()), true);
            $cValueReturn = $vaRequestData['auth']['email'];
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error']);
        }
        return $cValueReturn;
    }

    // WAJIB ARRAY
    public static function filterArrayClean($vaData = [], $vaKey = [])
    {
        foreach ($vaKey as $val) {
            $vaData[$val] = htmlspecialchars(trim($vaData[$val]));
        }
        return $vaData;
    }

    public static function filterArrayValue($vaData = [], $vaKey = [])
    {
        foreach ($vaKey as $val) {
            if (!isset($vaData[$val]))
                return false;
        }
        return true;
    }

    public static function EOM($dTgl)
    {
        $day = self::Date2String($dTgl);
        $d = Carbon::create($day)->endOfMonth();

        return $d->format('d-m-Y');
    }

    public static function MundurSatuBulanDanAmbilEOM($dTgl)
    {
        $day = self::Date2String($dTgl);
        $d = Carbon::create($day)->subMonth()->endOfMonth();

        return $d->format('d-m-Y');
    }

    public static function BOM($dTgl)
    {
        $day = self::String2Date($dTgl);
        $dBulan = substr($day, 5, 2);
        $dTahun = substr($day, 0, 4);
        $d = date('d-m-Y', mktime(0, 0, 0, $dBulan, 1, $dTahun));
        return $d;
    }

    public static function GetDayAwal($nTime)
    {
        $nDay = date("d", $nTime);
        $nMonth = date("m", $nTime);
        $nYear = date("Y", $nTime);

        $n1 = mktime(0, 0, 0, $nMonth, $nDay - 1, $nYear);
        return $n1;
    }

    public static function GetMonth($nBulan)
    {
        $n = min(max(strval($nBulan) - 1, 0), 11);
        $vaMonth = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
        return $vaMonth[$n];
    }

    public static function RoundUp($nNumber, $nPembulatan)
    {
        if ($nPembulatan <> 0) {
            $nNumber = ceil($nNumber);
            $nSelisih = $nNumber % $nPembulatan;
            if ($nSelisih <> 0) {
                $nNumber += ($nPembulatan - $nSelisih);
            }
        }
        return $nNumber;
    }

    public static function modAktiva($nNumber)
    {
        $nRoundUp = 1;
        $nSelisih = $nNumber % $nRoundUp;
        if ($nSelisih <> 0) {
            $nNumber += ($nRoundUp - $nSelisih);
        }
        return $nNumber;
    }

    public static function nextMonth($nTime, $nNextMonth)
    {
        $nDay = date("d", $nTime);
        $nMonth = date("m", $nTime);
        $nYear = date("Y", $nTime);

        $n1 = mktime(0, 0, 0, $nMonth + $nNextMonth, $nDay, $nYear);
        $n2 = mktime(0, 0, 0, $nMonth + $nNextMonth + 1, 0, $nYear);
        return min($n1, $n2);
    }
}
