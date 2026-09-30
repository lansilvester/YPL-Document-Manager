<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'document_id', 'action', 'meta', 'ip'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public static function record(?int $userId, ?int $docId, string $action, ?array $meta = null): void
    {
        static::pruneIfDue();

        static::create([
            'user_id' => $userId,
            'document_id' => $docId,
            'action' => $action,
            'meta' => $meta,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Hapus log yang lebih tua dari ambang batas.
     * Digerakkan lebih awal saat akses admin (tanpa cron).
     */
    public static function prune(int $days = 180, ?Carbon $asOf = null): int
    {
        $cutoff = ($asOf ?? Carbon::now())->subDays(max(1, $days));

        return static::query()->where('created_at', '<', $cutoff)->delete();
    }

    /**
     * Pangkas berkala secara sampling saat ada aktivitas,
     * sehingga tabel tidak membengkak tanpa penjadwal di server.
     */
    protected static function pruneIfDue(): void
    {
        $key = 'activity_clean_at';
        $everyHours = max(1, (int) env('ACTIVITY_PRUNE_EVERY_HOURS', 12));
        $last = (int) Cache::get($key, 0);

        if (time() - $last < $everyHours * 3600) {
            return;
        }

        if (random_int(1, 100) <= 5) {
            static::prune((int) env('ACTIVITY_PRUNE_DAYS', 180));
            Cache::put($key, time(), now()->addDays(2));
        }
    }
}
