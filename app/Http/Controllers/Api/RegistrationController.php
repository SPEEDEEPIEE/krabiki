<?php

namespace App\Http\Controllers\Api;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\UpdateRegistrationStatusRequest;
use App\Models\Registration;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Registration::with(['workshop', 'participant']);

        // Фильтр по мастер-классу
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Участник видит только свои
        if (auth()->user()->isParticipant()) {
            $query->where('participant_user_id', auth()->id());
        }

        // Организатор видит только регистрации на свои мастер-классы
        if (auth()->user()->isOrganizer()) {
            $organizer = auth()->user()->organizer;
            if ($organizer) {
                $query->whereHas('workshop', function ($q) use ($organizer) {
                    $q->where('organizer_id', $organizer->id);
                });
            }
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        return response()->json($query->paginate($perPage));
    }

    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $this->authorize('create', Registration::class);

        $workshop = Workshop::find($request->workshop_id);

        if (!$workshop) {
            return response()->json(['message' => 'Мастер-класс не найден.'], 404);
        }

        // Нельзя записаться на прошедший мастер-класс
        if ($workshop->starts_at->isPast()) {
            return response()->json([
                'message' => 'Нельзя записаться на мастер-класс, который уже начался или прошёл.',
            ], 422);
        }

        // Проверка на повторную активную регистрацию
        $existing = Registration::where('workshop_id', $workshop->id)
            ->where('participant_user_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            $msg = $existing->status === 'approved'
                ? 'Вы уже записаны на этот мастер-класс.'
                : 'Ваша заявка уже ожидает подтверждения.';
            return response()->json(['message' => $msg], 422);
        }

        // Проверка мест
        if ($workshop->isFull()) {
            return response()->json([
                'message' => 'К сожалению, все места заняты. Попробуйте другой мастер-класс.',
            ], 422);
        }

        $registration = Registration::create([
            'workshop_id' => $workshop->id,
            'participant_user_id' => auth()->id(),
            'status' => 'pending',
            'paid' => false,
        ]);

        return response()->json([
            'message' => 'Заявка отправлена! Ожидайте подтверждения от организатора.',
            'data' => $registration->load(['workshop', 'participant']),
        ], 201);
    }

    public function show(Registration $registration): JsonResponse
    {
        $this->authorize('view', $registration);

        $statusMessages = [
            'pending' => 'Заявка ожидает рассмотрения',
            'approved' => 'Заявка подтверждена',
            'cancelled' => 'Заявка отменена',
        ];

        return response()->json([
            'message' => 'Информация о регистрации.',
            'status_text' => $statusMessages[$registration->status->value] ?? '',
            'data' => $registration->load(['workshop.organizer', 'participant']),
        ]);
    }

    public function updateStatus(
        UpdateRegistrationStatusRequest $request,
        Registration $registration
    ): JsonResponse {
        $this->authorize('updateStatus', $registration);

        $newStatus = RegistrationStatus::from($request->status);

        if ($newStatus === RegistrationStatus::Approved) {
            $workshop = $registration->workshop;

            if ($workshop->isFull()) {
                return response()->json([
                    'message' => 'Нельзя подтвердить заявку — все места уже заняты.',
                ], 422);
            }

            DB::transaction(function () use ($registration) {
                $registration->update([
                    'status' => 'approved',
                    'paid' => true,
                ]);
            });
        } else {
            $registration->update(['status' => 'cancelled']);
        }

        $message = match ($newStatus) {
            RegistrationStatus::Approved => 'Заявка подтверждена. Участник допущен к мастер-классу.',
            RegistrationStatus::Cancelled => 'Заявка отменена.',
            default => 'Статус обновлён.',
        };

        return response()->json([
            'message' => $message,
            'data' => $registration->fresh(['workshop', 'participant']),
        ]);
    }

    public function destroy(Registration $registration): JsonResponse
    {
        $this->authorize('delete', $registration);
        $registration->delete();

        return response()->json(['message' => 'Регистрация удалена.']);
    }
}