<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id'
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id
            ]);

            $user->load('role');

            $token = $user->createToken('auth_token', [$user->role->name])->accessToken;

            DB::commit();

            return response()->json(['message' => 'User registered successfully', 'token' => $token], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to register',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        $user = Auth::user();

        $http = new GuzzleClient();
        try {
            $response = $http->post(('http://127.0.0.1:8000/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID'),
                    'client_secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET'),
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => $user->role->name,
                ],
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'OAuth server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user()->token();
            $user->revoke();
            return response()->json(["message" => "Successfully Logged Out"]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required',
        ]);

        try {
            $http = new GuzzleClient();

            $response = $http->post(('http://127.0.0.1:8000/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID'),
                    'client_secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET'),
                    'refresh_token' => $request->refresh_token,
                    'scope' => '',
                ],
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'OAuth server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
