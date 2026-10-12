<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(): mixed
    {
        return view('admin.contacts', [
            'contacts' => Contact::with('unit:id,name')->orderBy('sort_order')->orderBy('name')->get(),
            'units' => Unit::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $contact = Contact::create($data);
        ActivityLog::record(auth()->id(), null, 'contact_create', ['contact' => $contact->name]);

        return back()->with('ok', "Kontak {$contact->name} ditambahkan.");
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $this->validated($request);

        $contact->update($data);
        ActivityLog::record(auth()->id(), null, 'contact_update', ['contact' => $contact->name]);

        return back()->with('ok', "Kontak {$contact->name} diperbarui.");
    }

    public function destroy(Contact $contact)
    {
        ActivityLog::record(auth()->id(), null, 'contact_delete', ['contact' => $contact->name]);
        $contact->delete();

        return back()->with('ok', 'Kontak dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['unit_id'] = $data['unit_id'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
