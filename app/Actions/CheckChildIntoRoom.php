<?php

namespace App\Actions;

use App\Exceptions\ChildAlreadyCheckedIn;
use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CheckChildIntoRoom
{
    public function handle(Room $room, int $childId): CheckIn
    {
        $child = Child::query()->whereKey($childId)->first();

        if ($child === null || $child->tenant_id !== $room->tenant_id) {
            throw (new ModelNotFoundException)->setModel(Child::class, [$childId]);
        }

        return DB::transaction(function () use ($room, $child): CheckIn {
            $openCheckIn = CheckIn::query()
                ->whereBelongsTo($child)
                ->open()
                ->lockForUpdate()
                ->first();

            if ($openCheckIn !== null) {
                throw new ChildAlreadyCheckedIn($openCheckIn);
            }

            try {
                return CheckIn::query()->create([
                    'tenant_id' => $room->tenant_id,
                    'room_id' => $room->id,
                    'child_id' => $child->id,
                    'checked_in_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                $openCheckIn = CheckIn::query()
                    ->whereBelongsTo($child)
                    ->open()
                    ->first();

                throw new ChildAlreadyCheckedIn($openCheckIn);
            }
        });
    }
}
