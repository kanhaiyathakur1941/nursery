<?php

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;

it('lists the children currently in the room in check-in order', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $otherRoom = Room::factory()->for($tenant)->create();
    $firstChild = Child::factory()->for($tenant)->create(['name' => 'Ava']);
    $secondChild = Child::factory()->for($tenant)->create(['name' => 'Noah']);
    $checkedOutChild = Child::factory()->for($tenant)->create(['name' => 'Mia']);
    $childInOtherRoom = Child::factory()->for($tenant)->create(['name' => 'Leo']);

    $this->travelTo('2026-01-01 09:00:00');
    asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $firstChild->id,
    ])->assertCreated();

    $this->travelTo('2026-01-01 10:00:00');
    asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $secondChild->id,
    ])->assertCreated();

    CheckIn::factory()->checkedOut()->for($tenant)->for($room)->for($checkedOutChild)->create();
    CheckIn::factory()->for($tenant)->for($otherRoom)->for($childInOtherRoom)->create();

    $response = asTenant($tenant)->getJson("/rooms/{$room->id}/occupants");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.child_id', $firstChild->id)
        ->assertJsonPath('data.0.name', 'Ava')
        ->assertJsonPath('data.0.checked_in_at', '2026-01-01T09:00:00.000000Z')
        ->assertJsonPath('data.1.child_id', $secondChild->id)
        ->assertJsonPath('data.1.name', 'Noah')
        ->assertJsonPath('data.1.checked_in_at', '2026-01-01T10:00:00.000000Z');
});

it('returns an empty list when nobody is in the room', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();

    $response = asTenant($tenant)->getJson("/rooms/{$room->id}/occupants");

    $response->assertOk()
        ->assertJsonPath('data', []);
});

it('returns 404 when the room belongs to another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $room = Room::factory()->for($otherTenant)->create();
    $child = Child::factory()->for($otherTenant)->create(['name' => 'Ava']);
    CheckIn::factory()->for($otherTenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->getJson("/rooms/{$room->id}/occupants");

    $response->assertNotFound()
        ->assertJsonMissing(['name' => 'Ava']);
});

it('returns 404 when the room does not exist', function () {
    $tenant = Tenant::factory()->create();

    $response = asTenant($tenant)->getJson('/rooms/999999/occupants');

    $response->assertNotFound();
});
