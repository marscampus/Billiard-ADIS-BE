<?php

namespace App\Http\Controllers\api\auth;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Database_users;
use App\Models\Expired_tokens;
use App\Models\Karyawan;
use App\Models\Otp;
use App\Models\Menu_role;
use App\Models\Role;
use App\Models\User;
use App\Models\User_roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // dd(Hash::check('superadmin321', '$2y$10$5eUJKD92E9.iQfYA8MkHwOS/2ghA59DOQcyGKeSpUiSfzY4QEWZoG'));
        $user = User::where('email', $request->email)->first();

        // dd(Hash::check($request->password, $user->password));

        if (!$user) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'User Tidak Ditemukan',
                'datetime' => date('Y-m-d H:i:s'),
            ], 400);
        }


        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'Password Salah',
                'datetime' => date('Y-m-d H:i:s'),
            ], 400);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => self::$status['GAGAL'],
                'message' => 'User Tidak Aktif',
                'datetime' => date('Y-m-d H:i:s'),
            ], 400);
        }

        if ($user->role == 'superadmin') {
            $menuBase = self::$menuBase;

            $token = $user->createToken($user->name);

            $expired_tokens = Expired_tokens::create([
                'id_personal_tokens' => $token->accessToken->id,
                'token' => $token->plainTextToken,
                'expired_at' => now()->addHours(6)
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Login',
                'datetime' => date('Y-m-d H:i:s'),
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token->plainTextToken,
                'menu' => $menuBase,
            ], 200);
        } else if ($user->role == 'admin') {
            $menuBase = self::$menuBase;

            /* $menuArray = array_filter($menuBase, function ($menu) { */
            /*     return $menu['label'] !== 'User' && $menu['label'] !== 'Konfigurasi'; */
            /* }); */

            $menu = array_values($menuBase);

            $token = $user->createToken($user->name);

            $expired_tokens = Expired_tokens::create([
                'id_personal_tokens' => $token->accessToken->id,
                'token' => $token->plainTextToken,
                'expired_at' => now()->addHours(6)
            ]);

            return response()->json([
                'status' => self::$status['SUKSES'],
                'message' => 'Berhasil Login',
                'datetime' => date('Y-m-d H:i:s'),
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token->plainTextToken,
                'menu' => $menu,
            ], 200);
        }

        return response()->json([
            'status' => self::$status['GAGAL'],
            'message' => 'Gagal Login Karena role tidak ditemukan',
            'datetime' => date('Y-m-d H:i:s'),
        ], 400);
    }
}
