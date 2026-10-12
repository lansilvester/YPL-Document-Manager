<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RecommendationEngine;
use Illuminate\Support\Collection;

/**
 * Role Dashboard (BAB 11 + BAB 20): satu templat, konten & prioritas
 * menyesuaikan peran audiens pengguna. Tidak ada situs terpisah per peran.
 */
class RoleDashboardController extends Controller
{
    private const DOC_LIMIT = 5;

    public function index()
    {
        $user = auth()->user();
        $role = $user->audienceRole();
        $audience = in_array($user->audience, User::AUDIENCES, true) ? $user->audience : null;

        $sections = match ($audience) {
            'siswa' => $this->studentSections($user, $role),
            'orang_tua' => $this->parentSections($user, $role),
            'guru' => $this->teacherSections($user, $role),
            'karyawan' => $this->employeeSections($user, $role),
            'pimpinan' => $this->leaderSections($user, $role),
            'mitra' => $this->partnerSections($user),
            'publik' => $this->publicSections($user),
            default => $this->generalSections($user, $role),
        };

        return view('dashboard.index', [
            'role' => $role,
            'audience' => $audience,
            'heading' => $audience ? $role->name : 'Anggota',
            'quick' => $role->orderedQuick(),
            'explore' => $role->scopedExplore(),
            'recommendations' => $this->recommendations($user),
            'sections' => $sections,
        ]);
    }

    // ==================== AUDIENCE LAYOUTS ====================

    private function studentSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->agendaSection(),
            $this->collectionSection('handbook', 'Handbook Siswa', 'book'),
            $this->collectionSection('academic', 'Akademik', 'cap'),
            $this->collectionSection('boarding', 'Kehidupan Asrama', 'home'),
            $this->myRequestsSection($user),
        ]));
    }

    private function parentSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->agendaSection(),
            $this->collectionSection('academic', 'Akademik Anak', 'cap'),
            $this->collectionSection('boarding', 'Asrama & Kesiswaan', 'home'),
            $this->collectionSection('parent', 'Informasi Orang Tua', 'users'),
            $this->formsSection(),
            $this->myRequestsSection($user),
        ]));
    }

    private function teacherSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->agendaSection(),
            $this->collectionSection('academic', 'Akademik & Kurikulum', 'cap'),
            $this->collectionSection('governance', 'Policy & SOP', 'shield'),
            $this->collectionSection('staff', 'Pengembangan & Layanan Internal', 'tool'),
            $this->myRequestsSection($user),
        ]));
    }

    private function employeeSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->agendaSection(),
            $this->collectionSection('staff', 'HR & Layanan Internal', 'tool'),
            $this->collectionSection('governance', 'Policy & SOP', 'shield'),
            $this->formsSection(),
            $this->myRequestsSection($user),
        ]));
    }

    /**
     * Pimpinan: ringkasan operasional (due review, approval, layanan) + governance.
     */
    private function leaderSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->overviewSection(),
            $this->dueReviewSection(),
            $this->approvalsSection($user),
            $this->pendingServicesSection($user),
            $this->collectionSection('governance', 'Governance Library', 'shield'),
        ]));
    }

    private function generalSections(User $user, Role $role): array
    {
        return array_values(array_filter([
            $this->agendaSection(),
            $this->latestSection(),
            $this->myRequestsSection($user),
        ]));
    }

    /**
     * Mitra: buku panduan mitra, kontak, informasi kolaborasi, resource publik (BAB 11).
     */
    private function partnerSections(User $user): array
    {
        return array_values(array_filter([
            $this->collectionSection('handbook', 'Panduan Institusi Mitra', 'book'),
            $this->contactsSection(),
            $this->collectionSection('governance', 'Kebijakan Publik', 'shield'),
            $this->collectionSection('public', 'Resource Publik', 'globe'),
            $this->agendaSection(),
        ]));
    }

    /**
     * Publik: profil institusi, pendidikan, program, capaian, publikasi (BAB 11).
     */
    private function publicSections(User $user): array
    {
        return array_values(array_filter([
            $this->collectionSection('public', 'Profil & Publikasi Institusi', 'globe'),
            $this->collectionSection('handbook', 'Panduan & Informasi YPL', 'book'),
            $this->collectionSection('academic', 'Pendidikan & Program', 'cap'),
            $this->collectionSection('parent', 'Informasi untuk Keluarga', 'users'),
            $this->agendaSection(),
        ]));
    }

    // ==================== GENERIC SECTIONS ====================

    private function overviewSection(): ?array
    {
        $stats = [
            ['label' => 'Dokumen Aktif', 'value' => Document::query()->where('status', 'active')->count(), 'icon' => 'file'],
            ['label' => 'Review Jatuh Tempo', 'value' => $this->dueReviewQuery()->count(), 'icon' => 'clock'],
            ['label' => 'Persetujuan Pending', 'value' => RoleRequest::pending()->count(), 'icon' => 'check'],
            ['label' => 'Layanan Pending', 'value' => ServiceRequest::where('status', 'pending')->count(), 'icon' => 'inbox'],
        ];

        return [
            'key' => 'overview',
            'title' => 'Ringkasan Operasional',
            'icon' => 'chart',
            'stats' => $stats,
            'items' => collect(),
            'empty' => null,
            'more' => null,
        ];
    }

    private function agendaSection(): ?array
    {
        $events = Event::query()
            ->where('visibility', 'public')
            ->whereNotNull('starts_at')->where('starts_at', '>=', now())
            ->orderBy('starts_at')->limit(5)->get();

        return $this->section(
            'agenda', 'Agenda Terdekat', 'clock',
            $events->map(fn (Event $e) => [
                'title' => $e->title,
                'meta' => $e->starts_at?->translatedFormat('d M Y · H:i').($e->location ? ' · '.$e->location : ''),
                'url' => route('events.show', $e),
            ]),
            'Belum ada agenda dalam waktu dekat.',
            route('calendar.index')
        );
    }

    private function latestSection(): ?array
    {
        $docs = $this->docBase(auth()->user())->latest()->limit(self::DOC_LIMIT)->get();

        return $this->section(
            'latest', 'Dokumen Terbaru', 'file',
            $this->docItems($docs),
            'Belum ada dokumen terbaru.',
            route('library.index')
        );
    }

    private function collectionSection(string $collection, string $title, string $icon): ?array
    {
        $user = auth()->user();
        $docs = $this->docBase($user)
            ->whereHas('category', fn ($q) => $q->where('collection', $collection))
            ->latest()->limit(self::DOC_LIMIT)->get();

        if ($docs->isEmpty()) {
            return null;
        }

        return $this->section(
            'collection-'.$collection,
            $title,
            $icon,
            $this->docItems($docs),
            null,
            route('library.index', ['koleksi' => $collection])
        );
    }

    private function formsSection(): ?array
    {
        $docs = $this->docBase(auth()->user())
            ->where('doc_type', 'form')->latest()->limit(self::DOC_LIMIT)->get();

        if ($docs->isEmpty()) {
            return null;
        }

        return $this->section(
            'forms', 'Formulir & Pengajuan', 'file',
            $this->docItems($docs),
            null,
            route('governance.index', ['tipe' => 'form'])
        );
    }

    private function contactsSection(): ?array
    {
        $contacts = Contact::query()->with('unit:id,name')
            ->orderBy('sort_order')->orderBy('name')->limit(self::DOC_LIMIT)->get();

        if ($contacts->isEmpty()) {
            return null;
        }

        return $this->section(
            'contacts', 'Kontak & Kolaborasi', 'users',
            $contacts->map(fn (Contact $c) => [
                'title' => $c->name,
                'meta' => trim(($c->position ?? '').($c->unit ? ' · '.$c->unit->name : ''), ' ·') ?: 'Kontak YPL',
                'badge' => null,
                'url' => route('community.index'),
            ]),
            null,
            route('community.index')
        );
    }

    private function dueReviewSection(): ?array
    {
        $docs = $this->dueReviewQuery()
            ->select(['id', 'title', 'slug', 'review_at'])
            ->orderBy('review_at')->limit(self::DOC_LIMIT)->get();

        if ($docs->isEmpty()) {
            return null;
        }

        return $this->section(
            'due-review', 'Review Jatuh Tempo', 'clock',
            $docs->map(fn (Document $d) => [
                'title' => $d->title,
                'meta' => 'Review: '.($d->review_at?->translatedFormat('d M Y') ?? '—'),
                'badge' => $d->review_at && $d->review_at->isPast() ? 'Lewat' : 'Segera',
                'url' => route('governance.show', $d),
            ]),
            null,
            route('governance.index', ['sort' => 'review_date'])
        );
    }

    private function approvalsSection(User $user): ?array
    {
        if (! $user->isStaff()) {
            return null;
        }

        $requests = RoleRequest::pending()->with('user:id,name')->latest()->limit(self::DOC_LIMIT)->get();

        if ($requests->isEmpty()) {
            return null;
        }

        return $this->section(
            'approvals', 'Persetujuan Peran Pending', 'users',
            $requests->map(fn (RoleRequest $r) => [
                'title' => $r->user->name ?? 'Pengguna',
                'meta' => 'Mengajukan: '.$r->requested_role,
                'badge' => 'Pending',
                'url' => route('admin.approvals'),
            ]),
            null,
            route('admin.approvals')
        );
    }

    private function pendingServicesSection(User $user): ?array
    {
        if (! $user->isStaff()) {
            return null;
        }

        $requests = ServiceRequest::where('status', 'pending')
            ->with('user:id,name')->latest()->limit(self::DOC_LIMIT)->get();

        if ($requests->isEmpty()) {
            return null;
        }

        return $this->section(
            'pending-services', 'Layanan Menunggu Tindakan', 'inbox',
            $requests->map(fn (ServiceRequest $s) => [
                'title' => $s->title,
                'meta' => $s->user->name ?? 'Pengguna',
                'badge' => $s->label(),
                'url' => route('staff.requests'),
            ]),
            null,
            route('staff.requests')
        );
    }

    private function myRequestsSection(User $user): ?array
    {
        $requests = ServiceRequest::where('user_id', $user->id)
            ->latest()->limit(self::DOC_LIMIT)->get();

        if ($requests->isEmpty()) {
            return null;
        }

        return $this->section(
            'my-requests', 'Permintaan Saya', 'inbox',
            $requests->map(fn (ServiceRequest $s) => [
                'title' => $s->title,
                'meta' => $s->label().' · '.$s->created_at->translatedFormat('d M Y'),
                'badge' => ucfirst($s->status),
                'url' => route('services.my'),
            ]),
            null,
            route('services.my')
        );
    }

    // ==================== HELPERS ====================

    private function docBase(User $user)
    {
        return Document::query()->forCard()->visibleTo($user)
            ->whereIn('status', Document::LIVE_STATUSES);
    }

    private function dueReviewQuery()
    {
        return Document::query()->where('visibility', 'public')
            ->where('status', 'active')->whereNotNull('review_at')
            ->where('review_at', '<=', now()->addDays(30));
    }

    /**
     * @return Collection<int, array{title: string, meta: string|null, url: string, badge?: string}>
     */
    private function docItems(Collection $docs): Collection
    {
        return $docs->map(fn (Document $d) => [
            'title' => $d->title,
            'meta' => $d->category->name ?? 'Tanpa kategori',
            'badge' => null,
            'url' => route('documents.show', $d),
        ]);
    }

    /**
     * Normalisasi satu seksi agar templat dapat merender semuanya seragam.
     */
    private function section(string $key, string $title, string $icon, Collection $items, ?string $empty, ?string $more): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'icon' => $icon,
            'items' => $items,
            'empty' => $empty,
            'more' => $more,
        ];
    }

    private function recommendations(User $user): Collection
    {
        return (new RecommendationEngine(maxResults: 4))->forUser($user);
    }
}
