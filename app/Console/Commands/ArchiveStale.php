<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('governance:archive-stale {--days= : Timpa masa retensi (hari)} {--dry-run : Tampilkan kandidat tanpa mengubah data}')]
#[Description('Arsipkan dokumen review_due yang melewati masa retensi (Archive policy BAB 13)')]
class ArchiveStale extends Command
{
    public function handle(): int
    {
        $days = $this->option('days');
        if ($days !== null) {
            $asOf = Carbon::now()->subDays(max(30, (int) $days));
        } else {
            $asOf = null;
        }

        $query = Document::query()->archiveDue($asOf)->orderBy('id');

        if ($this->option('dry-run')) {
            $count = (clone $query)->count();
            $this->info("Kandidat arsip: {$count} dokumen (dry-run, tidak ada perubahan).");

            return self::SUCCESS;
        }

        $count = 0;

        $query->chunkById(200, function ($docs) use (&$count) {
            foreach ($docs as $doc) {
                DB::transaction(function () use ($doc, &$count) {
                    $from = $doc->status;
                    $doc->status = 'archived';
                    $doc->recordVersion($from, 'Otomatis: melewati masa retensi (archive policy).');
                    $doc->save();
                    $count++;
                });
                ActivityLog::record(null, $doc->id, 'transition', ['from' => 'review_due', 'to' => 'archived', 'auto' => true]);
            }
        });

        $this->info("Diarsipkan otomatis: {$count} dokumen.");

        return self::SUCCESS;
    }
}
