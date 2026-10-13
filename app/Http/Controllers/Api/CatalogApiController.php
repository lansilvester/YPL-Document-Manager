<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\SystemResource;
use App\Http\Resources\UnitResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\System;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogApiController extends ApiController
{
    public function categories(): JsonResponse
    {
        return $this->ok(CategoryResource::collection(
            Category::withCount('documents')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function systems(): JsonResponse
    {
        return $this->ok(SystemResource::collection(
            System::withCount('documents')->orderBy('name')->get()
        ));
    }

    public function system(System $system): JsonResponse
    {
        $system->loadCount('documents');

        return $this->ok(new SystemResource($system));
    }

    public function faqs(Request $request): JsonResponse
    {
        $user = $request->user();
        $faqs = Faq::query()
            ->when(! $user, fn ($q) => $q->where('visibility', 'public'))
            ->orderBy('sort_order')->get();

        return $this->ok(FaqResource::collection($faqs));
    }

    public function faq(Request $request, Faq $faq): JsonResponse
    {
        abort_unless($faq->isVisibleTo($request->user()), 404);

        return $this->ok(new FaqResource($faq));
    }

    public function units(): JsonResponse
    {
        return $this->ok(UnitResource::collection(
            Unit::withCount('contacts')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function contacts(): JsonResponse
    {
        return $this->ok(ContactResource::collection(
            Contact::with('unit:id,name,slug')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['nullable', 'string', Rule::in(Event::CATEGORIES)],
        ]);
        $user = $request->user();

        $events = Event::query()
            ->when(! $user, fn ($q) => $q->where('visibility', 'public'))
            ->when($data['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->orderBy('starts_at')
            ->paginate(20)->withQueryString();

        return EventResource::collection($events)->response();
    }

    public function event(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isVisibleTo($request->user()), 404);

        return $this->ok(new EventResource($event));
    }

    public function community(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'units' => UnitResource::collection(Unit::withCount('contacts')->orderBy('sort_order')->orderBy('name')->get()),
            'contacts' => ContactResource::collection(Contact::with('unit:id,name,slug')->orderBy('sort_order')->orderBy('name')->get()),
            'people' => $user
                ? User::query()->select(['id', 'name', 'role', 'academic_info'])->orderBy('name')->limit(50)->get()
                : null,
        ]);
    }

    public function help(Request $request): JsonResponse
    {
        $user = $request->user();
        $faqs = Faq::query()
            ->when(! $user, fn ($q) => $q->where('visibility', 'public'))
            ->orderBy('sort_order')->get();

        return $this->ok($faqs->groupBy(fn ($f) => $f->category ?? 'Umum'));
    }

    public function insight(): JsonResponse
    {
        return $this->ok([
            'stats' => [
                'documents' => Document::where('visibility', 'public')->count(),
                'views' => (int) Document::where('visibility', 'public')->sum('views_count'),
                'events' => Event::where('visibility', 'public')->count(),
                'faqs' => Faq::where('visibility', 'public')->count(),
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // Staff: kelola agenda (CRUD event)
    // ------------------------------------------------------------------

    public function storeEvent(Request $request): JsonResponse
    {
        $data = $this->validateEvent($request);

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

        return $this->created(new EventResource($event), "Agenda {$event->title} ditambahkan.", route('events.show', $event));
    }

    public function updateEvent(Request $request, Event $event): JsonResponse
    {
        $data = $this->validateEvent($request);

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

        return $this->ok(new EventResource($event), "Agenda {$event->title} diperbarui.");
    }

    public function destroyEvent(Event $event): JsonResponse
    {
        ActivityLog::record(auth()->id(), null, 'event_delete', ['event' => $event->title]);
        $event->delete();

        return $this->ok(null, 'Agenda dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request): array
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
