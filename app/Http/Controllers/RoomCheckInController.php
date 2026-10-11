<?php

namespace App\Http\Controllers;

use App\Actions\CheckChildIntoRoom;
use App\Http\Requests\StoreCheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoomCheckInController extends Controller
{
    public function store(StoreCheckInRequest $request, Room $room, CheckChildIntoRoom $checkChildIntoRoom): JsonResponse
    {
        $checkIn = $checkChildIntoRoom->handle($room, $request->integer('child_id'));

        return CheckInResource::make($checkIn)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
