<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Expired_tokens;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    //

    function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'email atau password anda salah']);
        }

        $token = $user->createToken($user->name);

        $expired_tokens = Expired_tokens::create([
            'id_personal_tokens' => $token->accessToken->id,
            'token' => $token->plainTextToken,
            'expired_at' => now()->addHours(6)
        ]);
        return response()->json(['name' => $user->name, 'email' => $user->email, 'token' => $token->plainTextToken]);
        // return $token->plainTextToken;
    }
}
