<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(?User $user, Document $document): bool
    {
        return $document->isVisibleTo($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->isStaff() || $document->user_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function transition(User $user, Document $document, string $to): bool
    {
        if (! $document->canTransitionTo($to)) {
            return false;
        }
        $isOwner = $document->user_id === $user->id;

        return match ($to) {
            'review' => $isOwner || $user->isStaff(),
            'approval' => $user->isStaff(),
            'active' => $user->isAdmin(),
            'draft' => $isOwner || $user->isStaff(),
            'review_due' => $user->isStaff(),
            'archived' => $isOwner || $user->isStaff(),
            default => false,
        };
    }

    public function relate(User $user, Document $document): bool
    {
        return $user->isStaff() || $document->user_id === $user->id;
    }

    public function download(?User $user, Document $document): bool
    {
        return $document->isVisibleTo($user);
    }

    public function embed(?User $user, Document $document): bool
    {
        return $document->isVisibleTo($user);
    }
}
