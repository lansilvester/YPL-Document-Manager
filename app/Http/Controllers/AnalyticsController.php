<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\ServiceRequest;

class AnalyticsController extends Controller
{
    /**
     * Analytics operasional (BAB 18/P6): agregat 30 hari, tren 14 hari,
     * dokumen teratas, layanan, dan aktivitas terakhir. Semua agregat
     * berindeks dan bounded — tanpa N+1.
     */
    public function index(): mixed
    {
        $since = now()->subDays(30);

        $byAction = ActivityLog::query()->where('created_at', '>=', $since)
            ->selectRaw('action, COUNT(*) as total')->groupBy('action')
            ->pluck('total', 'action');

        $trend = ActivityLog::query()->where('created_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')->orderBy('day')->get();

        $topDocs = Document::query()->select(['id', 'title', 'slug', 'views_count', 'downloads_count'])
            ->orderByDesc('views_count')->limit(8)->get();

        return view('staff.analytics', [
            'cards' => [
                ['Dibaca (30 hari)', (int) ($byAction['view'] ?? 0), 'eye'],
                ['Diunduh (30 hari)', (int) ($byAction['download'] ?? 0), 'download'],
                ['Pertanyaan Ask (30 hari)', (int) ($byAction['ask'] ?? 0), 'sparkles'],
                ['Unggahan (30 hari)', (int) ($byAction['upload'] ?? 0), 'upload'],
            ],
            'byAction' => $byAction,
            'trend' => $trend,
            'trendMax' => (int) ($trend->max('total') ?? 0),
            'topDocs' => $topDocs,
            'services' => ServiceRequest::query()
                ->selectRaw('status, COUNT(*) as total')->groupBy('status')
                ->pluck('total', 'status'),
            'recent' => ActivityLog::with(['user:id,name', 'document:id,title,slug'])
                ->latest()->limit(10)->get(),
        ]);
    }
}
