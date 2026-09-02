<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EventAttendee;
use App\Models\JournalEntry;
use App\Models\Setting;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Parse a scanned QR payload and resolve it to a receipt or attendee ticket.
     */
    public function verify(Request $request)
    {
        $code = trim((string) $request->query('code', $request->input('code', '')));

        if ($code === '') {
            return view('verification.show')->withErrors(['code' => 'No verification code provided.']);
        }

        $parts = explode('|', $code);

        // Receipt: OGCM|RCP|RCP-XXXX|amount
        if (count($parts) >= 3 && strtoupper($parts[0]) === 'OGCM' && strtoupper($parts[1]) === 'RCP') {
            return $this->resolveReceipt($parts[2]);
        }

        // Ticket: OGCM|TICKET|TICKETNO|slug
        if (count($parts) >= 3 && strtoupper($parts[0]) === 'OGCM' && strtoupper($parts[1]) === 'TICKET') {
            return $this->resolveTicket($parts[2]);
        }

        // Fallback: raw receipt no (RCP-...) or raw ticket no
        if (str_starts_with(strtoupper($code), 'RCP')) {
            return $this->resolveReceipt($code);
        }
        if (str_starts_with(strtoupper($code), 'TICKET') || preg_match('/^[A-Z]+-\d{4,}$/i', $code)) {
            return $this->resolveTicket($code);
        }

        return view('verification.show')->withErrors(['code' => 'Unrecognised verification code.']);
    }

    protected function resolveReceipt(string $receiptNo)
    {
        // receiptNo "RCP-0001" -> entry_no "JE-0001"
        $entryNo = 'JE-'.ltrim(mb_substr($receiptNo, 4), '-');

        $entry = JournalEntry::with('lines.account')->where('entry_no', $entryNo)->first();

        if (! $entry) {
            return view('verification.show', [
                'type' => 'receipt',
                'ref' => $receiptNo,
            ])->withErrors(['code' => 'Receipt not found. Please check the number and try again.']);
        }

        $amount = max(
            (float) $entry->lines->sum('debit'),
            (float) $entry->lines->sum('credit')
        );

        $confirmed = $entry->status === 'posted';

        AuditLog::record(
            'Verified receipt',
            'Verification',
            $receiptNo.' — '.$entry->entry_no.' ('.($confirmed ? 'valid' : 'not found/invalid').')'
        );

        return view('verification.show', [
            'type' => 'receipt',
            'ref' => $receiptNo,
            'confirmed' => $confirmed,
            'entry' => $entry,
            'amount' => $amount,
            'org' => Setting::get('org.name', 'Open Gate Camp Mission'),
        ]);
    }

    protected function resolveTicket(string $ticketNo)
    {
        $attendee = EventAttendee::with('event')->where('ticket_no', $ticketNo)->first();

        if (! $attendee) {
            return view('verification.show', [
                'type' => 'ticket',
                'ref' => $ticketNo,
            ])->withErrors(['code' => 'Ticket not found. Please check the number and try again.']);
        }

        $attendee->loadMissing('event');
        $confirmed = $attendee->hasCompletedContribution();

        AuditLog::record(
            'Verified ticket',
            'Verification',
            $ticketNo.' — '.$attendee->name.' ('.($confirmed ? 'valid' : 'incomplete').')'
        );

        return view('verification.show', [
            'type' => 'ticket',
            'ref' => $ticketNo,
            'confirmed' => $confirmed,
            'attendee' => $attendee,
            'org' => Setting::get('org.name', 'Open Gate Camp Mission'),
        ]);
    }
}