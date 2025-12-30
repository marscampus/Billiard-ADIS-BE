<?php

namespace App\Http\Controllers\api\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    public function data(Request $request)
    {
        try {
            // Get query parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $sortField = $request->input('sort_field', 'tgl');
            $sortOrder = $request->input('sort_order', 'desc');
            $searchField = $request->input('search_field', 'nama');
            $searchValue = $request->input('search_value', '');

            // Validate sort order
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

            // Start query
            $query = DB::connection('db2')
                ->table('registernasabah')
                ->select('kode', 'nama', 'telepon');

            // Apply search filter if search value exists
            if (!empty($searchValue) && !empty($searchField)) {
                $query->where($searchField, 'LIKE', '%' . $searchValue . '%');
            }

            // Apply sorting
            $validSortFields = ['kode', 'nama', 'telepon', 'tgl'];
            if (in_array($sortField, $validSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('tgl', 'desc');
            }

            // Get total count before pagination
            $total = $query->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $data = $query->skip($offset)->take($perPage)->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
                'data' => $data,
                'total' => $total,
                'page' => (int)$page,
                'per_page' => (int)$perPage,
                'last_page' => ceil($total / $perPage),
                'datetime' => date('Y-m-d H:i:s')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Saat Proses Data: ' . $th->getMessage(),
                'data' => [],
                'total' => 0,
                'datetime' => date('Y-m-d H:i:s')
            ], 400);
        }
    }
}
