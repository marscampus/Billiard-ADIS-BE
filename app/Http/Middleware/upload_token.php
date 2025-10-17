<?php

namespace App\Http\Middleware;

use Closure;
use Error;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class upload_token
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Config::set("database.connections.mysql", [
        //     'driver' => 'mysql',
        //     "host" => '192.168.31.117',
        //     "database" => "u6l2mo_aradhea",
        //     "username" => '49157',
        //     "password" => '49157-M@rsDB**70',
        //     "port" => '39157'
        // ]);
        // // dd('test');
        // DB::purge('mysql');

        // return $next($request);
        try {
            $value = explode('/', $request->bearerToken());
            if (count($value) < 2) {
                throw new Error('Unauthenticated');
            }
            $iv = hex2bin($value[1]);
            $key = openssl_pbkdf2('say_lalisa_love_me_lalisa_love_me_hey', $iv, 32, 100000, 'sha512');
            $decrypted = openssl_decrypt(hex2bin($value[0]), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            // dd($decrypted);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'error'], 500);
        }
        $request->headers->set('Authorization', "Bearer " . $decrypted);
        return $next($request);
    }
}
