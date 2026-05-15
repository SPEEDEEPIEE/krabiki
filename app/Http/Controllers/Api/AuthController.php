<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;


/**
 * @OA\Tag(name="Аутентификация", description="Регистрация, вход, выход")
 */

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Регистрация нового пользователя",
     *     tags={"Аутентификация"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Иван Петров"),
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"participant", "organizer"}, example="participant")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Успешная регистрация"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Вход в систему",
     *     tags={"Аутентификация"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="organizer@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный вход"),
     *     @OA\Response(response=401, description="Неверные учётные данные")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Неверный email или пароль.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Вход выполнен успешно. Добро пожаловать, ' . $user->name . '!',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Выход из системы",
     *     tags={"Аутентификация"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Успешный выход"),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Вы успешно вышли из системы.']);
    }

    /**
     * @OA\Get(
     *     path="/me",
     *     summary="Профиль текущего пользователя",
     *     tags={"Аутентификация"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Данные профиля"),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Данные вашего профиля.',
            'data' => $request->user()->load('organizer'),
        ]);
    }
}
