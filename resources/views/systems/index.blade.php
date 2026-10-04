@extends('layout')
@section('title', 'Sistem YPL')
@section('content')
<p class="text-xs font-bold tracking-widest text-[#c90018] mt-2">KNOWLEDGE GRAPH · SYSTEMS</p>
<h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80]">Sistem Operasional YPL</h1>
<p class="text-sm text-slate-500 mt-1">Simpul knowledge graph: dokumen governance tertaut ke sistem yang menjalankannya.</p>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
  @foreach($systems as $s)
    <a href="{{ route('systems.show', $s) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 block">
      <div class="font-extrabold text-slate-900">{{ $s->name }}</div>
      <div class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ $s->description }}</div>
      <div class="text-xs font-bold text-[#002f80] mt-3">{{ $s->documents_count }} dokumen terkait →</div>
    </a>
  @endforeach
</div>
@endsection
