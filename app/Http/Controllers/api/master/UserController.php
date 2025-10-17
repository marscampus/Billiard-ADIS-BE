<?php

namespace App\Http\Controllers\api\master;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function data(Request $request)
    {
        try {
            $vaData = DB::table('users')
                ->select('id', 'no_hp', 'name', 'email', 'status', 'role')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'SUKSES',
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

    public function store(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'email' => 'required|unique:users,email',
                'no_hp' => 'required|unique:users,no_hp',
                'name' => 'required|max:100',
                'password' => 'required|min:8'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);
            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $superadminExists = User::when(
                $request->role === 'superadmin',
                function ($query) {
                    return $query->where('role', 'superadmin');
                }
            )->exists();

            if (!$superadminExists) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Superadmin Maksimal 1 Akun',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            $vaData = User::create([
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'status' => $request->status,
                'role' => $request->role,
            ]);


            if (!$vaData) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Create Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

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

    public function update(Request $request)
    {
        try {
            $vaValidator = Validator::make($request->all(), [
                'email' => [
                    'required',
                    Rule::unique('users', 'email')->ignore($request->id), // Abaikan ID saat ini
                ],
                'no_hp' => [
                    'required',
                    Rule::unique('users', 'no_hp')->ignore($request->id), // Abaikan ID saat ini
                ],
                'name' => 'required|max:100',
                'password' => 'min:8'
            ], [
                'required' => 'Kolom :attribute harus diisi.',
                'max' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
                'min' => 'Kolom :attribute tidak boleh kurang dari :min karakter.',
                'unique' => 'Kode sudah ada di database.'
            ]);

            if ($vaValidator->fails()) {
                return response()->json([
                    'status' => self::$status['BAD_REQUEST'],
                    'message' => $vaValidator->errors()->first(),
                    'datetime' => date('Y-m-d H:i:s')
                ], 422);
            }

            $superadminExists = User::when(
                $request->role === 'superadmin',
                function ($query) {
                    return $query->where('role', 'superadmin');
                }
            )->exists();

            if (!$superadminExists) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Superadmin Maksimal 1 Akun',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            $vaBody = [
                'name' => $request->name,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'status' => $request->status,
                'role' => $request->role,
            ];

            if ($request->password) {
                $vaBody['password'] = Hash::make($request->password);
            }

            $vaData = User::where('id', $request->id)
                ->update($vaBody);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Update Data',
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
            $vaData = DB::table('users')->where('id', $request->id)->delete();

            if ($vaData === 0) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Gagal Hapus Data',
                    'datetime' => date('Y-m-d H:i:s')
                ], 400);
            }

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Hapus Data',
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
}
