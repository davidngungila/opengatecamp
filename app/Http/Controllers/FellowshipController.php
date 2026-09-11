<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Fellowship;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class FellowshipController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $showInactive = $request->query('inactive') === '1';

        $fellowships = Fellowship::query()
            ->with(['leaders'])
            ->withCount(['attendees', 'attendees as confirmed_count' => fn ($qr) => $qr->whereIn('status', ['confirmed', 'attended'])])
            ->withSum('attendees', 'amount_paid')
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('university', 'like', "%{$q}%")))
            ->when(! $showInactive, fn ($qr) => $qr->active())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $users = User::with('role')->orderBy('name')->get();

        return view('fellowships.index', [
            'fellowships' => $fellowships,
            'users' => $users,
            'filters' => compact('q', 'showInactive'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $fellowship = Fellowship::create($data);

        $this->syncLeaders($fellowship, $data['leader_ids'] ?? [], $data['primary_leader_id'] ?? null);

        $this->refreshFellowshipList();

        AuditLog::record('Created fellowship', 'Fellowships', $fellowship->name);

        return back()->with('success', "Fellowship \"{$fellowship->name}\" created.");
    }

    public function update(Request $request, Fellowship $fellowship)
    {
        $data = $this->validateData($request, $fellowship);

        $fellowship->update($data);

        $this->syncLeaders($fellowship, $data['leader_ids'] ?? [], $data['primary_leader_id'] ?? null);

        $this->refreshFellowshipList();

        AuditLog::record('Updated fellowship', 'Fellowships', $fellowship->name);

        return back()->with('success', "Fellowship \"{$fellowship->name}\" updated.");
    }

    public function destroy(Request $request, Fellowship $fellowship)
    {
        if ($fellowship->attendees()->exists()) {
            return back()->with('error', "Fellowship \"{$fellowship->name}\" has registered delegates — reassign them before deleting.");
        }

        AuditLog::record('Deleted fellowship', 'Fellowships', $fellowship->name);

        $fellowship->delete();

        $this->refreshFellowshipList();

        return back()->with('success', 'Fellowship removed.');
    }

    public function members(Request $request, Fellowship $fellowship)
    {
        $attendees = $fellowship->attendees()
            ->with('event')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'registered' => $fellowship->attendees()->count(),
            'confirmed' => $fellowship->attendees()->whereIn('status', ['confirmed', 'attended'])->count(),
            'attended' => $fellowship->attendees()->where('status', 'attended')->count(),
            'paid' => (float) $fellowship->attendees()->sum('amount_paid'),
        ];

        return view('fellowships.members', [
            'fellowship' => $fellowship,
            'attendees' => $attendees,
            'stats' => $stats,
        ]);
    }

    public function apiDelegation(Request $request, Fellowship $fellowship)
    {
        $attendees = $fellowship->attendees()
            ->with('event')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'status', 'amount_paid', 'event_id', 'fellowship_id', 'created_at']);

        $stats = [
            'registered' => $attendees->count(),
            'confirmed' => $attendees->whereIn('status', ['confirmed', 'attended'])->count(),
            'attended' => $attendees->where('status', 'attended')->count(),
            'paid' => (float) $attendees->sum('amount_paid'),
        ];

        return response()->json([
            'fellowship' => [
                'id' => $fellowship->id,
                'name' => $fellowship->name,
                'university' => $fellowship->university,
                'type' => $fellowship->getTypeLabel(),
            ],
            'attendees' => $attendees->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'phone' => $a->phone ?: '—',
                'status' => $a->getStatusLabel(),
                'status_raw' => $a->status,
                'paid' => (float) $a->amount_paid,
                'event' => $a->event?->title ?? '—',
                'created_at' => $a->created_at?->format('d M Y'),
            ]),
            'stats' => $stats,
        ]);
    }

    private function validateData(Request $request, ?Fellowship $fellowship = null): array
    {
        $unique = $fellowship ? 'unique:fellowships,name,'.$fellowship->id : 'unique:fellowships,name';

        $data = $request->validate([
            'name' => 'required|string|max:255|'.$unique,
            'type' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'capacity' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'active' => 'nullable|boolean',
            'leader_ids' => 'nullable|array',
            'leader_ids.*' => 'exists:users,id',
            'primary_leader_id' => 'nullable|exists:users,id',
        ]);

        $data['active'] = $request->boolean('active');

        return $data;
    }

    private function syncLeaders(Fellowship $fellowship, array $leaderIds, ?int $primaryId): void
    {
        $leaderIds = array_values(array_unique(array_map('intval', $leaderIds)));

        $attachments = [];
        foreach ($leaderIds as $id) {
            $attachments[$id] = [
                'is_primary' => $primaryId && (int) $primaryId === $id,
                'title' => $primaryId && (int) $primaryId === $id ? 'Primary Leader' : null,
            ];
        }

        $fellowship->leaders()->sync($attachments);

        if ($primaryId && ! in_array((int) $primaryId, $leaderIds, true)) {
            // Unselect any leftover primary flag not present in the leader list.
            $fellowship->leaders()->updateExistingPivot($primaryId, ['is_primary' => false, 'title' => null]);
        }
    }

    private function refreshFellowshipList(): void
    {
        Setting::put('fellowships.list', Fellowship::orderBy('name')->pluck('name')->implode("\n"));
    }
}