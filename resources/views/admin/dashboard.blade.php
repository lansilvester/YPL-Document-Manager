@extends('layout')
@section('title', 'Dasbor Admin')
@section('content')
<h1 class="text-2xl font-bold mb-4">Dasbor Yayasan</h1>
<div class="grid sm:grid-cols-4 gap-4 mb-6">
  @foreach(['Dokumen' => $stats['dokumen'], 'Publik' => $stats['publik'], 'Tayangan' => $stats['views'], 'Pengguna' => $stats['pengguna']] as $l => $v)
    <div class="bg-white rounded shadow p-4"><div class="text-xs text-stone-500">{{ $l }}</div><div class="text-2xl font-bold">{{ $v }}</div></div>
  @endforeach
</div>
<div class="flex gap-2 mb-4 text-sm">
  <a href="{{ route('admin.categories') }}" class="bg-emerald-700 text-white rounded px-3 py-1.5">Kelola Kategori</a>
  <a href="{{ route('admin.logs') }}" class="border rounded px-3 py-1.5 bg-white">Riwayat Aktivitas</a>
</div>
<div class="bg-white rounded shadow p-4">
  <h2 class="font-bold mb-2">Aktivitas Terbaru</h2>
  <table class="w-full text-sm"><thead><tr class="text-left text-stone-500"><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Dokumen</th></tr></thead>
  <tbody>@foreach($logs as $l)<tr class="border-t"><td>{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->user->name ?? '—' }}</td><td>{{ $l->action }}</td><td>{{ $l->document->title ?? '—' }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
