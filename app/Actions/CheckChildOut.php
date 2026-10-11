<?php

namespace App\Actions;

use App\Models\CheckIn;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CheckChildOut
{
    public function handle(CheckIn $checkIn): CheckIn
    {
        if ($checkIn->checked_out_at !== null) {
            throw (new ModelNotFoundException)->setModel(CheckIn::class, [$checkIn->id]);
        }

        $checkIn->checked_out_at = now();
        $checkIn->save();

        return $checkIn;
    }
}
