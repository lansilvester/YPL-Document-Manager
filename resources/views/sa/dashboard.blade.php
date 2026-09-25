@extends('layout')
@section('title', 'Dasbor Super Admin')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Pusat Kendali SA']]])
@php
$store = $stats['storage'];
$storeLabel = $store >= 1073741824 ? round($store / 1073741824, 2).' GB' : round($store / 1048576, 1).' MB';
@endphp

{{-- Kepala dasbor --}}
<div class="rounded-3xl overflow-hidden text-white shadow-xl shadow-[#002f80]/25" style="background:linear-gradient(120deg,#000f2e,#002f80 60%,#0a4fc4)">
  <div class="p-6 sm:p-7 flex flex-wrap items-center gap-4">
    <span class="w-14 h-14 rounded-2xl grid place-items-center bg-white/15 border border-white/25">@include('components.icon', ['name' => 'shield', 'class' => 'w-7 h-7'])</span>
    <div class="flex-1 min-w-52">
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-2xl font-extrabold tracking-tight">Pusat Kendali</h1>
        <span class="text-[11px] font-bold bg-[#c90018] rounded-full px-2.5 py-0.5">SUPER ADMIN</span>
      </div>
      <p class="text-sm text-slate-300">Halo, <strong class="text-white">{{ auth()->user()->name }}</strong> · {{ now()->format('l, d M Y') }}</p>
    </div>
    <a href="{{ route('home') }}" class="bg-white text-[#002f80] rounded-full px-5 py-2 text-[13px] font-bold inline-flex items-center gap-2 hover:brightness-95">@include('components.icon', ['name' => 'eye', 'class' => 'w-4 h-4']) Lihat Situs</a>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 border-t border-white/15 divide-x divide-white/15 text-center">
    @foreach([['Pengguna', $stats['pengguna'], 'users'], ['Anggota', $stats['anggota'], 'cap'], ['Staff', $stats['staff'], 'tool'], ['Admin', $stats['admin'], 'shield'], ['Dokumen', $stats['dokumen'], 'file'], ['Tayangan', $stats['views'], 'eye']] as [$l, $v, $i])
      <div class="p-3.5"><div class="flex justify-center opacity-70">@include('components.icon', ['name' => $i, 'class' => 'w-4 h-4'])</div><div class="text-lg font-extrabold tabular-nums">{{ number_format($v) }}</div><div class="text-[11px] text-slate-300 font-semibold">{{ $l }}</div></div>
    @endforeach
  </div>
</div>

{{-- Modul pengelolaan --}}
<h2 class="font-extrabold text-[#002f80] mt-6 mb-3">Modul Pengelolaan</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @php
  $modules = [
    ['Kelola Pengguna', 'Atur peran anggota, staff, admin. Akun SA terkunci.', 'users', route('sa.users'), null],
    ['Pengaturan Web', 'Logo, nama situs & yayasan, sakelar fitur.', 'sliders', route('sa.settings'), null],
    ['Persetujuan Peran', 'Tinjau pengajuan kenaikan peran anggota.', 'shield', route('admin.approvals'), $stats['pending'] ? $stats['pending'].' menunggu' : null],
    ['Kelola Kategori', 'Struktur kategori perpustakaan yayasan.', 'folder', route('admin.categories'), null],
    ['Riwayat Aktivitas', 'Log audit seluruh aktivitas platform.', 'scroll', route('admin.logs'), null],
    ['Ruang Staff', 'Kelola dokumen & badge sebagai staf.', 'tool', route('staff.documents'), null],
  ];
  @endphp
  @foreach($modules as [$t, $d, $i, $url, $badge])
    <a href="{{ $url }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-5 flex gap-4 items-start shadow-sm">
      <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#001c55,#0046c0)">@include('components.icon', ['name' => $i, 'class' => 'w-5 h-5'])</span>
      <span class="flex-1">
        <span class="font-extrabold text-slate-900 flex items-center gap-2">{{ $t }}
          @if($badge)<span class="text-[11px] font-bold bg-[#c90018] text-white rounded-full px-2 py-0.5">{{ $badge }}</span>@endif
        </span>
        <span class="block text-[13px] text-slate-500 mt-0.5">{{ $d }}</span>
      </span>
      <span class="text-slate-300 mt-1">@include('components.icon', ['name' => 'chev-right', 'class' => 'w-4 h-4'])</span>
    </a>
  @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
  {{-- Status fitur --}}
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-extrabold text-[#002f80]">Status Fitur</h2>
      <a href="{{ route('sa.settings') }}" class="text-xs font-bold text-[#002f80] hover:underline">Ubah</a>
    </div>
    <ul class="divide-y divide-slate-100 text-sm">
      @foreach($features as $key => $label)
        <li class="py-2 flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full {{ \App\Models\Setting::feature($key) ? 'bg-emerald-500' : 'bg-[#c90018]' }}"></span>
          <span class="flex-1 font-semibold">{{ $label }}</span>
          <span class="text-[11px] font-bold {{ \App\Models\Setting::feature($key) ? 'text-emerald-700' : 'text-[#c90018]' }}">{{ \App\Models\Setting::feature($key) ? 'AKTIF' : 'MATI' }}</span>
        </li>
      @endforeach
    </ul>
  </div>

  {{-- Aktivitas terbaru + penyimpanan --}}
  <div class="space-y-4">
    <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#001c55,#002f80)">
      <div class="text-xs font-bold text-slate-300">PENYIMPANAN DOKUMEN</div>
      <div class="text-2xl font-extrabold tabular-nums">{{ $storeLabel }}</div>
      <div class="text-xs text-slate-300">{{ number_format($stats['dokumen']) }} berkas di perpustakaan</div>
    </div>
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
      <div class="flex items-center justify-between mb-2">
        <h2 class="font-extrabold text-[#002f80]">Aktivitas Terbaru</h2>
        <a href="{{ route('admin.logs') }}" class="text-xs font-bold text-[#002f80] hover:underline">Semua</a>
      </div>
      <ul class="space-y-2 text-[13px]">
        @forelse($logs as $l)
          <li class="flex gap-2"><span class="h-fit shrink-0 mt-0.5">@include('components.activity-badge', ['action' => $l->action])</span><span class="min-w-0"><strong>{{ $l->user->name ?? '—' }}</strong> <span class="text-slate-500 truncate">{{ $l->document->title ?? '' }} · {{ $l->created_at->diffForHumans() }}</span></span></li>
        @empty
          <li class="text-slate-400">Belum ada aktivitas.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>
@endsection
