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

class LoginControllerold extends Controller
{
    function login(Request $request)
    {
        $user = null;
        if ($request->aslogin == 'user') {
            $user =  $this->_loginUser($request);
        } elseif ($request->aslogin == 'staff') {
            $user =  $this->_loginStaff($request);
        }
        if ($user == null) {
            return response()->json(['error' => 'something error'], 500);
        }

        return $user;
    }

    function _loginUser($request)
    {

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            //   return response()->json(['error'=>'email atau password anda salah']);
            return $this->_loginStaff($request);
        }

        if ($user->status == 'denied') {
            return response()->json(['error' => 'akses ditolak']);
        }

        if ($user->status != 'active') {
            return response()->json(['error' => 'otp']);
        }

        if ($user->role == 'master') {
            $menuBase = Menu_role::leftJoin('apps', 'menu_roles.id', '=', 'apps.id')
                ->where('apps.nama', 'like', '%Hotel%')
                ->where('priority', '=', 'admin')
                ->first();

            #jika menu tidak ada atau belum diisi error
            if (!$menuBase) {
                return  response()->json(['error' => 'akses ditolak']);
            }
            $user_role_master = User_roles::where("users_id", $user->id);
            $saveRoleMaster = $user_role_master->update([
                'menu' => $menuBase->menu
            ]);
            # jika menu tidak tersimpan maka error
            if (!$saveRoleMaster) {
                return response()->json(['error' => 'akses ditolak']);
            }
        }

        if ($user->role == 'client') {
            $databaseUser = Database_users::where('id_users', $user->id);
            if (!$databaseUser->exists()) {
                return  response()->json(['error' => "user tidak ditemukan"]);
            }
            $database = $databaseUser->first();
            if (now() > $database->used_until_date) {
                return  response()->json(['error' => "masa sewa aplikasi habis"]);
            }


            #serach menu_role fo client (owner)
            $menuBase = Menu_role::leftJoin('apps', 'apps.id', '=', 'menu_roles.nama_app')
                ->leftJoin('database_users', 'database_users.apps_id', '=', 'menu_roles.nama_app')
                ->where('apps.nama', 'like', '%' . env('APP_NAME') . '%')
                ->where('database_users.id_users', '=', $user->id)
                ->first();
            #jika menu tidak ada atau belum diisi error
            if (!$menuBase) {
                return  response()->json(['error' => 'E-mail belum terdaftar']);
            }

            #update role (owner)
            $plugin_route = DB::table('plugins_master as pm')
                ->join('invoices as in', 'in.apps_id', 'pm.id_plugin')
                ->join('plugin_routes as pr', 'pr.id_plugin', 'pm.id_plugin')
                ->join('plugins as p', 'p.id', 'pm.id_plugin')
                ->join('apps as ap', 'p.id_app', 'ap.id')
                ->where('in.users_id', $user->id)
                ->where('pm.id_user', $user->id)
                ->where('ap.nama', 'like', '%Hotel%')
                ->where('p.status', '!=', 'nonactive')
                ->whereNotIn('pm.status_plug', ['ban', 'uninstalled']) // Status plugin harus valid
                ->select('pr.id', 'pr.section', 'pr.menus')
                ->distinct()
                ->get();

            $updatedMenus = $this->updateMenuRole($menuBase->menu, $plugin_route);
            $userRoles = User_roles::updateOrCreate(
                [
                    'users_id' => $user->id,
                    'nama_app_id' => $menuBase->nama_app
                ],
                [
                    'menu' => $updatedMenus
                ]
            );
            # jika menu tidak tersimpan maka error
            if (!$userRoles) {
                return response()->json(['error' => 'akses ditolak']);
            }
        }

        $menuArray = $this->_searchRoleUser($user->id);
        if ($menuArray == 'error') {
            return response()->json(['error' => 'akses ditolak']);
        }

        #create token
        $token = $user->createToken($user->name);

        $expired_tokens = Expired_tokens::create([
            'id_personal_tokens' => $token->accessToken->id,
            'token' => $token->plainTextToken,
            'expired_at' => now()->addHours(6)
        ]);

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'token' => $token->plainTextToken,
            'menu' => $menuArray
        ]);
    }

    function _loginStaff($request)
    {
        $user = Karyawan::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'email atau password anda salah']);
        }
        if ($user->status == 'denied') {
            return response()->json(['error' => 'akses ditolak']);
        }

        $role = $user->ability;
        $string = str_replace("'", '"', $role);
        $tokenAbilities = json_decode($string);
        $token = $user->createToken($user->name, $tokenAbilities);

        $expired_tokens = Expired_tokens::create([
            'id_personal_tokens' => $token->accessToken->id,
            'token' => $token->plainTextToken,
            'expired_at' => now()->addHours(6)
        ]);

        $menu = User_roles::where('users_id', $user->id)
            ->leftJoin('apps', 'user_roles.nama_app_id', '=', 'apps.id')
            ->where('apps.nama', 'like', '%Hotel%')
            ->first();
        if (!$menu) {
            return response()->json(['error' => 'Karyawan belum diaktifkan']);
        }
        $jsonString = $menu->menu;

        $menuArray = json_decode($jsonString);
        return response()->json(['name' => $user->name, 'email' => $user->email, 'role' => $tokenAbilities, 'token' => $token->plainTextToken, 'menu' => $menuArray]);
    }

    function _searchRoleUser($id)
    {
        $menu = User_roles::where('users_id', $id)
            ->leftJoin('apps', 'user_roles.nama_app_id', '=', 'apps.id')
            ->where('apps.nama', 'like', '%Hotel%')
            ->first();

        if (!$menu) {
            return 'error';
        }
        $jsonString = $menu->menu;

        $menuArray = json_decode($jsonString);
        return $menuArray;
    }

    function updateMenuRole($menus, $pluginMenu)
    {
        $updatedMenus = $menus ? json_decode($menus, true) : [];
        $plugMenu = [];

        if (empty($pluginMenu) || $pluginMenu->count() < 1) {
            return $menus;
        }


        foreach ($pluginMenu as $menu) {
            $mergedMenus = array_merge($plugMenu, json_decode($menu->menus, true));

            // cek
            $pattern = $menu->section;
            $regex = '/^' . $pattern . '\\w*/';
            $regexFound = false;


            foreach ($updatedMenus as $key => $menu) {
                $isExistSection = preg_match($regex, strtolower($menu['label']));
                if ($isExistSection && $regex) {
                    $regexFound = true;

                    $existingLabels = array_column($menu['items'], 'label');
                    foreach ($mergedMenus as $item) {
                        if (!in_array($item['label'], $existingLabels)) {
                            array_splice($updatedMenus, $key + 1, 0, [$item]);
                        }
                    }
                    break;
                }
            }


            if (!$regexFound) {
                $updatedMenus[] = $mergedMenus[0];
            }
        }

        return $updatedMenus;
    }
}
