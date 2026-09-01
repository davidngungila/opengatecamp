<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Pledge;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $event = Event::where('event_type', 'camp')->orderByDesc('start_date')->first()
            ?? Event::orderByDesc('start_date')->first();

        if (! $event) {
            return view('dashboard.index', ['event' => null]);
        }

        $activeStatuses = ['pending', 'confirmed', 'attended'];

        $registrations = EventAttendee::where('event_id', $event->id);

        $stats = [
            'total' => (clone $registrations)->count(),
            'confirmed' => (clone $registrations)->whereIn('status', ['confirmed', 'attended'])->count(),
            'attended' => (clone $registrations)->where('status', 'attended')->count(),
            'pending' => (clone $registrations)->where('status', 'pending')->count(),
            'no_show' => (clone $registrations)->where('status', 'no_show')->count(),
            'cancelled' => (clone $registrations)->where('status', 'cancelled')->count(),
        ];

        $feeRows = (clone $registrations)->whereIn('status', $activeStatuses);
        $feesExpected = (float) (clone $feeRows)->sum('fee_amount');
        $feesCollected = (float) (clone $feeRows)->sum('amount_paid');

        $pledgeRows = Pledge::where('event_id', $event->id)->whereIn('status', ['pending', 'partial', 'fulfilled']);
        $pledged = (float) (clone $pledgeRows)->sum('amount');
        $pledgePaid = (float) (clone $pledgeRows)->sum('paid_amount');

        $budgetTotal = (float) Budget::where('event_id', $event->id)->sum('amount') ?: (float) ($event->budget_total ?? 0);

        $latestRegistrations = (clone $registrations)->orderByDesc('registered_on')->take(6)->get();
        $latestPledges = (clone $pledgeRows)->orderByDesc('amount')->take(5)->get();
        $sessions = $event->sessions;

        $statusBreakdown = (clone $registrations)->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')->pluck('c', 'status')->toArray();

        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $trend[$d->format('M j')] = 0;
        }
        $rows = (clone $registrations)
            ->selectRaw('DATE(registered_on) as d, COUNT(*) as c')
            ->where('registered_on', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->get();
        foreach ($rows as $r) {
            $trend[Carbon::parse($r->d)->format('M j')] = (int) $r->c;
        }

        $capacity = (int) $event->capacity;
        $seatsLeft = $capacity > 0 ? max(0, $capacity - $stats['total']) : null;
        $fillPercent = $capacity > 0 ? min(100, (int) round($stats['total'] / $capacity * 100)) : null;

        return view('dashboard.index',
            compact('event', 'stats', 'feesExpected', 'feesCollected', 'pledged', 'pledgePaid',
                'budgetTotal', 'latestRegistrations', 'latestPledges', 'sessions',
                'statusBreakdown', 'trend', 'capacity', 'seatsLeft', 'fillPercent')
        );
    }
}