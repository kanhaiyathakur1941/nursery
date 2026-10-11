<?php

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;

it('checks a child into a room', function () {
    $this->travelTo('2026-01-01 09:00:00');

    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.room_id', $room->id)
        ->assertJsonPath('data.child_id', $child->id)
        ->assertJsonPath('data.checked_in_at', '2026-01-01T09:00:00.000000Z')
        ->assertJsonPath('data.checked_out_at', null);

    $this->assertDatabaseHas('check_ins', [
        'id' => $response->json('data.id'),
        'tenant_id' => $tenant->id,
        'room_id' => $room->id,
        'child_id' => $child->id,
        'checked_out_at' => null,
    ]);
});

it('returns 409 when the child is already checked into another room', function () {
    $tenant = Tenant::factory()->create();
    $sunflowers = Room::factory()->for($tenant)->create();
    $explorers = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();
    $existing = CheckIn::factory()->for($tenant)->for($sunflowers)->for($child)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$explorers->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertConflict()
        ->assertJsonPath('message', 'This child is already checked into a room.')
        ->assertJsonPath('room_id', $sunflowers->id);

    $this->assertDatabaseCount('check_ins', 1);
    $this->assertDatabaseHas('check_ins', [
        'id' => $existing->id,
        'room_id' => $sunflowers->id,
        'checked_out_at' => null,
    ]);
});

it('returns 409 when the child is already checked into this room', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();
    CheckIn::factory()->for($tenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertConflict()
        ->assertJsonPath('room_id', $room->id);

    $this->assertDatabaseCount('check_ins', 1);
});

it('checks a child in again after they have checked out', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();
    CheckIn::factory()->checkedOut()->for($tenant)->for($room)->for($child)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.child_id', $child->id)
        ->assertJsonPath('data.checked_out_at', null);

    $this->assertDatabaseCount('check_ins', 2);
});

it('returns 422 when child id is missing', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'child_id' => 'The child id field is required.',
        ]);

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 422 when child id is not an integer', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => 'ava',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'child_id' => 'The child id field must be an integer.',
        ]);

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 404 when the child does not exist', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => 999999,
    ]);

    $response->assertNotFound();

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 404 when the child belongs to another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($otherTenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertNotFound();

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 404 when the room belongs to another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $room = Room::factory()->for($otherTenant)->create();
    $child = Child::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertNotFound();

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 400 when the tenant header is missing', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();

    $response = $this->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertBadRequest()
        ->assertJsonPath('message', 'A valid X-Tenant-ID header is required.');

    $this->assertDatabaseCount('check_ins', 0);
});

it('returns 400 when the tenant header is unknown', function () {
    $tenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();

    $response = $this->withHeaders([
        'X-Tenant-ID' => '999999',
    ])->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
    ]);

    $response->assertBadRequest()
        ->assertJsonPath('message', 'A valid X-Tenant-ID header is required.');

    $this->assertDatabaseCount('check_ins', 0);
});

it('does not let the body choose the tenant or close the visit', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $room = Room::factory()->for($tenant)->create();
    $child = Child::factory()->for($tenant)->create();

    $response = asTenant($tenant)->postJson("/rooms/{$room->id}/check-ins", [
        'child_id' => $child->id,
        'tenant_id' => $otherTenant->id,
        'checked_out_at' => now()->toJSON(),
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('check_ins', [
        'id' => $response->json('data.id'),
        'tenant_id' => $tenant->id,
        'checked_out_at' => null,
    ]);
});
