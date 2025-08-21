<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;

class AuthService
{
    public function register(array $request)
    {
        $request['password'] = Hash::make($request['password']);
        $request['role'] = $request['role'] ?? 'client';

        $user = User::create($request);
        //$token = JWTAuth::fromUser($user);
        $token = auth('api')->login($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

    public function login(array $request)
    {

        if (!$token = auth('api')->attempt($request)) {
            throw new \Exception('Identifiants invalides');
        }

        $user = auth('api')->user();

        if (!$user->is_active) {
            auth('api')->logout();
            throw new \Exception('Compte désactivé');
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

    public function logout()
    {
        auth('api')->logout();
        return ['message' => 'Déconnexion réussie'];
    }

    public function refresh()
    {
        $token = auth('api')->refresh();

        return [
            'user' => auth('api')->user(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

    public function me()
    {
        return auth('api')->user();
    }
}
