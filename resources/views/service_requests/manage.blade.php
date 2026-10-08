@extends('layout')
@section('title', 'Kelola Permintaan')
@section('content')
<h1 class="page-title mt-4">Kelola Permintaan Layanan</h1>
<div class="flex gap-2 mt-3 text-xs font-bold overflow-x-auto pb-1">
  <a href="{{ route('staff.requests') }}" class="shrink-0 px-3 py-1.5 rounded-full border transition {{ !$activeStatus ? 'bg-[#002f80] text-white border-[#002f80]' : 'bg-white border-[#d6d6d6]' }}">Semua{{ isset($counts) ? ' · '.$counts->sum() : '' }}</a>
  @foreach(\App\Models\ServiceRequest::STATUSES as $s)
    <a href="{{ route('staff.requests', ['status' => $s]) }}" class="shrink-0 px-3 py-1.5 rounded-full border transition {{ $activeStatus === $s ? 'bg-[#002f80] text-white border-[#002f80]' : 'bg-white border-[#d6d6d6]' }}">{{ strtoupper($s) }}{{ isset($counts[$s]) ? ' · '.$counts[$s] : '' }}</a>
  @endforeach
</div>
<div class="space-y-3 mt-4">
  @forelse($reqs as $r)
    <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4">
      <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
        <span class="bg-[#002f80]/5 text-[#002f80] px-2.5 py-1 rounded-full">{{ strtoupper($r->label()) }}</span>
        <span class="px-2.5 py-1 rounded-full {{ $r->status === 'approved' || $r->status === 'done' ? 'bg-green-50 text-green-700' : ($r->status === 'rejected' ? 'bg-red-50 text-[#c90018]' : 'bg-amber-50 text-amber-700') }}">{{ strtoupper($r->status) }}</span>
        <span class="text-slate-400 font-normal">{{ $r->user->name }} · {{ $r->created_at->format('d M Y H:i') }}</span>
      </div>
      <div class="font-bold text-slate-900 mt-1.5">{{ $r->title }}</div>
      @if($r->description)<p class="text-sm text-slate-500">{{ $r->description }}</p>@endif
      @if($r->needed_at)<p class="text-xs text-slate-400 mt-1">Dibutuhkan: {{ $r->needed_at->format('d M Y') }}</p>@endif
      <form method="post" action="{{ route('staff.requests.decide', $r) }}" class="flex flex-col sm:flex-row gap-2 mt-3">@csrf
        <select name="to" class="field !text-xs !w-auto" aria-label="Keputusan">@foreach(\App\Models\ServiceRequest::TRANSITIONS[$r->status] ?? [] as $next)<option value="{{ $next }}">{{ ucfirst($next) }}</option>@endforeach</select>
        <input name="handled_note" placeholder="Catatan (opsional)" class="field !text-xs flex-1">
        <button class="btn-primary !py-2 !text-xs shrink-0">Putuskan</button>
      </form>
    </div>
  @empty
    <p class="text-sm text-slate-400 bg-white rounded-2xl border border-dashed p-8 text-center">Tidak ada permintaan pada filter ini.</p>
  @endforelse
</div>
<div class="mt-5">{{ $reqs->links() }}</div>
@endsection
