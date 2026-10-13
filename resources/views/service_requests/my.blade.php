@extends('layout')
@section('title', 'Permintaan Saya')
@section('content')
<h1 class="page-title mt-4">Permintaan Saya</h1>
<p class="text-sm text-slate-500">Alur: pending → approved/rejected → done.</p>
<div class="space-y-3 mt-4">
  @forelse($reqs as $r)
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-4">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] px-2.5 py-1 rounded-full">{{ strtoupper($r->label()) }}</span>
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $r->status === 'approved' || $r->status === 'done' ? 'bg-green-50 text-green-700' : ($r->status === 'rejected' ? 'bg-red-50 text-[var(--ypl-red)]' : 'bg-amber-50 text-amber-700') }}">{{ strtoupper($r->status) }}</span>
        <span class="text-[11px] text-slate-400 ml-auto">{{ $r->created_at->format('d M Y') }}</span>
      </div>
      <div class="font-bold text-slate-900 mt-1.5">{{ $r->title }}</div>
      @if($r->description)<p class="text-sm text-slate-500 mt-0.5">{{ $r->description }}</p>@endif
      @if($r->handled_note)<p class="text-xs mt-2 bg-slate-50 rounded-xl px-3 py-2">Catatan staff: {{ $r->handled_note }}</p>@endif
    </div>
  @empty
    <div class="bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-10 text-center text-slate-400">
      <p class="font-bold text-slate-700">Belum ada pengajuan</p>
      <p class="text-sm">Mulai dari <a href="{{ route('services.index') }}" class="text-[var(--ypl-navy)] underline font-semibold">katalog layanan</a>.</p>
    </div>
  @endforelse
</div>
<div class="mt-5">{{ $reqs->links() }}</div>
@endsection
