<?php

namespace App\Http\Middleware;

use App\Models\Expired_tokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;


class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return Response()->json(['message'=>$request->rekening])->setStatusCode(200);;exit;
        //menangkap Bearer token
        $user = Auth::user();

        $id_personal_token = $user->currentAccessToken()->id;
        $result = Expired_tokens::where('id_personal_tokens', $id_personal_token)->first();

        //autentikasi user expire atau belum jika expired token dihapus dari tabel personal_access_token
        if (now() > $result->expired_at) {
            $user->tokens()->where('id', $id_personal_token)->update([
                'expires_at' => $result->expired_at
            ]);
            return Response()->json(['message' => 'Unauthenticated'])->setStatusCode(401);
        }

        //jika token masih bisa digunakan
        $waktu1 = Carbon::parse(now());
        $waktu2 = Carbon::parse($result->expired_at);

        //menghitung selisih waktu jika expired tinggal 60 menit kebawah maka waktu expire nya akan ditambahkan
        $selisih = $waktu1->diffInMinutes($waktu2);
        if ($selisih >= 0 && $selisih <= 60) {
            Expired_tokens::where('id_personal_tokens', $id_personal_token)->update(['expired_at' => now()->addHours(5)]);
        }

        return $next($request);
    }
}
