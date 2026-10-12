<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AskYpl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    private const SERVICE_SUBS = [
        'transportation' => 'Fleet request',
        'leave' => 'HR service',
        'maintenance' => 'Facility service',
        'student_permission' => 'Boarding service',
        'purchase' => 'Finance / Procurement',
        'event' => 'Event / permission',
        'incident' => 'Safety service',
        'submission' => 'Records service',
    ];

    public function index(Request $request): mixed
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $q = trim($data['q'] ?? '');
        $user = $request->user();

        $grouped = collect();
        $services = collect();
        $people = collect();
        $contacts = collect();
        $faqs = collect();
        $events = collect();
        $ai = null;

        if ($q !== '') {
            $docs = Document::query()->with(['category'])
                ->visibleTo($user)
                ->searchAny($q)
                ->select([
                    'documents.id', 'documents.title', 'documents.slug',
                    'documents.category_id', 'documents.doc_type', 'documents.version',
                    'documents.status', 'documents.description',
                ])
                ->limit(12)
                ->get();
            $grouped = $docs->groupBy(fn (Document $d) => $d->doc_type ?? 'dokumen');

            $services = $this->matchServices($q);
            $ai = (new AskYpl(3))->answer($q, $user);

            $like = Document::likePattern($q);
            $faqs = Faq::query()
                ->when(! $user, fn ($w) => $w->where('visibility', 'public'))
                ->where(fn ($w) => $w->where('question', 'like', $like)->orWhere('answer', 'like', $like))
                ->orderBy('sort_order')->limit(5)->get();
            $events = Event::query()
                ->when(! $user, fn ($w) => $w->where('visibility', 'public'))
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like))
                ->orderBy('starts_at')->limit(5)->get();

            // Kontak PIC & unit bersifat publik (BAB 3/9) — tanpa gerbang login.
            $contacts = Contact::query()->with('unit:id,name,slug')
                ->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('position', 'like', $like)
                        ->orWhereHas('unit', fn ($u) => $u->where('name', 'like', $like));
                })
                ->orderBy('sort_order')->orderBy('name')->limit(6)->get();

            if ($user) {
                $people = User::query()
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('academic_info', 'like', $like))
                    ->orderBy('name')->limit(5)->get(['id', 'name', 'role', 'academic_info']);
            }
        }

        return view('search.index', [
            'q' => $q,
            'grouped' => $grouped,
            'services' => $services,
            'people' => $people,
            'contacts' => $contacts,
            'faqs' => $faqs,
            'events' => $events,
            'ai' => $ai,
        ]);
    }

    /** @return Collection<int, array{key: string, label: string, sub: string, score: int}> */
    private function matchServices(string $q): Collection
    {
        $keywords = collect(preg_split('/\s+/u', mb_strtolower($q)) ?: [])
            ->map(fn ($w) => trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $w)))
            ->filter(fn ($w) => mb_strlen($w) >= 3)
            ->unique()->values();

        if ($keywords->isEmpty()) {
            return collect();
        }

        return collect(ServiceRequest::TYPES)
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'sub' => self::SERVICE_SUBS[$key] ?? 'YPL service',
                'score' => $keywords->filter(fn ($kw) => str_contains(mb_strtolower($key.' '.$label), $kw))->count(),
            ])
            ->filter(fn ($s) => $s['score'] > 0)
            ->sortByDesc('score')->take(4)->values();
    }
}
