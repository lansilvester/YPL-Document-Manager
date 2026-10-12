@extends('layout')
@section('title', 'Komunitas YPL')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">COMMUNITY</p>
<h1 class="page-title">Komunitas YPL</h1>
<p class="text-sm text-slate-500 mt-1">Direktori orang, unit, peran, dan pengetahuan komunitas Yayasan Pendidikan Lokon.</p>

<h2 class="section-label mt-6 mb-2">UNIT ({{ $units->count() }})</h2>
<div class="flex flex-wrap gap-2">
  @forelse($units as $u)
    <a href="{{ route('governance.index', ['unit' => $u->unit]) }}" class="bg-white rounded-full border border-[#d6d6d6] px-4 py-2 text-sm font-bold hover:border-[#002f80] hover:text-[#002f80] transition">{{ $u->unit }} <span class="text-slate-400 font-semibold">· {{ $u->total }}</span></a>
  @empty
    <p class="text-sm text-slate-400">Belum ada unit tercatat pada dokumen.</p>
  @endforelse
</div>

<h2 class="section-label mt-6 mb-2">PERAN</h2>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
  @foreach($roles as $r)
    <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4">
      <div class="font-bold text-sm text-slate-900">{{ $r->name }}</div>
      @if($r->description)<div class="text-xs text-slate-500 mt-0.5">{{ $r->description }}</div>@endif
    </div>
  @endforeach
</div>

<h2 class="section-label mt-6 mb-2">DIREKTORI ORANG</h2>
@if($people)
  <div class="grid sm:grid-cols-2 gap-3">
    @foreach($people as $p)
      <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4">
        <div class="font-bold text-sm text-slate-900">{{ $p->name }}</div>
        <div class="text-[11px] text-slate-400 mt-1">{{ ucfirst(str_replace('_', ' ', $p->role)) }}{{ $p->academic_info ? ' · '.$p->academic_info : '' }}</div>
      </div>
    @endforeach
  </div>
  <div class="mt-4">{{ $people->links() }}</div>
@else
  <div class="bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-8 text-center">
    <p class="font-bold text-slate-700">Direktori orang khusus komunitas YPL</p>
    <p class="text-sm text-slate-500 mt-1"><a href="{{ route('login') }}" class="text-[#002f80] font-bold underline">Masuk</a> untuk melihat direktori.</p>
  </div>
@endif

@if($faqs->isNotEmpty())
  <h2 class="section-label mt-6 mb-2">PENGETAHUAN KOMUNITAS ({{ $faqs->count() }})</h2>
  <div class="grid sm:grid-cols-2 gap-3">
    @foreach($faqs as $f)
      <a href="{{ route('faqs.show', $f) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block group">
        <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[#002f80]">{{ $f->question }}</div>
        <div class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($f->answer, 120) }}</div>
      </a>
    @endforeach
  </div>
@endif
@endsection
