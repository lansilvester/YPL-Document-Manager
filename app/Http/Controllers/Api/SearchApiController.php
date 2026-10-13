<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\FaqResource;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AskYpl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchApiController extends ApiController
{
    /** Sub-label layanan untuk pencarian (paritas web). */
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

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $q = trim($data['q'] ?? '');
        $user = $request->user();

        if ($q === '') {
            return $this->ok([
                'query' => '',
                'documents' => [],
                'services' => [],
                'people' => [],
                'contacts' => [],
                'faqs' => [],
                'events' => [],
            ]);
        }

        $docs = Document::query()->with('category')
            ->visibleTo($user)
            ->searchAny($q)
            ->select([
                'documents.id', 'documents.title', 'documents.slug', 'documents.category_id',
                'documents.doc_type', 'documents.version', 'documents.status', 'documents.description',
            ])
            ->limit(12)->get();

        $like = Document::likePattern($q);

        $faqs = Faq::query()
            ->when(! $user, fn ($w) => $w->where('visibility', 'public'))
            ->where(fn ($w) => $w->where('question', 'like', $like)->orWhere('answer', 'like', $like))
            ->orderBy('sort_order')->limit(5)->get();

        $events = Event::query()
            ->when(! $user, fn ($w) => $w->where('visibility', 'public'))
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('starts_at')->limit(5)->get();

        $contacts = Contact::query()->with('unit:id,name,slug')
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('position', 'like', $like)
                    ->orWhereHas('unit', fn ($u) => $u->where('name', 'like', $like));
            })
            ->orderBy('sort_order')->orderBy('name')->limit(6)->get();

        $people = $user
            ? User::query()
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('academic_info', 'like', $like))
                ->orderBy('name')->limit(5)->get(['id', 'name', 'role', 'academic_info'])
            : collect();

        return $this->ok([
            'query' => $q,
            'documents' => DocumentResource::collection($docs),
            'services' => $this->matchServices($q),
            'people' => $people->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'role' => $u->role, 'academic_info' => $u->academic_info,
            ])->values(),
            'contacts' => ContactResource::collection($contacts),
            'faqs' => FaqResource::collection($faqs),
            'events' => EventResource::collection($events),
        ]);
    }

    public function ask(Request $request, AskYpl $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['nullable', 'string', 'min:3', 'max:500', 'required_without:explain'],
            'doc' => ['nullable', 'string', 'max:255'],
            'explain' => ['nullable', 'boolean'],
        ]);

        $scope = isset($data['doc'])
            ? Document::query()->visibleTo($request->user())->where('slug', $data['doc'])->firstOrFail()
            : null;

        if (! empty($data['explain'])) {
            abort_unless($scope, 422, 'Mode ringkas membutuhkan konteks dokumen.');
            $result = $ask->explain($scope, $request->user());
            $question = 'Ringkas dokumen ini';
        } else {
            $question = $data['question'];
            $result = $ask->answer($question, $request->user(), $scope);
        }

        ActivityLog::record(
            $request->user()?->id, $scope?->id, 'ask',
            ['q' => mb_substr($question, 0, 100), 'found' => $result['found'], 'sources' => count($result['sources']), 'via' => 'api']
        );

        return $this->ok([
            'answer' => $result['answer'],
            'found' => $result['found'],
            'ai' => $result['ai'],
            'scope' => $scope?->slug,
            'sources' => collect($result['sources'])->map(fn (array $hit) => [
                'title' => $hit['document']->title,
                'slug' => $hit['document']->slug,
                'url' => route('documents.show', $hit['document']),
                'section' => $hit['section'] ?? null,
                'passage' => $hit['passage'],
                'score' => $hit['score'],
                'doc_type' => $hit['document']->doc_type,
                'status' => $hit['document']->status,
                'version' => (string) $hit['document']->version,
            ])->values(),
        ], $result['found'] ? 'Jawaban tersedia.' : 'Tidak ditemukan jawaban yang relevan.');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
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
