@extends('layout')
@section('title', 'Sistem YPL')
@section('content')
<p class="page-eyebrow text-[var(--ypl-red)] mt-2">KNOWLEDGE GRAPH · SYSTEMS</p>
<h1 class="page-title">Sistem Operasional YPL</h1>
<p class="text-sm text-slate-500 mt-1">Simpul knowledge graph: dokumen governance tertaut ke sistem yang menjalankannya.</p>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
  @foreach($systems as $s)
    <a href="{{ route('systems.show', $s) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-5 block">
      <div class="font-extrabold text-slate-900">{{ $s->name }}</div>
      <div class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ $s->description }}</div>
      <div class="text-xs font-bold text-[var(--ypl-navy)] mt-3">{{ $s->documents_count }} dokumen terkait →</div>
    </a>
  @endforeach
</div>
@endsection
