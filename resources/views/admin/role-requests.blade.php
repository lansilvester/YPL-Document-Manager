@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Persetujuan Peran')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Persetujuan Peran']]])
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#001c55,#0046c0)">@include('components.icon', ['name' => 'shield', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="page-title">Persetujuan Peningkatan Peran</h1>
    <p class="text-sm text-slate-500">Staff dapat disetujui Admin. Peran admin hanya oleh Super Admin.</p>
  </div>
</div>
<div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm mt-5 overflow-hidden">
  <div class="divide-y divide-slate-100">
    @forelse($requests as $r)
      <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-52">
          <div class="font-bold">{{ $r->user->name }} <span class="font-normal text-slate-400 text-xs">{{ $r->user->email }}</span></div>
          <div class="text-xs text-slate-500 mt-0.5">Minta peran <strong class="text-[#002f80]">{{ $r->requested_role }}</strong> · {{ $r->created_at->format('d M Y H:i') }}</div>
          @if($r->reason)<div class="text-[13px] text-slate-600 mt-1">“{{ $r->reason }}”</div>@endif
          @if($r->status !== 'pending')<div class="text-xs mt-1">Status: <strong>{{ $r->status }}</strong> oleh {{ $r->reviewer->name ?? '—' }}</div>@endif
        </div>
        @if($r->status === 'pending')
          <form method="post" action="{{ route('admin.approvals.approve', $r) }}">@csrf<button class="text-[13px] font-bold bg-[#002f80] text-white rounded-full px-4 py-1.5">Setujui</button></form>
          <form method="post" action="{{ route('admin.approvals.reject', $r) }}">@csrf<button class="text-[13px] font-bold border border-[#c90018]/30 text-[#c90018] rounded-full px-4 py-1.5">Tolak</button></form>
        @endif
      </div>
    @empty
      <p class="p-8 text-center text-slate-400 text-sm">Tidak ada pengajuan.</p>
    @endforelse
  </div>
  <div class="p-4 border-t border-slate-100">{{ $requests->links() }}</div>
</div>
@endsection
