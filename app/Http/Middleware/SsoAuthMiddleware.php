<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SsoAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!\Illuminate\Support\Facades\Session::has('auth_user_id')) {
            return redirect()->route('login');
        }

        $authUserId = \Illuminate\Support\Facades\Session::get('auth_user_id');
        $ssoUser = \Illuminate\Support\Facades\DB::table('auth_sessions')->where('auth_user_id', $authUserId)->first();

        if (!$ssoUser) {
            \Illuminate\Support\Facades\Session::flush();
            return redirect()->route('login');
        }

        // Parse JSON fields
        $ssoUser->roles = json_decode($ssoUser->roles, true) ?? [];
        $ssoUser->permissions = json_decode($ssoUser->permissions, true) ?? [];
        $ssoUser->profilemetadata = json_decode($ssoUser->profilemetadata, true) ?? [];

        $request->ssoUser = $ssoUser;

        return $next($request);
    }
}
