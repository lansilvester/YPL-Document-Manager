@extends('layout')
@section('title', 'Layanan')
@section('content')
<p class="page-eyebrow text-[var(--ypl-red)] mt-2">SERVICES</p>
<h1 class="page-title">Saya ingin…</h1>
<p class="text-sm text-slate-500 mt-1">Ajukan kebutuhan tanpa harus paham struktur organisasi. Butuh panduan? Buka <a href="{{ route('help.index') }}" class="font-bold text-[var(--ypl-navy)] underline">Help Center</a>. @auth @if($myPending) <a href="{{ route('services.my') }}" class="font-bold text-[var(--ypl-navy)] underline">{{ $myPending }} pengajuan menunggu</a>. @endif @endauth</p>

<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
  @php
    $subs = ['transportation' => 'Fleet request', 'leave' => 'HR service', 'maintenance' => 'Facility service', 'student_permission' => 'Boarding service', 'purchase' => 'Finance / Procurement', 'event' => 'Event / permission', 'incident' => 'Safety service', 'submission' => 'Records service'];
  @endphp
  @foreach($types as $key => $label)
    <a href="{{ route('services.request', $key) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 block">
      <div class="font-bold text-sm text-slate-900">{{ $label }}</div>
      <div class="text-xs text-slate-500 mt-0.5">{{ $subs[$key] ?? 'YPL service' }}</div>
      <div class="text-xs font-bold text-[var(--ypl-navy)] mt-2 text-right">Start →</div>
    </a>
  @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-8">
  <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-5">
    <h2 class="font-extrabold text-[var(--ypl-navy)]">FORMS</h2>
    <div class="mt-2 divide-y divide-slate-100">
      @forelse($forms as $f)
        <a href="{{ route('documents.show', $f) }}" class="flex items-center gap-3 py-2 group">
          <span class="text-xs font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] px-2 py-1 rounded-full shrink-0">FORM</span>
          <span class="min-w-0"><span class="block text-sm font-bold truncate group-hover:text-[var(--ypl-navy)]">{{ $f->title }}</span><span class="text-[11px] text-slate-400">v{{ $f->version }} · {{ $f->doc_code ?? '—' }}</span></span>
        </a>
      @empty
        <p class="text-sm text-slate-400 py-2">Belum ada formulir aktif.</p>
      @endforelse
    </div>
  </div>
  <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy))">
    <h2 class="font-extrabold">MY REQUESTS</h2>
    <p class="text-xs text-slate-300 mt-1">Pantau status pengajuan: pending → approved/rejected → done.</p>
    <div class="flex flex-wrap gap-2 mt-3 text-xs font-bold">
      <a href="{{ route('services.my') }}" class="bg-white text-[var(--ypl-navy)] rounded-full px-4 py-2">Permintaan Saya</a>
      @if($sop = \App\Models\Document::where('slug', 'sop-pengadaan-barang-dan-jasa')->first())
        <a href="{{ route('documents.show', $sop) }}" class="bg-white/15 border border-white/30 rounded-full px-4 py-2">Contoh alur: SOP Pengadaan</a>
      @endif
    </div>
  </div>
</div>
<p class="text-[11px] text-slate-400 mt-4">Integrasi Odoo (fleet, HR, finance, maintenance) hadir di Phase 6 — untuk sekarang pengajuan diproses manual oleh staff.</p>
@endsection
