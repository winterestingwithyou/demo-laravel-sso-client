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
            if ($request->error === 'access_denied') {
                return redirect()->route('login')->withErrors(['error' => 'Otentikasi dibatalkan. Anda harus menyetujui permintaan izin akses untuk dapat masuk ke aplikasi ini.']);
            }
            return redirect()->route('login')->withErrors(['error' => 'Terjadi kesalahan SSO: ' . $request->error . ' (' . $request->error_description . ')']);
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
        $refreshToken = $tokenData['refresh_token'] ?? null;

        // Ambil profil lengkap dari endpoint Profile
        $profileResponse = Http::withToken($accessToken)->get(env('SSO_PROFILE_URL'));
        
        if ($profileResponse->failed()) {
            return redirect()->route('login')->withErrors(['error' => 'Gagal mengambil profil pengguna dari SSO: ' . $profileResponse->body()]);
        }

        $profileData = $profileResponse->json()['data'] ?? null;
        if (!$profileData) {
            return redirect()->route('login')->withErrors(['error' => 'Data profil SSO kosong atau tidak valid.']);
        }

        // Filter identitas yang aktif (tidak null)
        $rawIdentities = $profileData['identities'] ?? [];
        $activeIdentities = [];
        foreach ($rawIdentities as $key => $value) {
            if ($value !== null) {
                $activeIdentities[] = [
                    'type' => strtoupper($key),
                    'data' => $value
                ];
            }
        }

        if (count($activeIdentities) > 1) {
            // Jika identity > 1, simpan ke sesi sementara dan redirect ke select-identity
            Session::put('sso_temp_user', $profileData);
            Session::put('sso_temp_identities', $activeIdentities);
            Session::put('sso_temp_access_token', $accessToken);
            Session::put('sso_temp_refresh_token', $refreshToken);
            
            return redirect()->route('sso.select_identity_view');
        }

        $authUserId = $profileData['authUserId'] ?? ($profileData['id'] ?? null);
        $selectedIdentity = count($activeIdentities) === 1 ? $activeIdentities[0]['type'] : null;

        $existing = DB::table('auth_sessions')->where('auth_user_id', $authUserId)->first();
        $authSessionId = $existing ? $existing->id : (string) Str::uuid();

        DB::table('auth_sessions')->updateOrInsert(
            ['auth_user_id' => $authUserId],
            [
                'id' => $authSessionId,
                'email' => $profileData['emails'][0]['email'] ?? ($profileData['email'] ?? ''),
                'name' => $profileData['fullName'] ?? ($profileData['name'] ?? null),
                'active_identity' => $selectedIdentity,
                'roles' => json_encode($profileData['roles'] ?? []),
                'permissions' => json_encode($profileData['permissions'] ?? []),
                'identities_cache' => json_encode($rawIdentities),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'profilemetadata' => json_encode($profileData),
                'updated_at' => now(),
                'created_at' => $existing ? $existing->created_at : now(),
            ]
        );

        Session::put('auth_user_id', $authUserId);

        return redirect()->route('dashboard');
    }

    public function selectIdentityView()
    {
        if (!Session::has('sso_temp_identities')) {
            return redirect()->route('login');
        }
        
        $identities = Session::get('sso_temp_identities');
        
        return view('auth.select-identity', compact('identities'));
    }

    public function selectIdentitySubmit(Request $request)
    {
        $request->validate(['identity' => 'required']);
        
        if (!Session::has('sso_temp_user')) {
            return redirect()->route('login');
        }

        $profileData = Session::get('sso_temp_user');
        $accessToken = Session::get('sso_temp_access_token');
        $refreshToken = Session::get('sso_temp_refresh_token');
        $authUserId = $profileData['authUserId'] ?? ($profileData['id'] ?? null);
        
        $existing = DB::table('auth_sessions')->where('auth_user_id', $authUserId)->first();
        $authSessionId = $existing ? $existing->id : (string) Str::uuid();

        DB::table('auth_sessions')->updateOrInsert(
            ['auth_user_id' => $authUserId],
            [
                'id' => $authSessionId,
                'email' => $profileData['emails'][0]['email'] ?? ($profileData['email'] ?? ''),
                'name' => $profileData['fullName'] ?? ($profileData['name'] ?? null),
                'active_identity' => $request->identity,
                'roles' => json_encode($profileData['roles'] ?? []),
                'permissions' => json_encode($profileData['permissions'] ?? []),
                'identities_cache' => json_encode($profileData['identities'] ?? []),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'profilemetadata' => json_encode($profileData),
                'updated_at' => now(),
                'created_at' => $existing ? $existing->created_at : now(),
            ]
        );

        Session::forget(['sso_temp_user', 'sso_temp_identities', 'sso_temp_access_token', 'sso_temp_refresh_token']);
        Session::put('auth_user_id', $authUserId);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $authUserId = Session::get('auth_user_id');
        if ($authUserId) {
            $session = DB::table('auth_sessions')->where('auth_user_id', $authUserId)->first();
            
            if ($session && $session->access_token) {
                // Revoke token di sisi SSO
                Http::asForm()->post(env('SSO_BASE_URL') . '/oauth/revoke', [
                    'token' => $session->access_token,
                    'client_id' => env('SSO_CLIENT_ID'),
                    'client_secret' => env('SSO_CLIENT_SECRET'),
                ]);
            }
            
            DB::table('auth_sessions')->where('auth_user_id', $authUserId)->delete();
        }
        
        Session::flush();
        return redirect()->route('login');
    }

    public function me(Request $request)
    {
        $authUserId = Session::get('auth_user_id');
        if (!$authUserId) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $session = DB::table('auth_sessions')->where('auth_user_id', $authUserId)->first();
        if (!$session) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id' => $session->auth_user_id,
            'email' => $session->email,
            'name' => $session->name,
            'active_identity' => $session->active_identity,
            'roles' => json_decode($session->roles),
            'permissions' => json_decode($session->permissions),
            'identities' => json_decode($session->identities_cache),
            'profile' => json_decode($session->profilemetadata),
        ]);
    }
}
