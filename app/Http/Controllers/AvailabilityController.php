<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AvailabilityController extends Controller
{
    private const IDEMPOTENCY_KEY = 'Idempotency-Key';
    private SlotService $slotService;

    public function __construct(
        SlotService $slotService
    )
    {
        $this->slotService = $slotService;
    }

    public function getAvailableSlots(): JsonResponse
    {
        return response()->json($this->slotService->getAvailableSlots()->map(fn($slot) => [
            "slot_id" => $slot->id,
            "capacity" => $slot->capacity,
            "remaining" => $slot->remaining]));
    }

    public function holdSlot(Request $request, int $slotId): JsonResponse
    {
        $idempotencyKeyValue = $request->header(self::IDEMPOTENCY_KEY);
        if ($idempotencyKeyValue) {
            $holdId = $this->slotService->holdSlot($slotId, $idempotencyKeyValue);
            if ($holdId > 0) {
                return response()->json(["hold_id" => $holdId]);
            }
        }
        return response()->json(["status" => ResponseAlias::HTTP_CONFLICT], ResponseAlias::HTTP_CONFLICT);

    }
}
