@extends('layout')
@section('title', 'Dasbor Admin')
@section('content')
<h1 class="text-2xl font-extrabold tracking-tight">Dasbor Yayasan</h1>
<p class="text-sm text-slate-500 mb-5">Pantau kesehatan platform dalam sekejap.</p>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @foreach([['Dokumen', $stats['dokumen'], '📄', 'linear-gradient(135deg,#065f46,#0d9488)'], ['Publik', $stats['publik'], '🌍', 'linear-gradient(135deg,#0c4a6e,#0284c7)'], ['Tayangan', $stats['views'], '👁', 'linear-gradient(135deg,#6b21a8,#a855f7)'], ['Pengguna', $stats['pengguna'], '👥', 'linear-gradient(135deg,#9a3412,#ea580c)']] as [$l, $v, $i, $g])
    <div class="rounded-2xl p-4 text-white shadow-lg" style="background:{{ $g }}"><div class="text-xl">{{ $i }}</div><div class="text-2xl font-extrabold tabular-nums">{{ number_format($v) }}</div><div class="text-xs opacity-80 font-semibold">{{ $l }}</div></div>
  @endforeach
</div>
<div class="flex gap-2 mb-4 text-[13px] font-bold">
  <a href="{{ route('admin.categories') }}" class="btn-primary">🗂 Kelola Kategori</a>
  <a href="{{ route('admin.logs') }}" class="btn-ghost">📜 Riwayat Aktivitas</a>
</div>
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
  <h2 class="font-extrabold mb-3">Aktivitas Terbaru</h2>
  <div class="overflow-x-auto"><table class="w-full text-sm min-w-[560px]"><thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400"><th class="pb-2">Waktu</th><th class="pb-2">Pengguna</th><th class="pb-2">Aksi</th><th class="pb-2">Dokumen</th></tr></thead>
  <tbody>@foreach($logs as $l)<tr class="border-t border-slate-100"><td class="py-2 text-slate-500">{{ $l->created_at->format('d M H:i') }}</td><td class="py-2 font-semibold">{{ $l->user->name ?? '—' }}</td><td class="py-2"><span class="text-[11px] font-bold bg-slate-100 rounded-full px-2.5 py-0.5">{{ $l->action }}</span></td><td class="py-2 text-slate-500">{{ $l->document->title ?? '—' }}</td></tr>@endforeach</tbody></table></div>
</div>
@endsection
