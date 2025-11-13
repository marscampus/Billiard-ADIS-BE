<?php

namespace App\Http\Controllers\plugin;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Expired_tokens;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PluginController extends Controller
{
    public function getClientHadPluginResto(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => $validator->errors()->first(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 422);
        }

        try {
            // Ambil user berdasarkan id owner
            $user = User::where('users.id', "1")
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'User Tidak Ditemukan',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }

            $plugin = 'o197Bidy2gIS6GRJumtWubndgkC0xt15' == $request->token;

            if ($plugin) {
                // Buat token baru
                $token = $user->createToken($user->name);

                Expired_tokens::create([
                    'id_personal_tokens' => $token->accessToken->id,
                    'token' => $token->plainTextToken,
                    'expired_at' => now()->addHours(6),
                ]);

                return response()->json([
                    'status' => self::$status['SUKSES'],
                    'message' => 'Berhasil Mengambil Data',
                    'data' => ['token' => $token->plainTextToken],
                    'datetime' => date('Y-m-d H:i:s'),
                ], 200);
            } else {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'Kredensial Salah',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }

    public function checkUserHadPlugin(Request $request)
    {
        try {

            $user = DB::table('users')->where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => self::$status['GAGAL'],
                    'message' => 'User Tidak Ditemukan',
                    'datetime' => date('Y-m-d H:i:s'),
                ], 400);
            }
            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Mengambil Data',
                'data' => ['id_plugin' => 'ajdshvag72813kl421qo1', 'id_user' => $user->id],
                'datetime' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                'status' => self::$status['BAD_REQUEST'],
                'message' => 'Terjadi Kesalahan Di Sistem : ' . $th->getMessage(),
                'datetime' => date('Y-m-d H:i:s'),
            ], 500);
        }
    }
}
