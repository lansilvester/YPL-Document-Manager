@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Riwayat Aktivitas')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Riwayat Aktivitas']]])
<div class="flex items-center justify-between flex-wrap gap-2 mb-4">
  <div class="flex items-center gap-3">
    <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">@include('components.icon', ['name' => 'scroll', 'class' => 'w-5 h-5'])</span>
    <div><h1 class="page-title">Riwayat Aktivitas</h1><p class="text-sm text-slate-500">Log audit pembukaan, pengunggahan, dan perubahan.</p></div>
  </div>
  <form method="get" class="text-sm">
    <select name="aksi" class="field !w-auto !rounded-full !py-2 text-[13px] font-bold" onchange="this.form.submit()">
      <option value="">Semua aksi</option>
      @foreach(['view','upload','update','delete','download','bookmark','register','login'] as $a)
        <option value="{{ $a }}" {{ request('aksi') === $a ? 'selected' : '' }}>{{ $a }}</option>
      @endforeach
    </select>
  </form>
</div>
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
<div class="overflow-x-auto"><table class="w-full text-sm min-w-[640px]"><thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400"><th class="pb-2">Waktu</th><th class="pb-2">Pengguna</th><th class="pb-2">Aksi</th><th class="pb-2">Dokumen</th><th class="pb-2">IP</th></tr></thead>
<tbody>@foreach($logs as $l)<tr class="border-t border-slate-100"><td class="py-2 text-slate-500">{{ $l->created_at->format('d M H:i') }}</td><td class="py-2 font-semibold">{{ $l->user->name ?? '—' }}</td><td class="py-2">@include('components.activity-badge', ['action' => $l->action])</td><td class="py-2 text-slate-500">{{ $l->document->title ?? '—' }}</td><td class="py-2 text-slate-400">{{ $l->ip }}</td></tr>@endforeach</tbody></table></div>
<div class="mt-3">{{ $logs->links() }}</div>
</div>
@endsection
