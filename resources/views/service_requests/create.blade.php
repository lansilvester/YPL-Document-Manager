@extends('layout')
@section('title', 'Ajukan Layanan')
@section('content')
@include('components.back-bar', ['trail' => [['Layanan', route('services.index')], ['Ajukan '.\App\Models\ServiceRequest::TYPES[$type]]]])
<div class="max-w-xl mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl shadow-[#002f80]/5 p-7 sm:p-8 mt-4">
  <h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Ajukan: {{ \App\Models\ServiceRequest::TYPES[$type] }}</h1>
  <p class="text-sm text-slate-500 mb-5">Ceritakan kebutuhanmu — staff akan memproses dan status tampil di Permintaan Saya.</p>
  <form method="post" action="{{ route('services.store') }}" class="space-y-4">@csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <div><label class="text-[13px] font-bold">Judul kebutuhan *</label><input name="title" required value="{{ old('title') }}" placeholder="cth: Antar jemput kegiatan Sabtu" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Detail</label><textarea name="description" rows="4" placeholder="Waktu, lokasi, jumlah, kontak…" class="field mt-1">{{ old('description') }}</textarea></div>
    <div><label class="text-[13px] font-bold">Dibutuhkan tanggal</label><input type="date" name="needed_at" value="{{ old('needed_at') }}" class="field mt-1"></div>
    <button class="btn-primary w-full !py-3">Kirim Pengajuan</button>
  </form>
</div>
@endsection
