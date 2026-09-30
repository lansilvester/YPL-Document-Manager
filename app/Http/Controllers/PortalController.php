<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function governance(Request $request): mixed
    {
        $type = $request->query('tipe');
        $status = $request->query('status');

        $docs = Document::query()->with(['category', 'user'])
            ->where('visibility', 'public')
            ->when($type, fn ($q) => $q->where('doc_type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()->paginate(12)->withQueryString();

        $typeCounts = Document::query()->where('visibility', 'public')
            ->selectRaw('doc_type, COUNT(*) as c')->groupBy('doc_type')->pluck('c', 'doc_type');
        $statusCounts = Document::query()->where('visibility', 'public')
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('portal.governance', [
            'docs' => $docs,
            'activeType' => $type,
            'activeStatus' => $status,
            'typeCounts' => $typeCounts,
            'statusCounts' => $statusCounts,
        ]);
    }

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

    public function community(): mixed
    {
        return view('portal.placeholder', [
            'eyebrow' => 'COMMUNITY',
            'title' => 'Komunitas YPL',
            'desc' => 'Direktori unit, peran, dan kontak PIC. Knowledge berbasis orang (Phase 4).',
            'actions' => [],
        ]);
    }

    public function calendar(): mixed
    {
        return view('portal.placeholder', [
            'eyebrow' => 'CALENDAR',
            'title' => 'Kalender YPL',
            'desc' => 'Foundation, akademik, asrama, event, dan deadline penting. YPL Today tampil di beranda.',
            'actions' => [],
        ]);
    }

    public function ask(): mixed
    {
        return view('portal.placeholder', [
            'eyebrow' => 'ASK YPL',
            'title' => 'Ask YPL',
            'desc' => 'Asisten knowledge berbasis sumber resmi dengan sitasi. Hadir penuh di Phase 5 — untuk sekarang gunakan pencarian.',
            'actions' => [
                ['label' => 'Cari Knowledge', 'href' => route('home'), 'primary' => true],
            ],
        ]);
    }

    public function insight(): mixed
    {
        return view('portal.placeholder', [
            'eyebrow' => 'PUBLIC',
            'title' => 'YPL Insight',
            'desc' => 'Profil institusi, program, fasilitas, prestasi, dan publikasi untuk publik.',
            'actions' => [
                ['label' => 'Lihat Koleksi Publik', 'href' => route('library.index', ['koleksi' => 'public']), 'primary' => true],
            ],
        ]);
    }
}
