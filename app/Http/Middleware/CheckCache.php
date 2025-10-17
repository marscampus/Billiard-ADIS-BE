<?php

namespace App\Http\Middleware;

use App\Models\Database_users;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class CheckCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $data = ['database'=>'ic3r4q_aa','username'=>'ic3r4q_aa','password'=>'LK0dIUXJ'];
        // Cache::put('tes', $data, now()->addMinutes(5));
        
        // $user = Auth::user();
        // if(!Cache::get($user->id)){
        //     $detail_user = Database_users::where('id_users',$user->id)->first();
        //     $data = ['database'=>$detail_user->nama,'username'=>$detail_user->username,'password'=>$detail_user->password];
        //     Cache::put($user->id,$data,36000);
        // }
        
        return $next($request);
    }
}
