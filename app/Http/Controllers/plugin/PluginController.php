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
            'id_owner' => 'required|exists:users,id',
            'id_plugin' => 'required|exists:plugins_master,id_plugin',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try {
            // Ambil user berdasarkan id owner
            $user = User::where('users.id', $request->id_owner)
                ->join('database_users as du', 'du.id_users', 'users.id')
                ->select('users.*')
                ->first();

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            // Ambil plugin yang valid berdasarkan id owner dan id plugin yang diberikan
            $plugin = DB::table('plugins_master as pm')
                ->leftJoin('invoices as in', 'in.apps_id', '=', 'pm.id_plugin')
                ->leftJoin('plugins as p', 'p.id', '=', 'pm.id_plugin')
                ->where('in.users_id', $user->id)
                ->where('pm.id_user', $user->id)
                ->where('pm.id_plugin', $request->id_plugin)
                ->where('p.status', '!=', 'nonactive')
                ->whereNotIn('pm.status_plug', ['ban', 'uninstalled'])
                ->distinct()
                ->get();

            if ($plugin->isNotEmpty()) {
                // Buat token baru
                $token = $user->createToken($user->name);

                Expired_tokens::create([
                    'id_personal_tokens' => $token->accessToken->id,
                    'token' => $token->plainTextToken,
                    'expired_at' => now()->addHours(6),
                ]);

                return response()->json(['token' => $token->plainTextToken]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'No valid plugin found'], 404);
            }
        } catch (Exception $er) {
            return response()->json(['error' => $er->getMessage()]);
        }
    }

    function checkUserHadPlugin(Request $request)
    {
        try {

            $user = DB::table('users')->where('email', $request->email)->first();


            $plugin = DB::table('plugins_master as pm')
                ->leftJoin('invoices as in', 'in.apps_id', 'pm.id_plugin')
                ->leftJoin('plugins as p', 'p.id', 'pm.id_plugin')
                ->where('in.users_id', $user->id)
                ->where('pm.id_user', $user->id)
                ->where('p.status', '!=', 'nonactive')
                ->whereNotIn('pm.status_plug', ['ban', 'uninstalled']) // Status plugin harus valid
                ->select('p.id', 'p.nama', 'pm.id_user')
                ->distinct()
                ->get();

            return response()->json($plugin);
        } catch (Exception $er) {
            return response()->json(['error' => $er]);
        }
    }
}
