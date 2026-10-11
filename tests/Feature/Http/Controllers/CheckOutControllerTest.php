<?php

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;

it('checks a child out of a room', function () {
    $this->travelTo('2026-01-01 09:00:00');

    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();
    $checkIn = CheckIn::factory()->for($tenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->postJson("/check-ins/{$checkIn->id}/check-out");

    $response->assertOk()
        ->assertJsonPath('data.id', $checkIn->id)
        ->assertJsonPath('data.room_id', $room->id)
        ->assertJsonPath('data.child_id', $child->id)
        ->assertJsonPath('data.checked_out_at', '2026-01-01T09:00:00.000000Z');

    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'tenant_id' => $tenant->id,
        'room_id' => $room->id,
        'child_id' => $child->id,
    ]);
    $this->assertDatabaseMissing('check_ins', [
        'id' => $checkIn->id,
        'checked_out_at' => null,
    ]);
});

it('returns 404 when the check-in is already closed', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();
    $checkIn = CheckIn::factory()->checkedOut()->for($tenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->postJson("/check-ins/{$checkIn->id}/check-out");

    $response->assertNotFound();

    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'checked_out_at' => $checkIn->checked_out_at,
    ]);
});

it('returns 404 when the check-in belongs to another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $room = Room::factory()->for($otherTenant)->create();
    $child = Child::factory()->for($otherTenant)->create();
    $checkIn = CheckIn::factory()->for($otherTenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->postJson("/check-ins/{$checkIn->id}/check-out");

    $response->assertNotFound();

    $this->assertDatabaseHas('check_ins', [
        'id' => $checkIn->id,
        'tenant_id' => $otherTenant->id,
        'checked_out_at' => null,
    ]);
});

it('returns 404 when the check-in does not exist', function () {
    $tenant = Tenant::factory()->create();

    $response = asTenant($tenant)->postJson('/check-ins/999999/check-out');

    $response->assertNotFound();
});
