<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FuncController extends Controller
{
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
            if (!isset($vaData[$val])) return false;
        }
        return true;
    }
}
