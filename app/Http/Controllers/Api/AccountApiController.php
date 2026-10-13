<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DocumentResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Bookmark;
use App\Models\Document;
use App\Models\Like;
use App\Models\ReadingNote;
use App\Models\RoleRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountApiController extends ApiController
{
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'wa_number' => ['nullable', 'string', 'max:25'],
        ]);

        $wa = null;
        if (! empty($data['wa_number'])) {
            $wa = WaNumber::normalize($data['wa_number']);
            if (! $wa) {
                throw ValidationException::withMessages([
                    'wa_number' => ['Nomor WA tidak dikenali. Contoh: 089612345678 atau +6289612345678.'],
                ]);
            }
        }

        $user = $request->user();
        $user->fill([
            'name' => $data['name'],
            'academic_info' => $data['academic_info'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'wa_number' => $wa,
        ])->save();

        return $this->ok(new UserResource($user), 'Profil diperbarui.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        ActivityLog::record($request->user()->id, null, 'password_change', ['via' => 'api']);

        return $this->ok(null, 'Kata sandi berhasil diganti.');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'uploads' => Document::where('user_id', $user->id)->count(),
            'views' => Document::where('user_id', $user->id)->sum('views_count'),
            'bookmarks' => Bookmark::where('user_id', $user->id)->count(),
            'read' => ActivityLog::where('user_id', $user->id)->where('action', 'view')->distinct('document_id')->count('document_id'),
        ]);
    }

    public function requestRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requested_role' => ['nullable', 'in:staff,admin'],
            'requested_audience' => ['nullable', Rule::in(User::AUDIENCES)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['requested_role']) === empty($data['requested_audience'])) {
            throw ValidationException::withMessages(['requested_role' => ['Pilih salah satu: peran atau audiens.']]);
        }

        $user = $request->user();
        if (! empty($data['requested_role']) && in_array($user->role, ['staff', 'admin', 'superadmin'], true)) {
            throw ValidationException::withMessages(['requested_role' => ['Peranmu sudah setara atau lebih tinggi.']]);
        }
        if (! empty($data['requested_audience']) && $user->audience === $data['requested_audience']) {
            throw ValidationException::withMessages(['requested_audience' => ['Audiensmu sudah '.$data['requested_audience'].'.']]);
        }
        if (RoleRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['requested_role' => ['Kamu masih memiliki pengajuan yang menunggu ditinjau.']]);
        }

        $roleRequest = RoleRequest::create([
            'user_id' => $user->id,
            'requested_role' => $data['requested_role'] ?? null,
            'requested_audience' => $data['requested_audience'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return $this->created(['id' => $roleRequest->id, 'status' => 'pending'], 'Pengajuan dikirim.');
    }

    public function bookmarks(Request $request): JsonResponse
    {
        abort_unless(Setting::feature('bookmark'), 403, 'Fitur bookmark dinonaktifkan.');
        $docs = Document::query()->forCard()
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()->paginate(12)->withQueryString();

        return $this->ok(DocumentResource::collection($docs));
    }

    public function toggleBookmark(Request $request, Document $document): JsonResponse
    {
        abort_unless(Setting::feature('bookmark'), 403, 'Fitur bookmark dinonaktifkan.');
        abort_unless($document->isVisibleTo($request->user()), 403);

        $added = DB::transaction(function () use ($document, $request) {
            $bookmark = Bookmark::where('user_id', $request->user()->id)
                ->where('document_id', $document->id)->lockForUpdate()->first();
            if ($bookmark) {
                $bookmark->delete();

                return false;
            }
            Bookmark::create(['user_id' => $request->user()->id, 'document_id' => $document->id]);

            return true;
        });

        if ($added) {
            ActivityLog::record($request->user()->id, $document->id, 'bookmark');
        }

        return $this->ok(['bookmarked' => $added], $added ? 'Ditambahkan ke bookmark.' : 'Dihapus dari bookmark.');
    }

    public function toggleLike(Request $request, Document $document): JsonResponse
    {
        abort_unless(Setting::feature('like'), 403, 'Fitur like dinonaktifkan.');
        abort_unless($document->isVisibleTo($request->user()), 403);

        $added = DB::transaction(function () use ($document, $request) {
            $like = Like::where('user_id', $request->user()->id)
                ->where('document_id', $document->id)->lockForUpdate()->first();
            if ($like) {
                $like->delete();

                return false;
            }
            Like::create(['user_id' => $request->user()->id, 'document_id' => $document->id]);

            return true;
        });

        if ($added) {
            ActivityLog::record($request->user()->id, $document->id, 'like');
        }

        return $this->ok([
            'liked' => $added,
            'likes_count' => $document->likes()->count(),
        ], $added ? 'Kamu menyukai dokumen ini.' : 'Batal menyukai dokumen.');
    }

    public function notes(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403);

        $notes = ReadingNote::where('user_id', $request->user()->id)
            ->where('document_id', $document->id)->orderBy('page')->get();

        return $this->ok($notes->map(fn (ReadingNote $n) => [
            'id' => $n->id, 'page' => $n->page, 'body' => $n->body,
            'created_at' => $n->created_at->toIso8601String(),
        ])->values());
    }

    public function storeNote(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403);

        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:5000'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $note = ReadingNote::create([
            'user_id' => $request->user()->id,
            'document_id' => $document->id,
            'page' => $data['page'],
            'body' => $data['body'],
        ]);
        ActivityLog::record($request->user()->id, $document->id, 'note', ['page' => $note->page]);

        return $this->created([
            'id' => $note->id, 'page' => $note->page, 'body' => $note->body,
            'created_at' => $note->created_at->toIso8601String(),
        ], 'Catatan tersimpan.');
    }

    public function destroyNote(Request $request, Document $document, ReadingNote $note): JsonResponse
    {
        abort_unless($note->document_id === $document->id && $note->user_id === $request->user()->id, 404);
        $note->delete();

        return $this->ok(null, 'Catatan dihapus.');
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);

        return $this->ok([
            'data' => collect($notifications->items())->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'read' => $n->read_at !== null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function readNotification(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->ok(['url' => $notification->data['url'] ?? null], 'Notifikasi ditandai terbaca.');
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->ok(null, 'Semua notifikasi ditandai terbaca.');
    }
}
