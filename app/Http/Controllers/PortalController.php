<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function services(): mixed
    {
        $forms = Document::query()->with(['category'])
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->where('doc_type', 'form')
            ->latest()->limit(6)->get();

        $myPending = auth()->check()
            ? ServiceRequest::where('user_id', auth()->id())->where('status', 'pending')->count()
            : 0;

        return view('portal.services', [
            'types' => ServiceRequest::TYPES,
            'forms' => $forms,
            'myPending' => $myPending,
        ]);
    }

    /**
     * Community nyata (BAB 3/17): direktori orang, unit, peran, FAQ komunitas.
     * Data orang hanya untuk login (privasi, sejajar /cari).
     */
    public function community(): mixed
    {
        $user = auth()->user();

        $units = Document::query()->visibleTo($user)
            ->whereNotNull('unit')->where('unit', '!=', '')
            ->selectRaw('unit, COUNT(*) as total')->groupBy('unit')->orderBy('unit')->get();

        $people = $user
            ? User::query()->select(['id', 'name', 'role', 'academic_info'])->orderBy('name')->paginate(12)
            : null;

        return view('portal.community', [
            'units' => $units,
            'people' => $people,
            'roles' => Role::orderBy('priority')->get(['slug', 'name', 'description']),
            'faqs' => Faq::query()->when(! $user, fn ($q) => $q->where('visibility', 'public'))
                ->orderBy('sort_order')->limit(6)->get(),
        ]);
    }

    /**
     * Calendar nyata (BAB 3/5): agenda per kategori + YPL Today (deadline review).
     */
    public function calendar(Request $request): mixed
    {
        $data = $request->validate(['category' => ['nullable', 'string', 'in:foundation,academic,boarding,event,deadline']]);
        $user = auth()->user();

        $events = Event::query()->when(! $user, fn ($q) => $q->where('visibility', 'public'))
            ->when($data['category'] ?? null, fn ($q, $cat) => $q->where('category', $cat))
            ->orderBy('starts_at')->paginate(12)->withQueryString();

        $todayDocs = Document::with(['category'])->where('visibility', 'public')
            ->where('status', 'active')->whereNotNull('review_at')
            ->orderBy('review_at')->limit(5)->get();

        return view('portal.calendar', [
            'events' => $events,
            'todayDocs' => $todayDocs,
            'category' => $data['category'] ?? null,
            'categories' => Event::CATEGORIES,
        ]);
    }

    /**
     * YPL Insight publik (BAB 3): etalase koleksi publik + statistik.
     */
    public function insight(): mixed
    {
        $docs = Document::with(['category', 'user', 'tags'])->where('visibility', 'public')
            ->where('status', 'active')->latest()->limit(6)->get();

        return view('portal.insight', [
            'docs' => $docs,
            'stats' => [
                'dokumen' => Document::where('visibility', 'public')->count(),
                'views' => (int) Document::where('visibility', 'public')->sum('views_count'),
                'agenda' => Event::where('visibility', 'public')->count(),
                'faq' => Faq::where('visibility', 'public')->count(),
            ],
        ]);
    }
}
