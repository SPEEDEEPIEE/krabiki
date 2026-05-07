<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkshopRequest;
use App\Models\Organizer;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
  public function index(Request $request): JsonResponse
{
    $query = Workshop::with(['organizer', 'venue']);

    if ($request->filled('organizer_id')) {
        $query->where('organizer_id', $request->organizer_id);
    }

    if ($request->filled('date_from')) {
        $query->where('starts_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->where('ends_at', '<=', $request->date_to);
    }

    if ($request->boolean('has_available_spots')) {
        $query->withCount(['registrations as approved_count' => function ($q) {
            $q->where('status', 'approved');
        }])->whereRaw('max_participants > approved_count');
    }

    $sort = $request->get('sort', '-starts_at');
    $direction = 'asc';
    if (str_starts_with($sort, '-')) {
        $direction = 'desc';
        $sort = substr($sort, 1);
    }

    $allowedSorts = ['starts_at', 'ends_at', 'title', 'price', 'created_at'];
    if (in_array($sort, $allowedSorts)) {
        $query->orderBy($sort, $direction);
    } else {
        $query->orderBy('starts_at', 'asc');
    }

    $perPage = min((int) $request->get('per_page', 15), 100);
    $workshops = $query->paginate($perPage);

    $workshops->getCollection()->transform(function ($workshop) {
        $workshop->available_spots = $workshop->availableSpots();
        return $workshop;
    });

    // Явно возвращаем стандартный формат пагинации Laravel
    return response()->json([
        'data' => $workshops->items(),
        'meta' => [
            'current_page' => $workshops->currentPage(),
            'per_page' => $workshops->perPage(),
            'total' => $workshops->total(),
        ],
        'links' => [
            'first' => $workshops->url(1),
            'last' => $workshops->url($workshops->lastPage()),
            'prev' => $workshops->previousPageUrl(),
            'next' => $workshops->nextPageUrl(),
        ],
    ]);
}

    public function store(StoreWorkshopRequest $request): JsonResponse
    {
        $this->authorize('create', Workshop::class);

        $data = $request->validated();

        // Если админ создаёт — нужно указать organizer_id
        if (auth()->user()->isAdmin()) {
            if (!$request->filled('organizer_id')) {
                return response()->json([
                    'message' => 'Администратор должен указать organizer_id.',
                ], 422);
            }
            $data['organizer_id'] = $request->organizer_id;
        } else {
            // Организатор создаёт свой
            $organizer = Organizer::where('user_id', auth()->id())->first();
            if (!$organizer) {
                return response()->json([
                    'message' => 'Только организаторы могут создавать мастер-классы.',
                ], 403);
            }
            $data['organizer_id'] = $organizer->id;
        }

        $workshop = Workshop::create($data);

        return response()->json([
            'message' => 'Мастер-класс успешно создан. Ждём участников!',
            'data' => $workshop->load(['organizer', 'venue']),
        ], 201);
    }

    public function show(Workshop $workshop): JsonResponse
    {
        $workshop->load(['organizer.user', 'venue']);
        $workshop->available_spots = $workshop->availableSpots();

        return response()->json(['data' => $workshop]);
    }

    public function update(StoreWorkshopRequest $request, Workshop $workshop): JsonResponse
    {
        $this->authorize('update', $workshop);

        $workshop->update($request->validated());

        return response()->json([
            'message' => 'Мастер-класс успешно обновлён.',
            'data' => $workshop->fresh(['organizer', 'venue']),
        ]);
    }

    public function destroy(Workshop $workshop): JsonResponse
    {
        $this->authorize('delete', $workshop);

        $workshop->delete();

        return response()->json([
            'message' => 'Мастер-класс удалён.',
        ]);
    }
}