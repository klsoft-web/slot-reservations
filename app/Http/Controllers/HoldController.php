<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class HoldController extends Controller
{
    private SlotService $slotService;

    public function __construct(
        SlotService $slotService
    )
    {
        $this->slotService = $slotService;
    }

    function confirmHold(int $holdId): Response
    {
        if ($this->slotService->confirmHold($holdId)) {
            return response("confirmed");
        }
        return response(["status" => ResponseAlias::HTTP_CONFLICT], ResponseAlias::HTTP_CONFLICT);
    }

    function cancelHold(int $holdId): Response
    {
        if ($this->slotService->cancelHold($holdId)) {
            return response("canceled");
        }
        return response(["status" => ResponseAlias::HTTP_CONFLICT], ResponseAlias::HTTP_CONFLICT);
    }
}
