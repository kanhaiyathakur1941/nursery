<?php

namespace App\Actions;

use App\Models\CheckIn;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class ListRoomOccupants
{
    /**
     * @return Collection<int, CheckIn>
     */
    public function handle(Room $room): Collection
    {
        return $room->checkIns()
            ->open()
            ->with('child')
            ->orderBy('checked_in_at')
            ->orderBy('id')
            ->get();
    }
}
