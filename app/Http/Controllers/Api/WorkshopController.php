<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkshopRequest;
use App\Models\Organizer;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Мастер-классы", description="Управление мастер-классами")
 */
class WorkshopController extends Controller
{
    /**
     * @OA\Get(
     *     path="/workshops",
     *     summary="Список мастер-классов с фильтрами",
     *     tags={"Мастер-классы"},
     *     @OA\Parameter(name="organizer_id", in="query", @OA\Schema(type="integer"), description="Фильтр по организатору"),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date"), description="С даты"),
     *     @OA\Parameter(name="has_available_spots", in="query", @OA\Schema(type="boolean"), description="Только с местами"),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="Сортировка (-starts_at, price, title)"),
     *     @OA\Response(response=200, description="Список с пагинацией")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Workshop::with(['organizer', 'venue']);

        if ($request->filled('organizer_id')) {
            $query->where('organizer_id', $request->organizer_id);
        }
        if ($request->filled('date_from')) {
            $query->where('starts_at', '>=', $request->date_from);
        }
        if ($request->boolean('has_available_spots')) {
            $query->withCount(['registrations as approved_count' => function ($q) {
                $q->where('status', 'approved');
            }])->whereRaw('max_participants > approved_count');
        }

        $sort = $request->get('sort', '-starts_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sort = ltrim($sort, '-');
        $allowedSorts = ['starts_at', 'ends_at', 'title', 'price', 'created_at'];
        $query->orderBy(in_array($sort, $allowedSorts) ? $sort : 'starts_at', $direction);

        $workshops = $query->paginate(min((int) $request->get('per_page', 15), 100));
        $workshops->getCollection()->transform(fn($w) => tap($w, fn($x) => $x->available_spots = $x->availableSpots()));

        return response()->json($workshops);
    }

    /**
     * @OA\Post(
     *     path="/workshops",
     *     summary="Создать мастер-класс (организатор/админ)",
     *     tags={"Мастер-классы"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(
     *         required={"venue_id","title","starts_at","ends_at","max_participants"},
     *         @OA\Property(property="venue_id", type="integer", example=1),
     *         @OA\Property(property="title", type="string", example="Мастер-класс по рисованию"),
     *         @OA\Property(property="starts_at", type="string", format="date-time", example="2026-12-25 10:00:00"),
     *         @OA\Property(property="ends_at", type="string", format="date-time", example="2026-12-25 13:00:00"),
     *         @OA\Property(property="max_participants", type="integer", example=20),
     *         @OA\Property(property="price", type="number", example=1500)
     *     )),
     *     @OA\Response(response=201, description="Создано"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function store(StoreWorkshopRequest $request): JsonResponse
    {
        $this->authorize('create', Workshop::class);
        $data = $request->validated();
        $organizer = Organizer::where('user_id', auth()->id())->first();
        if (!$organizer && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Только организаторы могут создавать мастер-классы.'], 403);
        }
        $data['organizer_id'] = $organizer?->id ?? $request->organizer_id;
        $workshop = Workshop::create($data);
        return response()->json(['message' => 'Мастер-класс создан.', 'data' => $workshop->load(['organizer', 'venue'])], 201);
    }

    /**
     * @OA\Get(
     *     path="/workshops/{id}",
     *     summary="Просмотр мастер-класса",
     *     tags={"Мастер-классы"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные"),
     *     @OA\Response(response=404, description="Не найдено")
     * )
     */
    public function show(Workshop $workshop): JsonResponse
    {
        $workshop->load(['organizer.user', 'venue']);
        $workshop->available_spots = $workshop->availableSpots();
        return response()->json(['data' => $workshop]);
    }

    /**
     * @OA\Patch(
     *     path="/workshops/{id}",
     *     summary="Обновить мастер-класс (владелец/админ)",
     *     tags={"Мастер-классы"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="title", type="string", example="Новое название"))),
     *     @OA\Response(response=200, description="Обновлено"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function update(StoreWorkshopRequest $request, Workshop $workshop): JsonResponse
    {
        $this->authorize('update', $workshop);
        $workshop->update($request->validated());
        return response()->json(['message' => 'Мастер-класс обновлён.', 'data' => $workshop->fresh(['organizer', 'venue'])]);
    }

    /**
     * @OA\Delete(
     *     path="/workshops/{id}",
     *     summary="Удалить мастер-класс (владелец/админ)",
     *     tags={"Мастер-классы"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Удалено"),
     *     @OA\Response(response=403, description="Нет прав")
     * )
     */
    public function destroy(Workshop $workshop): JsonResponse
    {
        $this->authorize('delete', $workshop);
        $workshop->delete();
        return response()->json(['message' => 'Мастер-класс удалён.']);
    }
}
