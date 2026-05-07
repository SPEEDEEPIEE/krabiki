<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Регистрация прошла успешно. Добро пожаловать!',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Неверный email или пароль. Попробуйте снова.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Вход выполнен успешно. Добро пожаловать, ' . $user->name . '!',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Вы успешно вышли из системы. До встречи!',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('organizer');
        return response()->json([
            'message' => 'Данные вашего профиля.',
            'data' => $user,
        ]);
    }
}