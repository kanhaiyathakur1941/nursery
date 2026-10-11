<?php

namespace App\Http\Controllers;

use App\Actions\CheckChildOut;
use App\Http\Resources\CheckInResource;
use App\Models\CheckIn;

class CheckOutController extends Controller
{
    public function store(CheckIn $checkIn, CheckChildOut $checkChildOut): CheckInResource
    {
        return CheckInResource::make($checkChildOut->handle($checkIn));
    }
}
