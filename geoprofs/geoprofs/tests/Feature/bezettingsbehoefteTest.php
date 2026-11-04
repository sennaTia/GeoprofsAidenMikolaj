<?php

use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\StaffingPlanController;
use App\Models\{Department, User, LeaveRequest, LeaveApproval, StaffingPlan};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http};

$check = function (string $naam, bool $ok) {
    echo ($ok ? '[OK]   ' : '[FOUT] ') . $naam . PHP_EOL;
};

$gooitFout = function (string $naam, callable $fn) use ($check) {
    try {
        $fn();
        $check($naam, false);
    } catch (\Throwable $e) {
        $check($naam, true);
    }
};

$verzoek = function (User $user, array $data = []) {
    auth()->setUser($user);
    $request = Request::create('/test', 'POST', $data);
    $request->setUserResolver(fn () => $user);
    return $request;
};

DB::beginTransaction();

try {
    Http::fake(); // approve kan de overdracht naar de betalingsinstantie starten

    $afd = Department::create(['name' => 'tinker-Bezetting', 'min_staffing' => 2]);
    $maak = fn ($naam, $rol) => User::create([
        'name' => $naam, 'email' => "tinker-$naam@geoprofs.test", 'password' => 'Welkom123!Test',
        'role' => $rol, 'department_id' => $afd->id,
    ]);

    $manager = $maak('manager', 'manager');
    $anna = $maak('anna', 'werknemer');
    $bram = $maak('bram', 'werknemer');
    $cees = $maak('cees', 'werknemer');

    // Cees is vrijdag 13-11 al weg
    LeaveRequest::create(['user_id' => $cees->id, 'start_date' => '2026-11-13', 'end_date' => '2026-11-13', 'days_requested' => 1, 'reason' => 'Tandarts', 'status' => 'goedgekeurd']);

    // Anna vraagt vrijdag 13-11 t/m maandag 16-11 aan
    $aanvraag = LeaveRequest::create(['user_id' => $anna->id, 'start_date' => '2026-11-13', 'end_date' => '2026-11-16', 'days_requested' => 2, 'reason' => 'Lang weekend', 'status' => 'in_behandeling']);

    $lr = app(LeaveRequestController::class);
    $sp = app(StaffingPlanController::class);

    auth()->setUser($manager);
    $data = $lr->staffing($aanvraag)->getData(true);
    $check('Teamgrootte telt werknemers en manager', $data['teamgrootte'] === 4);
    $check('Weekend wordt overgeslagen', count($data['dagen']) === 2);
    $check('Vrijdag: Cees en Anna weg, bezetting 2', $data['dagen'][0]['verwacht'] === 2);
    $check('Maandag: alleen Anna weg, bezetting 3', $data['dagen'][1]['verwacht'] === 3);
    $check('Geen waarschuwing zolang minimum gehaald wordt', $data['onder_minimum'] === false);

    $sp->store($verzoek($manager, ['date' => '2026-11-16', 'min_staffing' => 4]));
    $check('Manager legt planning vast', StaffingPlan::where('department_id', $afd->id)->where('date', '2026-11-16')->value('min_staffing') === 4);

    $sp->store($verzoek($manager, ['date' => '2026-11-16', 'min_staffing' => 4]));
    $check('Zelfde datum opnieuw opslaan geeft geen dubbel', StaffingPlan::where('department_id', $afd->id)->count() === 1);

    $gooitFout('Werknemer kan geen planning vastleggen', fn () => $sp->store($verzoek($anna, ['date' => '2026-11-17', 'min_staffing' => 1])));

    auth()->setUser($manager);
    $data = $lr->staffing($aanvraag)->getData(true);
    $check('Planning maandag wordt gebruikt als minimum', $data['dagen'][1]['minimum'] === 4);
    $check('Onder minimum wordt gesignaleerd', $data['onder_minimum'] === true);

    $res = $lr->approve($verzoek($manager), $aanvraag);
    $check('Goedkeuren onder minimum zonder reden wordt geblokkeerd', $res->getStatusCode() === 409);
    $check('Status blijft in behandeling na blokkade', $aanvraag->fresh()->status === 'in_behandeling');
    $check('Geen goedkeuring vastgelegd na blokkade', LeaveApproval::where('leave_request_id', $aanvraag->id)->doesntExist());

    $gooitFout('Te korte reden wordt geweigerd', fn () => $lr->approve($verzoek($manager, ['override_reason' => 'kort']), $aanvraag));

    $lr->approve($verzoek($manager, ['override_reason' => 'Project afgerond, maandag rustig']), $aanvraag);
    $goedkeuring = LeaveApproval::where('leave_request_id', $aanvraag->id)->first();
    $check('Goedkeuring met reden wordt vastgelegd', $goedkeuring && $goedkeuring->override_reason !== null);
    $check('Bezetting bij beslissing is vastgelegd', $goedkeuring?->lowest_staffing === 3 && $goedkeuring?->min_staffing === 4);

    $bramAanvraag = LeaveRequest::create(['user_id' => $bram->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-20', 'days_requested' => 1, 'reason' => 'Verhuizing', 'status' => 'in_behandeling']);
    $lr->approve($verzoek($manager), $bramAanvraag);
    $bramGoedkeuring = LeaveApproval::where('leave_request_id', $bramAanvraag->id)->first();
    $check('Boven minimum goedkeuren kan zonder reden', $bramGoedkeuring && $bramGoedkeuring->override_reason === null);

    auth()->setUser($anna);
    $gooitFout('Werknemer kan bezetting niet inzien', fn () => $lr->staffing($bramAanvraag));
} finally {
    DB::rollBack();
}