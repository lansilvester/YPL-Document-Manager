<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnitController extends Controller
{
    public function index(): mixed
    {
        return view('admin.units', [
            'units' => Unit::withCount('contacts')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $unit = Unit::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        ActivityLog::record(auth()->id(), null, 'unit_create', ['unit' => $unit->name]);

        return back()->with('ok', "Unit {$unit->name} ditambahkan.");
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $unit->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        ActivityLog::record(auth()->id(), null, 'unit_update', ['unit' => $unit->name]);

        return back()->with('ok', "Unit {$unit->name} diperbarui.");
    }

    public function destroy(Unit $unit)
    {
        ActivityLog::record(auth()->id(), null, 'unit_delete', ['unit' => $unit->name]);
        $unit->delete();

        return back()->with('ok', 'Unit dihapus.');
    }
}
