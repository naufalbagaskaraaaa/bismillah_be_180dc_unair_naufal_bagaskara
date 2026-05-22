<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'registasi berhasil',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'created_at' => $user->created_at
                ],
                'token' => $token
            ]
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'invalid token',
                'data' => null,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'login berhasil',
            'data' => [
                'token' => $token
            ]
        ], 200);
    }
}
