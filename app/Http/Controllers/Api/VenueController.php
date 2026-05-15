<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;


/**
 * @OA\Tag(name="Площадки", description="Управление площадками")
 */
class VenueController extends Controller
{
    /**
     * @OA\Get(
     *     path="/venues",
     *     summary="Список площадок",
     *     tags={"Площадки"},
     *     @OA\Response(response=200, description="Список площадок с пагинацией")
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json(Venue::paginate(15));
    }

    /**
     * @OA\Post(
     *     path="/venues",
     *     summary="Создать площадку (только админ)",
     *     tags={"Площадки"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","address","capacity"},
     *             @OA\Property(property="name", type="string", example="Новая студия"),
     *             @OA\Property(property="address", type="string", example="ул. Пушкина, д. 10"),
     *             @OA\Property(property="capacity", type="integer", example=50),
     *             @OA\Property(property="hourly_fee", type="number", format="float", example=150.00)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Площадка создана"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        $this->authorize('create', Venue::class);
        $venue = Venue::create($request->validated());
        return response()->json(['message' => 'Площадка успешно создана.', 'data' => $venue], 201);
    }

    /**
     * @OA\Get(
     *     path="/venues/{id}",
     *     summary="Просмотр площадки",
     *     tags={"Площадки"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные площадки"),
     *     @OA\Response(response=404, description="Не найдено")
     * )
     */
    public function show(Venue $venue): JsonResponse
    {
        return response()->json(['data' => $venue]);
    }

    /**
     * @OA\Patch(
     *     path="/venues/{id}",
     *     summary="Обновить площадку (только админ)",
     *     tags={"Площадки"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string", example="Обновлённая студия"),
     *         @OA\Property(property="capacity", type="integer", example=30)
     *     )),
     *     @OA\Response(response=200, description="Обновлено"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function update(UpdateVenueRequest $request, Venue $venue): JsonResponse
    {
        $this->authorize('update', $venue);
        $venue->update($request->validated());
        return response()->json(['message' => 'Площадка успешно обновлена.', 'data' => $venue]);
    }

    /**
     * @OA\Delete(
     *     path="/venues/{id}",
     *     summary="Удалить площадку (только админ)",
     *     tags={"Площадки"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Удалено"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function destroy(Venue $venue): JsonResponse
    {
        $this->authorize('delete', $venue);
        $venue->delete();
        return response()->json(['message' => 'Площадка успешно удалена.']);
    }
}
