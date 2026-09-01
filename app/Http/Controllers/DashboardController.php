<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Pledge;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();

        $stats = [
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('start_date', '>=', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'totalRegistrations' => EventAttendee::count(),
            'confirmedAttendees' => EventAttendee::whereIn('status', ['confirmed', 'attended'])->count(),
            'attended' => EventAttendee::where('status', 'attended')->count(),
            'pendingConfirmations' => EventAttendee::where('status', 'pending')->count(),
        ];

        $pledgeTotals = Pledge::select(DB::raw('COALESCE(SUM(amount),0) as pledged, COALESCE(SUM(paid_amount),0) as paid'))->first();
        $pledgeTotals->outstanding = $pledgeTotals->pledged - $pledgeTotals->paid;

        $budgetTotals = Budget::select(DB::raw('COALESCE(SUM(amount),0) as budget_total'))->first();

        $monthlySeries = EventAttendee::selectRaw("DATE_FORMAT(registered_on, '%b') as m, COUNT(*) as c")
            ->where('registered_on', '>=', now()->subMonths(5)->startOfMonth())
            ->where('registered_on', '<', now()->startOfDay())
            ->groupBy(DB::raw("DATE_FORMAT(registered_on, '%Y-%m')"), 'm')
            ->orderBy(DB::raw("DATE_FORMAT(registered_on, '%Y-%m')"))
            ->pluck('c', 'm')->toArray();

        $pledgeByEvent = Pledge::select('event_id', DB::raw('COALESCE(SUM(amount),0) as t'))
            ->whereNotNull('event_id')->groupBy('event_id')->orderByDesc('t')->limit(6)->get();

        $upcoming = Event::withCount('attendees')
            ->where('start_date', '>=', $today)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('start_date')->take(5)->get();

        $recent = Event::withCount('attendees')->latest('created_at')->take(5)->get();

        return view('dashboard.index', [
            'stats' => $stats,
            'pledgeTotals' => $pledgeTotals,
            'budgetTotal' => $budgetTotals->budget_total,
            'monthlySeries' => $monthlySeries,
            'pledgeByEvent' => $pledgeByEvent,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'today' => $today,
        ]);
    }
}