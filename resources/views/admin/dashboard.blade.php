@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Dasbor Admin')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin']]])
<h1 class="page-title">Dasbor Yayasan</h1>
<p class="text-sm text-slate-500 mb-5">Pantau kesehatan platform dalam sekejap.</p>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @foreach([['Dokumen', $stats['dokumen'], 'file', 'linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy))'], ['Publik', $stats['publik'], 'globe', 'linear-gradient(135deg,var(--ypl-navy),#3b6fd4)'], ['Tayangan', $stats['views'], 'eye', 'linear-gradient(135deg,var(--ypl-red-800),var(--ypl-red))'], ['Pengguna', $stats['pengguna'], 'users', 'linear-gradient(135deg,#334155,var(--ypl-muted))']] as [$l, $v, $i, $g])
    <div class="rounded-2xl p-4 text-white shadow-lg" style="background:{{ $g }}"><div class="opacity-80">@include('components.icon', ['name' => $i, 'class' => 'w-5 h-5'])</div><div class="text-2xl font-extrabold tabular-nums mt-1">{{ number_format($v) }}</div><div class="text-xs opacity-80 font-semibold">{{ $l }}</div></div>
  @endforeach
</div>
<div class="flex gap-2 mb-4 text-[13px] font-bold flex-wrap">
  <a href="{{ route('admin.categories') }}" class="btn-primary">Kelola Kategori</a>
  <a href="{{ route('admin.logs') }}" class="btn-ghost">Riwayat Aktivitas</a>
  <a href="{{ route('admin.approvals') }}" class="btn-ghost">Persetujuan Peran @if($pendingApprovals)<span class="bg-[var(--ypl-red)] text-white text-[11px] rounded-full px-2 py-0.5">{{ $pendingApprovals }}</span>@endif</a>
  <a href="{{ route('admin.users') }}" class="btn-ghost">Kelola Pengguna</a>
  <a href="{{ route('admin.units') }}" class="btn-ghost">Unit</a>
  <a href="{{ route('admin.contacts') }}" class="btn-ghost">Kontak PIC</a>
  @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.users') }}" class="btn-ghost">Panel Super Admin</a>@endif
  @if(auth()->user()->isSuperAdmin())<a href="{{ route('sa.settings') }}" class="btn-ghost">Pengaturan Web</a>@endif
</div>
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
  <h2 class="font-extrabold mb-3 text-[var(--ypl-navy)]">Aktivitas Terbaru</h2>
  <div class="overflow-x-auto"><table class="w-full text-sm min-w-[560px]"><thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400"><th class="pb-2">Waktu</th><th class="pb-2">Pengguna</th><th class="pb-2">Aksi</th><th class="pb-2">Dokumen</th></tr></thead>
  <tbody>@foreach($logs as $l)<tr class="border-t border-slate-100"><td class="py-2 text-slate-500">{{ $l->created_at->format('d M H:i') }}</td><td class="py-2 font-semibold">{{ $l->user->name ?? '—' }}</td><td class="py-2">@include('components.activity-badge', ['action' => $l->action])</td><td class="py-2 text-slate-500">{{ $l->document->title ?? '—' }}</td></tr>@endforeach</tbody></table></div>
</div>
@endsection
