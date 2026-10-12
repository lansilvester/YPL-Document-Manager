<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Notifications\GovernanceNotification;
use Illuminate\Support\Collection;

/**
 * Penyalur notifikasi governance (BAB 13) — satu tempat untuk menentukan
 * penerima & mengirim, agar perintah/controller tetap ringkas dan konsisten.
 */
class GovernanceNotifier
{
    /**
     * Pihak yang bertanggung jawab atas dokumen: PIC, approver, dan pemilik.
     * Deduplikasi berdasarkan id; user tak dikenal/null disaring.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(Document $document): Collection
    {
        return User::query()
            ->whereIn('id', array_filter([
                $document->pic_id,
                $document->approver_id,
                $document->user_id,
            ]))
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Kirim notifikasi review-due ke setiap pihak terkait.
     *
     * @return int jumlah penerima
     */
    public function notifyReviewDue(Document $document): int
    {
        return $this->send(
            $document,
            'review_due',
            'Dokumen perlu ditinjau: '.$document->title,
            'Dokumen ini telah melewati tanggal review dan berstatus "review due".',
            ['Review terakhir: '.($document->review_at?->translatedFormat('d M Y') ?? '—')],
        );
    }

    /**
     * Kirim notifikasi arsip otomatis (masa retensi terlampaui).
     *
     * @return int jumlah penerima
     */
    public function notifyArchived(Document $document): int
    {
        return $this->send(
            $document,
            'archived',
            'Dokumen diarsipkan otomatis: '.$document->title,
            'Dokumen telah melewati masa retensi dan dipindahkan ke arsip.',
            ['Masa retensi aktif: '.Document::retentionDays().' hari.'],
        );
    }

    /**
     * @param  array<int, string>  $extraLines
     */
    private function send(Document $document, string $kind, string $title, string $message, array $extraLines = []): int
    {
        $recipients = $this->recipientsFor($document);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $notification = new GovernanceNotification($document, $kind, $title, $message, $extraLines);

        foreach ($recipients as $user) {
            $user->notify($notification);
        }

        return $recipients->count();
    }
}
