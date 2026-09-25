@extends('layout')
@section('title', 'Riwayat Aktivitas')
@section('content')
<h1 class="text-2xl font-bold mb-4">Riwayat Aktivitas</h1>
<form method="get" class="mb-3 text-sm flex gap-2">
  <select name="aksi" class="border rounded px-2 py-1" onchange="this.form.submit()">
    <option value="">Semua aksi</option>
    @foreach(['view','upload','update','delete','download','favorite','register','login'] as $a)
      <option value="{{ $a }}" {{ request('aksi') === $a ? 'selected' : '' }}>{{ $a }}</option>
    @endforeach
  </select>
</form>
<div class="bg-white rounded shadow p-4">
<table class="w-full text-sm"><thead><tr class="text-left text-stone-500"><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Dokumen</th><th>IP</th></tr></thead>
<tbody>@foreach($logs as $l)<tr class="border-t"><td>{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->user->name ?? '—' }}</td><td>{{ $l->action }}</td><td>{{ $l->document->title ?? '—' }}</td><td>{{ $l->ip }}</td></tr>@endforeach</tbody></table>
<div class="mt-3">{{ $logs->links() }}</div>
</div>
@endsection
