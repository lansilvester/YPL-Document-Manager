<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'audience' => $this->audience,
            'is_staff' => $this->isStaff(),
            'is_admin' => $this->isAdmin(),
            'is_super_admin' => $this->isSuperAdmin(),
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'academic_info' => $this->academic_info,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'wa_number' => $this->wa_number,
            'ypl_verified' => (bool) $this->ypl_verified,
            'created_at' => $this->created_at?->toIso8601String(),
            'counts' => $this->whenHas('documents_count', fn () => [
                'documents' => $this->documents_count,
                'bookmarks' => $this->bookmarks_count,
            ]),
        ];
    }
}
