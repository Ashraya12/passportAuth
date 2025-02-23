<?php

namespace App\Http\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;

class AuthService
{

    public function register($data)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'role_id' => $data['role_id']
            ]);

            $user->load('role');

            $token = $user->createToken('auth_token', [$user->role->name])->accessToken;

            DB::commit();

            return [
                'message' => 'User registered successfully',
                'token' => $token
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        $user = Auth::user();

        $http = new GuzzleClient();

        $response = $http->post(('http://127.0.0.1:8000/oauth/token'), [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID'),
                'client_secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET'),
                'username' => $credentials['email'],
                'password' => $credentials['password'],
                'scope' => $user->role->name,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    public function refresh_token($refresh_token)
    {
        $http = new GuzzleClient();

        $response = $http->post(('http://127.0.0.1:8000/oauth/token'), [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID'),
                'client_secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET'),
                'refresh_token' => $refresh_token,
                'scope' => '',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
