<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ServiceRequest;

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
