<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Event;
use App\Models\Role;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();
        $role = Role::for($user);

        $latest = Document::query()->where('visibility', 'public')
            ->whereIn('status', Document::LIVE_STATUSES)
            ->select(['id', 'title', 'slug', 'version', 'category_id', 'created_at'])
            ->with('category:id,name')
            ->latest()->limit(3)->get();

        // YPL Today: dokumen aktif dengan review terdekat (deadline nyata).
        $todayDocs = Document::query()->where('visibility', 'public')
            ->where('status', 'active')->whereNotNull('review_at')
            ->select(['id', 'title', 'slug', 'review_at'])
            ->orderBy('review_at')->limit(3)->get();

        // UNTUK ANDA: agenda terdekat + (login) pengajuan + (operasional) review jatuh tempo.
        $forYou = ['events' => Event::query()
            ->when(! $user, fn ($w) => $w->where('visibility', 'public'))
            ->whereNotNull('starts_at')->where('starts_at', '>=', now())
            ->orderBy('starts_at')->limit(3)->get()];
        if ($user) {
            $forYou['pendingServices'] = ServiceRequest::where('user_id', $user->id)
                ->where('status', 'pending')->count();
        }
        if ($role->isOperational()) {
            $forYou['dueReviews'] = Document::query()->where('visibility', 'public')
                ->where('status', 'active')->whereNotNull('review_at')
                ->where('review_at', '<=', now()->addDays(30))
                ->select(['id', 'title', 'slug', 'review_at'])
                ->orderBy('review_at')->limit(3)->get();
        }

        return view('home', [
            'role' => $role,
            'forYou' => $forYou,
            'latestDocs' => $latest,
            'todayDocs' => $todayDocs,
            'q' => $q,
            'stats' => Cache::remember('home_public_stats', 300, fn () => [
                'dokumen' => Document::where('visibility', 'public')->whereIn('status', Document::LIVE_STATUSES)->count(),
                'kategori' => Category::count(),
                'views' => (int) Document::where('visibility', 'public')->whereIn('status', Document::LIVE_STATUSES)->sum('views_count'),
            ]),
        ]);
    }
}
