@extends('layout')
@section('title', 'Riwayat Aktivitas')
@section('content')
<div class="flex items-center justify-between flex-wrap gap-2 mb-4">
  <div><h1 class="text-2xl font-extrabold tracking-tight">Riwayat Aktivitas</h1><p class="text-sm text-slate-500">Log audit pembukaan, pengunggahan, dan perubahan.</p></div>
  <form method="get" class="text-sm">
    <select name="aksi" class="field !w-auto !rounded-full !py-2 text-[13px] font-bold" onchange="this.form.submit()">
      <option value="">Semua aksi</option>
      @foreach(['view','upload','update','delete','download','favorite','register','login'] as $a)
        <option value="{{ $a }}" {{ request('aksi') === $a ? 'selected' : '' }}>{{ $a }}</option>
      @endforeach
    </select>
  </form>
</div>
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
<div class="overflow-x-auto"><table class="w-full text-sm min-w-[640px]"><thead><tr class="text-left text-xs uppercase tracking-wider text-slate-400"><th class="pb-2">Waktu</th><th class="pb-2">Pengguna</th><th class="pb-2">Aksi</th><th class="pb-2">Dokumen</th><th class="pb-2">IP</th></tr></thead>
<tbody>@foreach($logs as $l)<tr class="border-t border-slate-100"><td class="py-2 text-slate-500">{{ $l->created_at->format('d M H:i') }}</td><td class="py-2 font-semibold">{{ $l->user->name ?? '—' }}</td><td class="py-2"><span class="text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full px-2.5 py-0.5">{{ $l->action }}</span></td><td class="py-2 text-slate-500">{{ $l->document->title ?? '—' }}</td><td class="py-2 text-slate-400">{{ $l->ip }}</td></tr>@endforeach</tbody></table></div>
<div class="mt-3">{{ $logs->links() }}</div>
</div>
@endsection
