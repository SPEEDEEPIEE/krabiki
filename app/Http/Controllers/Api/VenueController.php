<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function index(): JsonResponse
    {
        $venues = Venue::paginate(15);
        return response()->json($venues);
    }

    public function store(StoreVenueRequest $request): JsonResponse
    {
        $this->authorize('create', Venue::class);

        $venue = Venue::create($request->validated());

        return response()->json([
            'message' => 'Площадка успешно создана.',
            'data' => $venue,
        ], 201);
    }

    public function show(Venue $venue): JsonResponse
    {
        return response()->json(['data' => $venue]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): JsonResponse
    {
        $this->authorize('update', $venue);

        $venue->update($request->validated());

        return response()->json([
            'message' => 'Площадка успешно обновлена.',
            'data' => $venue,
        ]);
    }

    public function destroy(Venue $venue): JsonResponse
    {
        $this->authorize('delete', $venue);

        $venue->delete();

        return response()->json([
            'message' => 'Площадка успешно удалена.',
        ]);
    }
}