<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function show(Event $event): mixed
    {
        abort_unless($event->isVisibleTo(auth()->user()), 404);

        $docs = $event->documents()->with(['category', 'user', 'tags'])
            ->visibleTo(auth()->user())->latest()->paginate(12);

        return view('events.show', compact('event', 'docs'));
    }
}
