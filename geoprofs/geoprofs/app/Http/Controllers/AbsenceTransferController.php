<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsenceTransfer;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbsenceTransferController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['office_management', 'admin'])) {
            return response()->json(['message' => 'Geen toegang.'], 403);
        }

        return response()->json(AbsenceTransfer::with('leaveRequest')->latest()->get());
    }

    public function store(Request $request, $leaveRequestId)
    {
        if (!in_array($request->user()->role, ['office_management', 'admin'])) {
            return response()->json(['message' => 'Geen toegang.'], 403);
        }

        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

        if ($leaveRequest->status !== 'goedgekeurd') {
            return response()->json(['message' => 'Alleen goedgekeurde aanvragen worden doorgestuurd.'], 422);
        }

        $transfer = $this->transfer($leaveRequest);

        return response()->json($transfer, $transfer->status === 'sent' ? 200 : 502);
    }

    public function retry(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['office_management', 'admin'])) {
            return response()->json(['message' => 'Geen toegang.'], 403);
        }

        $transfer = AbsenceTransfer::findOrFail($id);

        if ($transfer->status === 'sent') {
            return response()->json(['message' => 'Deze overdracht is al verzonden.'], 422);
        }

        $this->send($transfer);
        $transfer = $transfer->fresh();

        return response()->json($transfer, $transfer->status === 'sent' ? 200 : 502);
    }

    public function retryFailed()
    {
        AbsenceTransfer::where('status', 'failed')
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->get()
            ->each(fn($transfer) => $this->send($transfer));
    }

    public function transfer(LeaveRequest $leaveRequest)
    {
        $payload = json_encode([
            'leave_request_id' => $leaveRequest->id,
            'employee_id' => $leaveRequest->user_id,
            'employee_name' => $leaveRequest->user->name,
            'start_date' => $leaveRequest->start_date,
            'end_date' => $leaveRequest->end_date,
        ]);

        $transfer = AbsenceTransfer::firstOrCreate(
            ['leave_request_id' => $leaveRequest->id],
            [
                'payload' => $payload,
                'hash' => hash_hmac('sha256', $payload, config('services.payroll.secret')),
                'status' => 'pending',
            ]
        );

        if ($transfer->status !== 'sent') {
            $this->send($transfer);
        }

        return $transfer->fresh();
    }

    private function send(AbsenceTransfer $transfer)
    {
        $transfer->increment('attempts');

        $url = (string) config('services.payroll.url');

        if (!str_starts_with($url, 'https://')) {
            return $this->fail($transfer, 'Verbinding is niet versleuteld (geen https).');
        }

        if (!hash_equals($transfer->hash, hash_hmac('sha256', $transfer->payload, config('services.payroll.secret')))) {
            return $this->fail($transfer, 'Integriteitscontrole mislukt: gegevens zijn gewijzigd.');
        }

        try {
            $response = Http::withOptions(['verify' => true])
                ->withHeaders(['X-Signature' => $transfer->hash])
                ->timeout(10)
                ->retry(3, 1000, throw: false)
                ->withBody($transfer->payload, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            return $this->fail($transfer, $e->getMessage());
        }

        if (!$response->successful()) {
            return $this->fail($transfer, 'Betalingsinstantie gaf status ' . $response->status() . '.');
        }

        if ($response->json('received_hash') !== $transfer->hash) {
            return $this->fail($transfer, 'Ontvangen gegevens wijken af van de verzonden gegevens.');
        }

        $transfer->update(['status' => 'sent', 'last_error' => null, 'sent_at' => now()]);
    }

    private function fail(AbsenceTransfer $transfer, string $error)
    {
        $transfer->update(['status' => 'failed', 'last_error' => $error, 'alerted_at' => now()]);

        Log::error('Overdracht afwezigheidsgegevens mislukt', ['transfer_id' => $transfer->id, 'error' => $error]);

        Mail::raw(
            "Overdracht {$transfer->id} van verlofaanvraag {$transfer->leave_request_id} is mislukt: {$error}",
            fn($message) => $message->to(config('services.payroll.hr_email'))->subject('Overdracht afwezigheidsgegevens mislukt')
        );
    }
}
