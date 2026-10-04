<?php

namespace App\Http\Controllers;

use App\Models\System;

class SystemController extends Controller
{
    public function index(): mixed
    {
        $systems = System::withCount('documents')->orderBy('name')->get();

        return view('systems.index', compact('systems'));
    }

    public function show(System $system): mixed
    {
        $docs = $system->documents()->with(['category', 'user'])
            ->where('visibility', 'public')->latest()->paginate(12);

        return view('systems.show', compact('system', 'docs'));
    }
}
