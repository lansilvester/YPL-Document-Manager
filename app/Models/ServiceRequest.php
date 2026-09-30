<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'description', 'needed_at',
        'status', 'handler_id', 'handled_note',
    ];

    protected function casts(): array
    {
        return ['needed_at' => 'date'];
    }

    public const TYPES = [
        'transportation' => 'Transportasi',
        'leave' => 'Cuti & HR',
        'maintenance' => 'Pemeliharaan',
        'student_permission' => 'Izin Siswa',
        'purchase' => 'Pengadaan',
        'event' => 'Acara',
        'incident' => 'Laporan Insiden',
        'submission' => 'Pengajuan Dokumen',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected', 'done'];

    public const TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['done'],
        'rejected' => ['pending'],
        'done' => [],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    public function label(): string
    {
        return static::TYPES[$this->type] ?? $this->type;
    }
}
