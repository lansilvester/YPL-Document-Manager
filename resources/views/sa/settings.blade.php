@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Pengaturan Web')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Pusat Kendali SA', route('sa.dashboard')], ['Pengaturan Web']]])
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#7a0010,#c90018)">@include('components.icon', ['name' => 'sliders', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="page-title">Pengaturan Web</h1>
    <p class="text-sm text-slate-500">Wilayah Super Admin — identitas situs dan sakelar fitur.</p>
  </div>
</div>

<form method="post" action="{{ route('sa.settings.update') }}" enctype="multipart/form-data" class="grid lg:grid-cols-2 gap-5 mt-5">@csrf @method('PUT')
  {{-- Identitas --}}
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 h-fit">
    <h2 class="font-extrabold text-[#002f80] mb-3">Identitas Web</h2>
    <div class="space-y-3">
      <div><label class="text-[13px] font-bold">Nama situs *</label><input name="site_name" required maxlength="60" value="{{ old('site_name', \App\Models\Setting::get('site_name')) }}" class="field mt-1 !py-2 !text-[13px]"></div>
      <div><label class="text-[13px] font-bold">Nama yayasan *</label><input name="foundation_name" required maxlength="80" value="{{ old('foundation_name', \App\Models\Setting::get('foundation_name')) }}" class="field mt-1 !py-2 !text-[13px]"></div>
      <div><label class="text-[13px] font-bold">Domain email pendaftaran <span class="font-normal text-slate-400">(opsional)</span></label><input name="reg_domains" maxlength="255" value="{{ old('reg_domains', \App\Models\Setting::get('reg_domains')) }}" placeholder="cth: lokon.or.id, ypl.ac.id — kosongkan = email bebas" class="field mt-1 !py-2 !text-[13px]">
        <p class="text-[11px] text-slate-400 mt-1">Pisahkan beberapa domain dengan koma. Berguna bila domain YPL berubah.</p></div>
      <div><label class="text-[13px] font-bold">Masa retensi arsip <span class="font-normal text-slate-400">(hari)</span></label><input name="retention_days" type="number" min="30" max="3650" value="{{ old('retention_days', \App\Models\Setting::get('retention_days')) }}" class="field mt-1 !py-2 !text-[13px]">
        <p class="text-[11px] text-slate-400 mt-1">Dokumen berstatus <em>review due</em> yang melewati masa ini diarsipkan otomatis (default 1095 hari ≈ 3 tahun).</p></div>
      <div>
        <label class="text-[13px] font-bold">Logo web <span class="font-normal text-slate-400">(JPG/PNG/GIF/WebP, maks 2 MB)</span></label>
        <div class="flex items-center gap-3 mt-1.5">
          <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo saat ini" class="h-14 w-auto rounded-lg border border-[#d6d6d6] bg-white object-contain px-2">
          <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="field !py-2 !text-[13px] file:mr-3 file:rounded-full file:border-0 file:bg-[#002f80]/10 file:text-[#002f80] file:text-xs file:font-bold file:px-4 file:py-1.5">
        </div>
        @error('logo')<p class="text-xs font-semibold text-[#c90018] mt-1">{{ $message }}</p>@enderror
        <label class="text-xs flex gap-2 items-center mt-2 text-slate-500"><input type="checkbox" name="logo_reset" value="1" class="accent-[#c90018]"> Kembalikan ke logo bawaan YPL</label>
      </div>
    </div>
  </div>

  {{-- Sakelar fitur --}}
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 h-fit">
    <h2 class="font-extrabold text-[#002f80] mb-1">Fitur & Tampilan</h2>
    <p class="text-xs text-slate-400 mb-3">Matikan fitur untuk menyembunyikan tombolnya dan memblokir akses langsung via URL.</p>
    <div class="divide-y divide-slate-100">
      @foreach(\App\Models\Setting::FEATURES as $key => $label)
        <label class="flex items-center gap-3 py-2.5 cursor-pointer">
          <input type="checkbox" name="feat_{{ $key }}" value="1" {{ \App\Models\Setting::feature($key) ? 'checked' : '' }} class="peer sr-only">
          <span class="w-10 h-6 rounded-full bg-slate-200 relative transition peer-checked:bg-[#002f80] after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-4"></span>
          <span class="text-sm font-bold">{{ $label }}</span>
        </label>
      @endforeach
    </div>
  </div>

  <div class="lg:col-span-2 flex gap-2">
    <button class="btn-primary">Simpan Pengaturan</button>
    <a href="{{ route('sa.users') }}" class="btn-ghost">Kelola Pengguna</a>
    <a href="{{ route('sa.dashboard') }}" class="btn-ghost">Dasbor SA</a>
  </div>
</form>
@endsection
