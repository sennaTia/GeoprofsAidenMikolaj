<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('geeft null terug als er geen verlofsaldo bestaat voor dat jaar', function () {
    $user = User::factory()->create();

    expect($user->remainingDays(2026))->toBeNull();
});

it('berekent het resterende saldo correct met goedgekeurde aanvragen', function () {
    $user = User::factory()->create();

    $user->leaveBalances()->create(['year' => 2026, 'total_days' => 25]);

    $user->leaveRequests()->create([
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-05',
        'days_requested' => 5,
        'status' => 'goedgekeurd',
    ]);

    expect($user->remainingDays(2026))->toBe(20.0);
});

it('telt aanvragen die nog in behandeling zijn niet mee', function () {
    $user = User::factory()->create();

    $user->leaveBalances()->create(['year' => 2026, 'total_days' => 25]);

    $user->leaveRequests()->create([
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-02',
        'days_requested' => 2,
        'status' => 'in_behandeling',
    ]);

    expect($user->remainingDays(2026))->toBe(25.0);
});