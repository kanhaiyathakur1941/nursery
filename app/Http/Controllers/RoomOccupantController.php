<?php

namespace App\Http\Controllers;

use App\Actions\ListRoomOccupants;
use App\Http\Resources\OccupantResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomOccupantController extends Controller
{
    public function index(Room $room, ListRoomOccupants $listRoomOccupants): AnonymousResourceCollection
    {
        return OccupantResource::collection($listRoomOccupants->handle($room));
    }
}
