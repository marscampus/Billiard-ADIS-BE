<?php

namespace App\Http\Middleware;

use App\Models\Expired_tokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class H2HTokenValid
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

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status'  => $status['BAD_REQUEST'],
                'message' => 'Authorization Bearer token tidak ditemukan',
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 401);
        }

        if ($token !== env('H2HTOKEN')) {
            return response()->json([
                'status'  => $status['BAD_REQUEST'],
                'message' => 'Unauthorize',
                'datetime' => now()->format('Y-m-d H:i:s')
            ], 401);
        }

        return $next($request);
    }
}
