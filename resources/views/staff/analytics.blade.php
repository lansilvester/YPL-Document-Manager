@extends('layout')
@section('title', 'Analytics')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Ruang Kerja Staff', route('staff.documents')], ['Analytics']]])
<p class="page-eyebrow text-[#c90018] mt-2">ANALYTICS · 30 HARI</p>
<h1 class="page-title">Analytics Operasional</h1>
<p class="text-sm text-slate-500 mt-1">Bacaan, unduhan, pertanyaan, unggahan, layanan, dan aktivitas terakhir.</p>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
  @foreach($cards as [$label, $n, $icon])
    <div class="bg-white rounded-2xl border border-[#d6d6d6] p-4 flex items-center gap-3">
      <span class="w-10 h-10 rounded-xl grid place-items-center text-white grad-navy shrink-0">@include('components.icon', ['name' => $icon, 'class' => 'w-5 h-5'])</span>
      <span class="min-w-0"><span class="block text-2xl font-extrabold text-[#002f80] tabular-nums leading-none">{{ number_format($n) }}</span><span class="block text-[11px] font-bold tracking-widest text-slate-400 mt-1">{{ strtoupper($label) }}</span></span>
    </div>
  @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
  <div class="bg-white rounded-2xl border border-[#d6d6d6] p-5">
    <h2 class="font-extrabold text-[#002f80]">TREN 14 HARI</h2>
    <div class="mt-3 space-y-1.5">
      @forelse($trend as $t)
        <div class="flex items-center gap-2 text-xs">
          <span class="w-14 shrink-0 font-bold text-slate-500 tabular-nums">{{ \Carbon\Carbon::parse($t->day)->format('d M') }}</span>
          <span class="flex-1 h-4 rounded-full bg-slate-100 overflow-hidden"><span class="block h-full rounded-full grad-navy" style="width:{{ $trendMax > 0 ? round($t->total / $trendMax * 100) : 0 }}%"></span></span>
          <span class="w-10 text-right font-bold text-slate-700 tabular-nums">{{ number_format($t->total) }}</span>
        </div>
      @empty
        <p class="text-sm text-slate-400">Belum ada aktivitas tercatat.</p>
      @endforelse
    </div>
  </div>
  <div class="bg-white rounded-2xl border border-[#d6d6d6] p-5">
    <h2 class="font-extrabold text-[#002f80]">LAYANAN PER STATUS</h2>
    <div class="mt-3 space-y-2">
      @forelse($services as $status => $n)
        <div class="flex items-center gap-2 text-sm">
          <span class="flex-1 font-bold text-slate-700">{{ strtoupper(str_replace('_', ' ', $status)) }}</span>
          <span class="font-extrabold text-[#002f80] tabular-nums">{{ number_format($n) }}</span>
          <a href="{{ route('staff.requests') }}" class="text-xs font-bold text-slate-400 hover:text-[#002f80]">Kelola →</a>
        </div>
      @empty
        <p class="text-sm text-slate-400">Belum ada pengajuan layanan.</p>
      @endforelse
    </div>
    <h2 class="font-extrabold text-[#002f80] mt-5">AKSI 30 HARI</h2>
    <div class="mt-2 flex flex-wrap gap-1.5">
      @foreach($byAction as $action => $n)
        <span class="text-[11px] font-bold bg-slate-100 text-slate-600 rounded-full px-2.5 py-1">{{ $action }} · {{ number_format($n) }}</span>
      @endforeach
    </div>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
  <div class="bg-white rounded-2xl border border-[#d6d6d6] p-5">
    <h2 class="font-extrabold text-[#002f80]">DOKUMEN TERATAS <span class="text-[10px] font-bold text-slate-400">SEPANJANG WAKTU</span></h2>
    <div class="mt-2 divide-y divide-slate-100">
      @forelse($topDocs as $d)
        <a href="{{ route('documents.show', $d) }}" class="flex items-center gap-3 py-2 group">
          <span class="font-extrabold text-slate-300 tabular-nums w-6 shrink-0">{{ $loop->iteration }}</span>
          <span class="min-w-0 flex-1"><span class="block text-sm font-bold truncate group-hover:text-[#002f80]">{{ $d->title }}</span><span class="text-[11px] text-slate-400">{{ number_format($d->views_count) }} dibaca · {{ number_format($d->downloads_count) }} diunduh</span></span>
        </a>
      @empty
        <p class="text-sm text-slate-400 py-2">Belum ada data.</p>
      @endforelse
    </div>
  </div>
  <div class="bg-white rounded-2xl border border-[#d6d6d6] p-5">
    <h2 class="font-extrabold text-[#002f80]">AKTIVITAS TERAKHIR</h2>
    <div class="mt-2 divide-y divide-slate-100 text-sm">
      @forelse($recent as $log)
        <div class="flex items-center gap-2 py-2">
          <span class="text-[10px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-0.5 rounded-full shrink-0">{{ strtoupper($log->action) }}</span>
          <span class="min-w-0 flex-1 truncate text-slate-600">{{ $log->user->name ?? 'Tamu' }}{{ $log->document ? ' · '.$log->document->title : '' }}</span>
          <span class="text-[11px] text-slate-400 shrink-0 tabular-nums">{{ $log->created_at->diffForHumans() }}</span>
        </div>
      @empty
        <p class="text-sm text-slate-400 py-2">Belum ada aktivitas.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
