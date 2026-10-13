@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Persetujuan Peran')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Persetujuan Peran']]])
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">@include('components.icon', ['name' => 'shield', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="page-title">Persetujuan Peningkatan Peran</h1>
    <p class="text-sm text-slate-500">Staff dapat disetujui Admin. Peran admin hanya oleh Super Admin.</p>
  </div>
</div>
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm mt-5 overflow-hidden">
  <div class="divide-y divide-slate-100">
    @forelse($requests as $r)
      <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-52">
          <div class="font-bold">{{ $r->user->name }} <span class="font-normal text-slate-400 text-xs">{{ $r->user->email }}</span></div>
          <div class="text-xs text-slate-500 mt-0.5">@if($r->requested_audience) Minta audiens <strong class="text-[var(--ypl-navy)]">{{ str_replace('_', ' ', $r->requested_audience) }}</strong>@else Minta peran <strong class="text-[var(--ypl-navy)]">{{ $r->requested_role }}</strong>@endif · {{ $r->created_at->format('d M Y H:i') }}</div>
          @if($r->reason)<div class="text-[13px] text-slate-600 mt-1">“{{ $r->reason }}”</div>@endif
          @if($r->status !== 'pending')<div class="text-xs mt-1">Status: <strong>{{ $r->status }}</strong> oleh {{ $r->reviewer->name ?? '—' }}</div>@endif
        </div>
        @if($r->status === 'pending')
          <form method="post" action="{{ route('admin.approvals.approve', $r) }}">@csrf<button class="text-[13px] font-bold bg-[var(--ypl-navy)] text-white rounded-full px-4 py-1.5">Setujui</button></form>
          <form method="post" action="{{ route('admin.approvals.reject', $r) }}">@csrf<button class="text-[13px] font-bold border border-[var(--ypl-red)]/30 text-[var(--ypl-red)] rounded-full px-4 py-1.5">Tolak</button></form>
        @endif
      </div>
    @empty
      <p class="p-8 text-center text-slate-400 text-sm">Tidak ada pengajuan.</p>
    @endforelse
  </div>
  <div class="p-4 border-t border-slate-100">{{ $requests->links() }}</div>
</div>
@endsection
