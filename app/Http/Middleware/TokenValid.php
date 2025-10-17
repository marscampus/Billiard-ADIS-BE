<?php

namespace App\Http\Middleware;

use App\Models\Expired_tokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class TokenValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $status = [
            'SUKSES' => '00',
            'GAGAL' => '01',
            'PENDING' => '02',
            'NOT_FOUND' => '03',
            'BAD_REQUEST' => '99'
        ];

        $user = Auth::guard('sanctum')->user();
        $guard = 'sanctum';
        if (empty($user)) {
            return Response()->json([
                'status' => $status['GAGAL'],
                'message' => 'Unauthenticated',
                'datetime' => date('Y-m-d H:i:s'),

            ])->setStatusCode(401);
        }

        #get token
        $id_personal_token = $user->currentAccessToken()->id;
        #get exipred token
        $result = Expired_tokens::where('id_personal_tokens', $id_personal_token)->first();
        #autentikasi user expire atau belum jika expired token dihapus dari tabel personal_access_token
        if (now() > $result->expired_at) {
            $user->tokens()->where('id', $id_personal_token)->update([
                'expires_at' => $result->expired_at
            ]);
            return Response()->json([
                'status' => $status['GAGAL'],
                'message' => 'Unauthenticated',
                'datetime' => date('Y-m-d H:i:s'),
            ])->setStatusCode(401);
        }

        #jika token masih bisa digunakan
        $waktu1 = Carbon::parse(now());
        $waktu2 = Carbon::parse($result->expired_at);

        #menghitung selisih waktu jika expired tinggal 60 menit kebawah maka waktu expire nya akan ditambahkan
        $selisih = $waktu1->diffInMinutes($waktu2);
        if ($selisih >= 0 && $selisih <= 60) {
            Expired_tokens::where('id_personal_tokens', $id_personal_token)->update(['expired_at' => now()->addHours(5)]);
        }

        $request->merge(['auth' => $user]);

        return $next($request);
    }
}
