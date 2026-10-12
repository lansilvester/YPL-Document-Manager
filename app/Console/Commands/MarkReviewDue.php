<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\GovernanceNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('governance:mark-review-due')]
#[Description('Tandai dokumen active yang lewat review_at menjadi review_due + catat revisi')]
class MarkReviewDue extends Command
{
    public function handle(GovernanceNotifier $notifier): int
    {
        $count = 0;
        $notified = 0;

        Document::query()
            ->where('status', 'active')
            ->whereNotNull('review_at')
            ->whereDate('review_at', '<', now()->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($docs) use (&$count, &$notified, $notifier) {
                foreach ($docs as $doc) {
                    DB::transaction(function () use ($doc, &$count, &$notified, $notifier) {
                        DocumentVersion::create([
                            'document_id' => $doc->id,
                            'user_id' => null,
                            'version' => $doc->version,
                            'from_status' => 'active',
                            'to_status' => 'review_due',
                            'change_note' => 'Otomatis: melewati tanggal review.',
                            'snapshot' => $doc->snapshot(),
                        ]);
                        $doc->update(['status' => 'review_due']);
                        $notified += $notifier->notifyReviewDue($doc);
                        $count++;
                    });
                    ActivityLog::record(null, $doc->id, 'transition', ['from' => 'active', 'to' => 'review_due', 'auto' => true]);
                }
            });

        $this->info("Ditandai review_due: {$count} dokumen · notifikasi terkirim: {$notified}.");

        return self::SUCCESS;
    }
}
