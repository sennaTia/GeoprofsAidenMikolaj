<?php

use App\Http\Controllers\AbsenceTransferController;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$check = function (string $naam, bool $ok) {
    echo ($ok ? '[OK]   ' : '[FOUT] ') . $naam . PHP_EOL;
};

$modus = 'ok';
$verzoeken = 0;
$laatsteVerzoek = null;

config([
    'services.payroll.url' => 'https://betalingsinstantie.test/afwezigheid',
    'services.payroll.secret' => 'tinker-geheim',
    'services.payroll.hr_email' => 'tinker-hr@geoprofs.test',
    'mail.default' => 'log',
]);

Http::fake(function ($request) use (&$modus, &$verzoeken, &$laatsteVerzoek) {
    $verzoeken++;
    $laatsteVerzoek = $request;

    if ($modus === 'fout') {
        return Http::response(['message' => 'Server error'], 500);
    }

    if ($modus === 'gewijzigd') {
        return Http::response(['received_hash' => 'onjuist'], 200);
    }

    return Http::response(['received_hash' => hash_hmac('sha256', $request->body(), 'tinker-geheim')], 200);
});

DB::beginTransaction();

try {
    $controller = new AbsenceTransferController();

    $hr = User::create(['name' => 'Test HR', 'email' => 'tinker-hr@geoprofs.test', 'password' => 'password', 'role' => 'office-manager']);
    $werknemer = User::create(['name' => 'Test Werknemer', 'email' => 'tinker-w@geoprofs.test', 'password' => 'password', 'role' => 'werknemer']);

    $als = function ($user) {
        $r = Request::create('/', 'POST');
        $r->setUserResolver(fn () => $user);
        return $r;
    };

    $nieuweAanvraag = fn ($status = 'goedgekeurd') => LeaveRequest::create([
        'user_id' => $werknemer->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
        'days_requested' => 3,
        'reason' => 'Vakantie',
        'status' => $status,
    ]);

    $check('Werknemer kan overdrachten niet inzien (403)', $controller->index($als($werknemer))->status() === 403);
    $check('HR kan overdrachten inzien (200)', $controller->index($als($hr))->status() === 200);
    $check('Niet-goedgekeurde aanvraag wordt niet doorgestuurd (422)', $controller->store($als($hr), $nieuweAanvraag('in_behandeling')->id)->status() === 422);

    $aanvraag1 = $nieuweAanvraag();
    $overdracht1 = $controller->transfer($aanvraag1);
    $check('Overdracht is verzonden', $overdracht1->status === 'sent');
    $check('Verbinding gebruikt https', str_starts_with($laatsteVerzoek->url(), 'https://'));
    $check('Verzonden body is gelijk aan opgeslagen gegevens', $laatsteVerzoek->body() === $overdracht1->payload);
    $check('Handtekening wordt meegestuurd', $laatsteVerzoek->header('X-Signature')[0] === $overdracht1->hash);
    $check('Reden van verlof wordt niet meegestuurd', !str_contains($overdracht1->payload, 'reason'));

    $verzoeken = 0;
    $controller->transfer($aanvraag1);
    $check('Verzonden overdracht wordt niet dubbel verstuurd', $verzoeken === 0);

    $modus = 'fout';
    $verzoeken = 0;
    $overdracht2 = $controller->transfer($nieuweAanvraag());
    $check('Mislukte overdracht krijgt status failed', $overdracht2->status === 'failed');
    $check('Er is 3 keer geprobeerd', $verzoeken === 3);
    $check('HR is gewaarschuwd', $overdracht2->alerted_at !== null);
    $check('Foutmelding is opgeslagen', str_contains($overdracht2->last_error, '500'));

    $modus = 'gewijzigd';
    $overdracht3 = $controller->transfer($nieuweAanvraag());
    $check('Afwijkende ontvangen gegevens worden afgekeurd', $overdracht3->status === 'failed' && $overdracht3->alerted_at !== null);

    $modus = 'ok';
    config(['services.payroll.url' => 'http://betalingsinstantie.test/afwezigheid']);
    $verzoeken = 0;
    $overdracht4 = $controller->transfer($nieuweAanvraag());
    $check('Onversleutelde verbinding wordt geweigerd', $overdracht4->status === 'failed' && $verzoeken === 0);
    config(['services.payroll.url' => 'https://betalingsinstantie.test/afwezigheid']);

    $overdracht4->update(['payload' => $overdracht4->payload . ' ']);
    $verzoeken = 0;
    $controller->retry($als($hr), $overdracht4->id);
    $check('Gewijzigde gegevens worden niet verstuurd', $verzoeken === 0 && str_contains($overdracht4->fresh()->last_error, 'Integriteit'));

    $check('Werknemer kan niet opnieuw proberen (403)', $controller->retry($als($werknemer), $overdracht2->id)->status() === 403);
    $check('HR kan mislukte overdracht opnieuw versturen (200)', $controller->retry($als($hr), $overdracht2->id)->status() === 200);
    $check('Na retry is de overdracht verzonden', $overdracht2->fresh()->status === 'sent');
    $check('Verzonden overdracht kan niet opnieuw (422)', $controller->retry($als($hr), $overdracht2->id)->status() === 422);

    $overdracht3->update(['attempts' => 5]);
    $controller->retryFailed();
    $check('Na 5 pogingen wordt niet automatisch opnieuw geprobeerd', $overdracht3->fresh()->status === 'failed');

    $overdracht3->update(['attempts' => 1]);
    $controller->retryFailed();
    $check('Automatische retry verstuurt mislukte overdracht', $overdracht3->fresh()->status === 'sent');
} finally {
    DB::rollBack();
    echo PHP_EOL . 'Testdata teruggedraaid.' . PHP_EOL;
}