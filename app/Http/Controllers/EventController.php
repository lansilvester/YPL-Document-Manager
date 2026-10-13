<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(): mixed
    {
        return view('staff.events', [
            'events' => Event::query()->orderBy('starts_at')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $event = Event::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'location' => $data['location'] ?? null,
            'visibility' => $data['visibility'],
        ]);
        ActivityLog::record(auth()->id(), null, 'event_create', ['event' => $event->title]);

        return back()->with('ok', "Agenda {$event->title} ditambahkan.");
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request);

        // Slug dipertahankan agar URL kalender stabil.
        $event->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'location' => $data['location'] ?? null,
            'visibility' => $data['visibility'],
        ]);
        ActivityLog::record(auth()->id(), null, 'event_update', ['event' => $event->title]);

        return back()->with('ok', "Agenda {$event->title} diperbarui.");
    }

    public function destroy(Event $event)
    {
        ActivityLog::record(auth()->id(), null, 'event_delete', ['event' => $event->title]);
        $event->delete();

        return back()->with('ok', 'Agenda dihapus.');
    }

    public function show(Event $event): mixed
    {
        abort_unless($event->isVisibleTo(auth()->user()), 404);

        $docs = Document::query()->forCard()
            ->whereIn('documents.id', $event->documents()->select('documents.id'))
            ->visibleTo(auth()->user())->latest()->paginate(12);

        return view('events.show', compact('event', 'docs'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(Event::CATEGORIES)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:120'],
            'visibility' => ['required', Rule::in(['public', 'internal'])],
        ]);
    }
}
