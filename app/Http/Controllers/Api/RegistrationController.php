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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Регистрации", description="Запись на мастер-классы")
 */
class RegistrationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/registrations",
     *     summary="Список регистраций",
     *     tags={"Регистрации"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="workshop_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Список")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
        $query = Registration::with(['workshop', 'participant']);
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if (auth()->user()->isParticipant()) {
            $query->where('participant_user_id', auth()->id());
        }
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

    /**
     * @OA\Post(
     *     path="/registrations",
     *     summary="Записаться на мастер-класс",
     *     tags={"Регистрации"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(@OA\JsonContent(required={"workshop_id"}, @OA\Property(property="workshop_id", type="integer"))),
     *     @OA\Response(response=201, description="Заявка создана"),
     *     @OA\Response(response=422, description="Ошибка")
     * )
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        // ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
        $this->authorize('create', Registration::class);
        $workshop = Workshop::find($request->workshop_id);
        if (!$workshop) {
            return response()->json(['message' => 'Мастер-класс не найден.'], 404);
        }
        if ($workshop->starts_at->isPast()) {
            return response()->json(['message' => 'Нельзя записаться на мастер-класс, который уже начался или прошёл.'], 422);
        }
        $existing = Registration::where('workshop_id', $workshop->id)
            ->where('participant_user_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])->first();
        if ($existing) {
            $msg = $existing->status === 'approved'
                ? 'Вы уже записаны на этот мастер-класс.'
                : 'Ваша заявка уже ожидает подтверждения.';
            return response()->json(['message' => $msg], 422);
        }
        if ($workshop->isFull()) {
            return response()->json(['message' => 'К сожалению, все места заняты.'], 422);
        }
        $registration = Registration::create([
            'workshop_id' => $workshop->id,
            'participant_user_id' => auth()->id(),
            'status' => 'pending',
            'paid' => false,
        ]);
        return response()->json(['message' => 'Заявка отправлена!', 'data' => $registration->load(['workshop', 'participant'])], 201);
    }

    /**
     * @OA\Get(
     *     path="/registrations/{id}",
     *     summary="Просмотр регистрации",
     *     tags={"Регистрации"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Registration $registration): JsonResponse
    {
        // ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
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

    /**
     * @OA\Patch(
     *     path="/registrations/{id}/status",
     *     summary="Изменить статус заявки",
     *     tags={"Регистрации"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(required={"status"}, @OA\Property(property="status", type="string", enum={"approved","cancelled"}))),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function updateStatus(UpdateRegistrationStatusRequest $request, Registration $registration): JsonResponse
    {
        // ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
        $this->authorize('updateStatus', $registration);
        $newStatus = RegistrationStatus::from($request->status);
        if ($newStatus === RegistrationStatus::Approved) {
            $workshop = $registration->workshop;
            if ($workshop->isFull()) {
                return response()->json(['message' => 'Нельзя подтвердить заявку — все места уже заняты.'], 422);
            }
            DB::transaction(function () use ($registration) {
                $registration->update(['status' => 'approved', 'paid' => true]);
            });
        } else {
            $registration->update(['status' => 'cancelled']);
        }
        $message = match ($newStatus) {
            RegistrationStatus::Approved => 'Заявка подтверждена.',
            RegistrationStatus::Cancelled => 'Заявка отменена.',
            default => 'Статус обновлён.',
        };
        return response()->json(['message' => $message, 'data' => $registration->fresh(['workshop', 'participant'])]);
    }

    /**
     * @OA\Delete(
     *     path="/registrations/{id}",
     *     summary="Удалить регистрацию",
     *     tags={"Регистрации"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function destroy(Registration $registration): JsonResponse
    {
        // ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
        $this->authorize('delete', $registration);
        $registration->delete();
        return response()->json(['message' => 'Регистрация удалена.']);
    }
}
