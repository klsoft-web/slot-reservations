<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\HoldStatus;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SlotService
{
    private const SLOTS_CACHE_KEY = 'slots';
    private const SLOTS_CACHE_PERIOD = 5;
    private const SLOTS_AWAIT_DELAY = 5 * 1000;
    private const HOLD_LIFE_PERIOD = 5 * 60;
    private const SLOT_LOCK_PERIOD = 4;

    function getAvailableSlots(): Collection
    {
        try {
            $availableSlots = Cache::get(SlotService::SLOTS_CACHE_KEY);
            if (!$availableSlots) {
                $lock = Cache::lock(self::SLOTS_CACHE_KEY, self::SLOTS_CACHE_PERIOD - 1);
                if ($lock->get()) {
                    Cache::put(self::SLOTS_CACHE_KEY, Slot::all(), self::SLOTS_CACHE_PERIOD);
                    $availableSlots = Cache::get(self::SLOTS_CACHE_KEY, Collection::empty());
                    $lock->release();
                } else {
                    usleep(self::SLOTS_AWAIT_DELAY);
                    while ($availableSlots = Cache::get(SlotService::SLOTS_CACHE_KEY)) {
                        usleep(self::SLOTS_AWAIT_DELAY);
                    }
                }
            }
            return $availableSlots;
        } catch (\Throwable $e) {
            return Collection::empty();
        }
    }

    function holdSlot(int $slotId, string $idempotencyKey): int
    {
        return Cache::rememberForever($slotId . "_" . $idempotencyKey, function () use ($slotId) {
            $availableSlots = $this->getAvailableSlots();
            $slot = $availableSlots->find($slotId);
            if ($slot) {
                $hold = new Hold();
                $hold->status = HoldStatus::Held;
                $hold->slot()->associate($slot);
                if ($hold->save() &&
                    $slot->capacity > 0) {
                    return $hold->id;
                }
            }
            return -1;
        });
    }

    function confirmHold(int $holdId): bool
    {
        $hold = Hold::find($holdId);
        if ($hold &&
            $this->isHoldLive($hold)
        ) {
            if ($hold->status != HoldStatus::Confirmed) {
                return Cache::lock($hold->slot->id, self::SLOT_LOCK_PERIOD)->block(self::SLOT_LOCK_PERIOD, function () use ($hold) {
                    try {
                        $hold->status = HoldStatus::Confirmed;
                        DB::beginTransaction();
                        if ($hold->save()) {
                            $availableSlots = $this->getAvailableSlots();
                            $slot = $availableSlots->find($hold->slot->id);
                            if ($slot && $slot->remaining > 0) {
                                $slot->remaining--;
                                if ($slot->save()) {
                                    DB::commit();
                                    Cache::forget(SlotService::SLOTS_CACHE_KEY);
                                    return true;
                                }
                            }
                            DB::rollBack();
                        }
                    } catch (\Throwable $e) {
                        DB::rollBack();
                    }
                    return false;
                });
            } else {
                return true;
            }
        }
        return false;
    }

    private function isHoldLive(Hold $hold): bool
    {
        return $hold->created_at > now()->subSeconds(self::HOLD_LIFE_PERIOD);
    }

    function cancelHold(int $holdId): bool
    {
        try {
            $hold = Hold::find($holdId);
            if ($hold &&
                $this->isHoldLive($hold)) {
                if ($hold->status != HoldStatus::Cancelled) {
                    $holdPrevStatus = $hold->status;
                    $hold->status = HoldStatus::Cancelled;
                    if ($holdPrevStatus == HoldStatus::Confirmed) {
                        return Cache::lock($hold->slot->id, self::SLOT_LOCK_PERIOD)->block(self::SLOT_LOCK_PERIOD, function () use ($hold) {
                            try {
                                DB::beginTransaction();
                                if ($hold->save()) {
                                    $availableSlots = $this->getAvailableSlots();
                                    $slot = $availableSlots->find($hold->slot->id);
                                    if ($slot) {
                                        $slot->remaining++;
                                        if ($slot->save()) {
                                            DB::commit();
                                            Cache::forget(SlotService::SLOTS_CACHE_KEY);
                                            return true;
                                        }
                                    }
                                }
                                DB::rollBack();
                            } catch (\Throwable $e) {
                                DB::rollBack();
                            }
                            return false;
                        });
                    } else {
                        if ($hold->save()) {
                            Cache::forget(SlotService::SLOTS_CACHE_KEY);
                            return true;
                        }
                    }
                } else {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
