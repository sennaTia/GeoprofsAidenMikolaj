<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('zet 5 aanvragen in één bulk-actie allemaal op goedgekeurd', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $werknemer = User::factory()->create(['role' => 'werknemer']);
    $werknemer->leaveBalances()->create(['year' => 2027, 'total_days' => 25]);

    $aanvragen = collect(range(1, 5))->map(fn ($i) => $werknemer->leaveRequests()->create([
        'start_date' => "2027-0{$i}-01",
        'end_date' => "2027-0{$i}-02",
        'days_requested' => 1,
        'status' => 'in_behandeling',
    ]));

    $response = $this->actingAs($manager)->post('/verlofaanvragen/bulk-goedkeuren', [
        'leave_request_ids' => $aanvragen->pluck('id')->toArray(),
    ]);

    $response->assertStatus(200);
    expect($response->json('goedgekeurd'))->toHaveCount(5);

    $aanvragen->each(function ($aanvraag) {
        expect($aanvraag->refresh()->status)->toBe('goedgekeurd');
    });
});

it('weigert dat een werknemer zijn eigen aanvraag goedkeurt', function () {
    $werknemer = User::factory()->create(['role' => 'werknemer']);

    $aanvraag = $werknemer->leaveRequests()->create([
        'start_date' => '2027-06-01',
        'end_date' => '2027-06-02',
        'days_requested' => 1,
        'status' => 'in_behandeling',
    ]);

    $response = $this->actingAs($werknemer)
        ->post("/verlofaanvragen/{$aanvraag->id}/goedkeuren");

    $response->assertStatus(403);
    expect($aanvraag->refresh()->status)->toBe('in_behandeling');
});

it('laat een individuele goedkeuring de rest van de aanvragen ongemoeid', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $werknemer = User::factory()->create(['role' => 'werknemer']);
    $werknemer->leaveBalances()->create(['year' => 2027, 'total_days' => 25]);

    $aanvraag1 = $werknemer->leaveRequests()->create([
        'start_date' => '2027-07-01',
        'end_date' => '2027-07-02',
        'days_requested' => 1,
        'status' => 'in_behandeling',
    ]);

    $aanvraag2 = $werknemer->leaveRequests()->create([
        'start_date' => '2027-08-01',
        'end_date' => '2027-08-02',
        'days_requested' => 1,
        'status' => 'in_behandeling',
    ]);

    $this->actingAs($manager)->post("/verlofaanvragen/{$aanvraag1->id}/goedkeuren");

    expect($aanvraag1->refresh()->status)->toBe('goedgekeurd');
    expect($aanvraag2->refresh()->status)->toBe('in_behandeling');
});