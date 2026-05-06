<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SsoAuthController extends Controller
{
    public function loginView(Request $request)
    {
        if (Session::has('auth_user_id')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $code_verifier = Str::random(128);
        $code_challenge = strtr(rtrim(base64_encode(hash('sha256', $code_verifier, true)), '='), '+/', '-_');

        $request->session()->put('state', $state);
        $request->session()->put('code_verifier', $code_verifier);

        $query = http_build_query([
            'client_id' => env('SSO_CLIENT_ID'),
            'redirect_uri' => env('SSO_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'OPENID PROFILE EMAIL',
            'state' => $state,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect(env('SSO_BASE_URL') . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors(['error' => 'SSO Redirect Error: ' . $request->error . ' (' . $request->error_description . ')']);
        }

        if (!$request->code) {
            return redirect()->route('login')->withErrors(['error' => 'Authorization code is missing from the SSO redirect.']);
        }

        $state = $request->session()->pull('state');
        $code_verifier = $request->session()->pull('code_verifier');

        if (!$state || $state !== $request->state) {
            return redirect()->route('login')->withErrors(['error' => 'Invalid state or session expired.']);
        }

        $response = Http::asForm()->post(env('SSO_BASE_URL') . '/oauth/token', [
            'grant_type' => 'AUTHORIZATION_CODE',
            'client_id' => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
            'redirect_uri' => env('SSO_REDIRECT_URI'),
            'code' => $request->code,
            'code_verifier' => $code_verifier,
        ]);

        if ($response->failed()) {
            return redirect()->route('login')->withErrors(['error' => 'Failed to obtain access token: ' . $response->body()]);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        $profileResponse = Http::withToken($accessToken)->get(env('SSO_BASE_URL') . '/api/auth/me');

        if ($profileResponse->failed()) {
            return redirect()->route('login')->withErrors(['error' => 'Failed to obtain user profile: ' . $profileResponse->body()]);
        }

        $userData = $profileResponse->json();
        $user = $userData['data'] ?? $userData;

        $existing = DB::table('auth_sessions')->where('auth_user_id', $user['id'])->first();
        $authSessionId = $existing ? $existing->id : (string) Str::uuid();

        DB::table('auth_sessions')->updateOrInsert(
            ['auth_user_id' => $user['id']],
            [
                'id' => $authSessionId,
                'email' => $user['email'],
                'name' => $user['name'] ?? null,
                'active_identity' => $user['active_identity'] ?? null,
                'roles' => json_encode($user['roles'] ?? []),
                'permissions' => json_encode($user['permissions'] ?? []),
                'access_token' => $accessToken,
                'profilemetadata' => json_encode($user), // Tambahan data profil agar tidak hit API terus
                'updated_at' => now(),
                'created_at' => $existing ? $existing->created_at : now(),
            ]
        );

        Session::put('auth_user_id', $user['id']);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $authUserId = Session::get('auth_user_id');
        if ($authUserId) {
            DB::table('auth_sessions')->where('auth_user_id', $authUserId)->delete();
        }
        
        Session::flush();
        return redirect()->route('login');
    }
}
