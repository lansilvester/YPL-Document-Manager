@extends('layout')
@section('title', $system->name)
@section('content')
<p class="text-xs font-bold tracking-widest text-slate-500 mt-2">SYSTEMS / {{ strtoupper($system->slug) }}</p>
<h1 class="page-title mt-1">{{ $system->name }}</h1>
<p class="text-sm text-slate-500 mt-1">{{ $system->description }}</p>
<div class="flex gap-2 mt-4">
  <a href="{{ route('systems.index') }}" class="btn-ghost">← Semua Sistem</a>
  @if($system->url)<a href="{{ $system->url }}" target="_blank" rel="noopener" class="btn-primary">Buka Sistem →</a>@endif
</div>
<h2 class="text-sm font-extrabold tracking-widest text-slate-500 mt-6 mb-2">DOKUMEN TERKAIT</h2>
<div class="grid sm:grid-cols-2 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-sm text-slate-400 sm:col-span-2 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada dokumen tertaut ke sistem ini.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
