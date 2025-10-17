<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\Database_users;

class ChangeDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $id = Auth::user()->id;
        // $cache = Cache::get($id);

        // Config::set("database.connections.mysql", [
        //     'driver' => 'mysql',
        //     "host" => env('DB_HOST'),
        //     "database" => $cache['database'],
        //     "username" => $cache['username'],
        //     "password" => $cache['password']
        // ]);
        $id = $request->auth->users_id ?? $request->auth->id;
        $database = Database_users::where('id_users', $id)
            ->where('apps_id', '9dd849f3-0d94-4170-86a6-eccabcaa7b68')
            ->first();

        Config::set("database.connections.mysql", [
            'driver' => 'mysql',
            "host" => '192.168.31.131',
            "database" => $database->nama,
            "username" => '49157',
            "password" => '49157-M@rsDB**70',
            "port" => '39157'
        ]);

        DB::purge('mysql');

        return $next($request);
    }
}


