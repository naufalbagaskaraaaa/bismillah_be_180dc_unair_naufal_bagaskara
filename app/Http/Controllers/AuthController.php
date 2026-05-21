<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);

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
}
