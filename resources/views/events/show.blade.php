@extends('layout')
@section('title', $event->title)
@section('content')
<p class="page-eyebrow text-slate-500 mt-2">KALENDER / {{ strtoupper($event->category) }}</p>
<h1 class="page-title mt-1">{{ $event->title }}</h1>
<p class="text-sm text-slate-500 mt-1">{{ $event->description }}</p>
<div class="flex flex-wrap gap-2 mt-4 text-xs font-bold">
  @if($event->starts_at)<span class="bg-white border border-[#d6d6d6] rounded-full px-3 py-1.5">📅 {{ $event->starts_at->format('d M Y H:i') }}{{ $event->ends_at ? ' – '.$event->ends_at->format('d M Y H:i') : '' }}</span>@endif
  @if($event->location)<span class="bg-white border border-[#d6d6d6] rounded-full px-3 py-1.5">📍 {{ $event->location }}</span>@endif
</div>
<h2 class="text-sm font-extrabold tracking-widest text-slate-500 mt-6 mb-2">DOKUMEN TERKAIT</h2>
<div class="grid sm:grid-cols-2 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-sm text-slate-400 sm:col-span-2 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada dokumen tertaut ke agenda ini.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
