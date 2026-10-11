<?php

namespace App\Exceptions;

use App\Models\CheckIn;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChildAlreadyCheckedIn extends Exception implements ShouldntReport
{
    public function __construct(public ?CheckIn $checkIn = null)
    {
        parent::__construct('This child is already checked into a room.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $this->getMessage(),
            'room_id' => $this->checkIn?->room_id,
        ], fn (mixed $value): bool => $value !== null), 409);
    }
}
