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
        return $user->isAdmin() || $document->user_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
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
