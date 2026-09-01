<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Family;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index()
    {
        return view('families.index', [
            'families' => Family::withCount('members')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'head' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $family = Family::create($data);

        AuditLog::record('Created family', 'Families', $family->name);

        return redirect()->route('families.index')->with('success', "Family {$family->name} created successfully.");
    }

    public function update(Request $request, Family $family)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'head' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $family->update($data);

        AuditLog::record('Updated family', 'Families', $family->name);

        return redirect()->route('families.index')->with('success', "Family {$family->name} updated successfully.");
    }

    public function destroy(Family $family)
    {
        AuditLog::record('Deleted family', 'Families', $family->name);
        $name = $family->name;
        $family->delete();

        return redirect()->route('families.index')->with('success', "Family {$name} deleted successfully.");
    }
}
