@extends('layout')
@section('title', 'Tata Kelola')
@section('content')
<p class="text-xs font-bold tracking-widest text-[#c90018] mt-2">GOVERNANCE</p>
<h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80]">Policy, SOP & Document Control</h1>
<p class="text-sm text-slate-500 mt-1">Lifecycle DRAFT → REVIEW → APPROVAL → ACTIVE → REVIEW DUE → ARCHIVED, dengan Document Passport per dokumen.</p>

<div class="flex gap-2 mt-4 text-[13px] font-semibold overflow-x-auto pb-1">
  <a href="{{ route('governance.index') }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ !$activeType ? 'bg-[#002f80] text-white border-[#002f80]' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">Semua tipe</a>
  @foreach(\App\Models\Document::TYPES as $t)
    <a href="{{ route('governance.index', ['tipe' => $t, 'status' => $activeStatus]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $activeType === $t ? 'bg-[#002f80] text-white border-[#002f80]' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">{{ strtoupper(str_replace('_', ' ', $t)) }}{{ isset($typeCounts[$t]) ? ' · '.$typeCounts[$t] : '' }}</a>
  @endforeach
</div>
<div class="flex gap-2 mt-2 text-xs font-bold overflow-x-auto pb-1">
  <a href="{{ route('governance.index', ['tipe' => $activeType]) }}" class="shrink-0 px-3 py-1 rounded-full border transition {{ !$activeStatus ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-[#d6d6d6]' }}">Semua status</a>
  @foreach(\App\Models\Document::STATUSES as $s)
    <a href="{{ route('governance.index', ['tipe' => $activeType, 'status' => $s]) }}" class="shrink-0 px-3 py-1 rounded-full border transition {{ $activeStatus === $s ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-[#d6d6d6]' }}">{{ strtoupper(str_replace('_', ' ', $s)) }}{{ isset($statusCounts[$s]) ? ' · '.$statusCounts[$s] : '' }}</a>
  @endforeach
</div>
<form action="{{ route('governance.index') }}" method="get" class="flex flex-wrap items-center gap-2 mt-3">
  <input type="hidden" name="tipe" value="{{ $activeType }}">
  <input type="hidden" name="status" value="{{ $activeStatus }}">
  <select name="audiens" class="field !text-xs !w-auto !rounded-full" aria-label="Filter audience" onchange="this.form.submit()">
    <option value="">Semua audience</option>
    @foreach(\App\Models\Document::AUDIENCES as $a)
      <option value="{{ $a }}" {{ $activeAudience === $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
    @endforeach
  </select>
  <span class="flex gap-1 bg-white border border-[#d6d6d6] rounded-full p-1">
    <input name="unit" value="{{ $activeUnit }}" placeholder="Cari unit…" class="bg-transparent text-xs px-3 outline-none w-32" aria-label="Cari unit">
    <button class="rounded-full bg-[#002f80] text-white text-xs font-bold px-4 py-1.5">Cari</button>
  </span>
  @if($activeAudience || $activeUnit)<a href="{{ route('governance.index') }}" class="text-xs font-bold text-[#c90018]">Reset ✕</a>@endif
</form>

<div class="grid sm:grid-cols-2 gap-4 mt-5">
  @forelse($docs as $d)
    <a href="{{ route('documents.show', $d) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block">
      <div class="flex flex-wrap gap-1.5 text-[10px] font-bold">
        <span class="bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full">{{ $d->doc_type ? strtoupper(str_replace('_', ' ', $d->doc_type)) : 'NO TYPE' }}</span>
        <span class="bg-green-50 text-green-700 px-2 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $d->status)) }}</span>
        <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-full">v{{ $d->version }}</span>
      </div>
      <div class="font-bold text-slate-900 mt-2 leading-snug line-clamp-2">{{ $d->title }}</div>
      <div class="text-[11px] text-slate-400 mt-1">{{ $d->doc_code ?? '—' }} · {{ $d->category->name ?? 'Tanpa kategori' }}</div>
    </a>
  @empty
    <p class="text-sm text-slate-400 sm:col-span-2 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada dokumen governance pada filter ini.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
