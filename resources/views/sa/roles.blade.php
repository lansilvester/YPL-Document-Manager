@extends('layout')
@section('title', 'Definisi Peran')
@section('main_width', 'max-w-[1400px]')
@section('content')
@include('components.back-bar', ['trail' => [['Pusat Kendali', route('sa.dashboard')], ['Definisi Peran']]])
<div class="flex flex-wrap items-end gap-3 mb-4">
  <div class="flex-1 min-w-52">
    <h1 class="page-title">Definisi Peran Role-Aware</h1>
    <p class="text-sm text-slate-500">Khusus Super Admin. Peran sistem (🔒) terkunci dari hapus dan ganti slug. Prioritas kecil = konten diprioritaskan lebih dulu.</p>
  </div>
</div>

{{-- Ringkasan --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
  @foreach([['Total Peran', $stats['total'], 'users'], ['Sistem 🔒', $stats['system'], 'shield'], ['Audiens', $stats['audience'], 'cap'], ['Pengguna Terpetakan', $stats['mapped'], 'check']] as [$l, $v, $i])
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-4 flex items-center gap-3">
      <span class="w-10 h-10 rounded-xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">@include('components.icon', ['name' => $i, 'class' => 'w-5 h-5'])</span>
      <span><span class="block text-2xl font-extrabold text-[var(--ypl-navy)] leading-none">{{ $v }}</span><span class="text-xs text-slate-500 font-semibold">{{ $l }}</span></span>
    </div>
  @endforeach
</div>

{{-- Toolbar interaktif --}}
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-3 mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
  <div class="relative flex-1 min-w-0">
    <input id="role-search" placeholder="Cari nama atau slug peran…" class="field !rounded-full !pl-10" aria-label="Cari peran">
    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">@include('components.icon', ['name' => 'search', 'class' => 'w-4 h-4'])</span>
  </div>
  <div class="flex gap-2 text-xs font-bold">
    <button data-role-filter="semua" class="role-filter px-4 py-2 rounded-full bg-[var(--ypl-navy)] text-white transition">Semua</button>
    <button data-role-filter="sistem" class="role-filter px-4 py-2 rounded-full bg-slate-100 text-slate-600 transition">🔒 Sistem</button>
    <button data-role-filter="audiens" class="role-filter px-4 py-2 rounded-full bg-slate-100 text-slate-600 transition">Audiens</button>
  </div>
  <span id="role-count" class="text-xs text-slate-400 font-semibold sm:ml-auto whitespace-nowrap"></span>
</div>

<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden mb-6">
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[880px]">
      <thead class="sticky top-0">
        <tr class="text-left text-[11px] tracking-widest text-slate-400 border-b border-slate-100 bg-white">
          <th class="font-bold px-4 py-3 w-24">PRIORITAS</th>
          <th class="font-bold px-4 py-3">PERAN</th>
          <th class="font-bold px-4 py-3">SCOPE KOLEKSI</th>
          <th class="font-bold px-4 py-3">HOMEPAGE</th>
          <th class="font-bold px-4 py-3 w-24">PENGGUNA</th>
          <th class="font-bold px-4 py-3 w-36 text-right">AKSI</th>
        </tr>
      </thead>
      <tbody id="role-rows" class="divide-y divide-slate-100">
        @foreach($roles as $r)
          @php
            $prioStyle = $r->priority <= 10 ? 'background:#002f80' : ($r->priority <= 30 ? 'background:#0a4fc4' : ($r->priority <= 60 ? 'background:#64748b' : 'background:#cbd5e1;color:#475569'));
          @endphp
          <tr class="role-row hover:bg-slate-50/70 align-top" data-name="{{ strtolower($r->name.' '.$r->slug) }}" data-kind="{{ $r->is_system ? 'sistem' : 'audiens' }}">
            <td class="px-4 py-3"><span class="inline-block text-xs font-extrabold text-white px-2.5 py-1 rounded-full" style="{{ $prioStyle }}">#{{ $r->priority }}</span></td>
            <td class="px-4 py-3">
              <span class="font-bold text-slate-900">{{ $r->name }} @if($r->is_system)<span title="Peran sistem">🔒</span>@endif</span>
              <span class="block text-xs text-slate-400 font-mono">{{ $r->slug }}</span>
              @if($r->description)<span class="block text-xs text-slate-500 mt-0.5">{{ $r->description }}</span>@endif
            </td>
            <td class="px-4 py-3">
              <span class="flex flex-wrap gap-1">@foreach($r->scopes['collections'] ?? [] as $c)<span class="text-[11px] font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] px-2 py-0.5 rounded-full">{{ $c }}</span>@endforeach</span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-500">
              @if(!empty($r->homepage['quick']))<span class="block"><span class="font-bold text-slate-600">Quick:</span> {{ implode(' → ', $r->homepage['quick']) }}</span>@endif
              @if(!empty($r->homepage['explore']))<span class="block mt-0.5"><span class="font-bold text-slate-600">Explore:</span> {{ implode(' → ', $r->homepage['explore']) }}</span>@endif
              @if(empty($r->homepage['quick']) && empty($r->homepage['explore']))<span class="text-slate-300">— default —</span>@endif
            </td>
            <td class="px-4 py-3"><span class="text-sm font-bold">{{ $userCounts[$r->slug] ?? 0 }}</span></td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button data-role-toggle="{{ $r->id }}" class="text-xs font-bold text-[var(--ypl-navy)] border border-[var(--ypl-navy)]/30 rounded-full px-3 py-1.5 hover:bg-[var(--ypl-navy)] hover:text-white transition" aria-expanded="false">Ubah</button>
              @unless($r->is_system)
                <form method="post" action="{{ route('sa.roles.destroy', $r) }}" class="inline" data-confirm="Hapus peran {{ $r->name }}?">@csrf @method('DELETE')<button class="text-xs font-bold text-[var(--ypl-red)] border border-[var(--ypl-red)]/30 rounded-full px-3 py-1.5 hover:bg-[var(--ypl-red)]/5">Hapus</button></form>
              @endunless
            </td>
          </tr>
          <tr id="role-form-{{ $r->id }}" class="hidden bg-slate-50/70">
            <td colspan="6" class="px-4 py-4">
              <form method="post" action="{{ route('sa.roles.update', $r) }}" class="space-y-3">@csrf @method('PUT')
                <div class="grid sm:grid-cols-3 gap-3">
                  <div><label class="text-[12px] font-bold">Slug {{ $r->is_system ? '(terkunci)' : '*' }}</label><input name="slug" required value="{{ old('slug', $r->slug) }}" class="field mt-1" {{ $r->is_system ? 'disabled' : '' }}></div>
                  <div><label class="text-[12px] font-bold">Nama *</label><input name="name" required value="{{ old('name', $r->name) }}" class="field mt-1"></div>
                  <div><label class="text-[12px] font-bold">Prioritas *</label><input type="number" name="priority" required value="{{ old('priority', $r->priority) }}" min="0" max="999" class="field mt-1"></div>
                </div>
                <div><label class="text-[12px] font-bold">Deskripsi</label><input name="description" value="{{ old('description', $r->description) }}" class="field mt-1"></div>
                <div><span class="text-[12px] font-bold">Scope koleksi</span><div class="flex flex-wrap gap-2 mt-1">@foreach(\App\Models\Role::COLLECTIONS as $c)<label class="text-xs font-semibold inline-flex items-center gap-1.5 bg-white border rounded-full px-3 py-1.5"><input type="checkbox" name="collections[]" value="{{ $c }}" {{ in_array($c, old('collections', $r->scopes['collections'] ?? []), true) ? 'checked' : '' }}> {{ $c }}</label>@endforeach</div></div>
                <div class="grid sm:grid-cols-2 gap-3">
                  <div><span class="text-[12px] font-bold">Urutan quick actions</span><div class="flex flex-wrap gap-2 mt-1">@foreach(\App\Models\Role::QUICK_ACTIONS as $qa)<label class="text-xs font-semibold inline-flex items-center gap-1.5 bg-white border rounded-full px-3 py-1.5"><input type="checkbox" name="quick[]" value="{{ $qa }}" {{ in_array($qa, old('quick', $r->homepage['quick'] ?? []), true) ? 'checked' : '' }}> {{ $qa }}</label>@endforeach</div></div>
                  <div><span class="text-[12px] font-bold">Urutan explore</span><div class="flex flex-wrap gap-2 mt-1">@foreach(\App\Models\Role::EXPLORE as $ex)<label class="text-xs font-semibold inline-flex items-center gap-1.5 bg-white border rounded-full px-3 py-1.5"><input type="checkbox" name="explore[]" value="{{ $ex }}" {{ in_array($ex, old('explore', $r->homepage['explore'] ?? []), true) ? 'checked' : '' }}> {{ $ex }}</label>@endforeach</div></div>
                </div>
                <div><button class="btn-primary !py-2">Simpan {{ $r->slug }}</button></div>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
<script nonce="{{ csp_nonce() }}">
(function () {
  // Buka/tutup baris form.
  document.querySelectorAll('[data-role-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = document.getElementById('role-form-' + btn.dataset.roleToggle);
      var open = row.classList.toggle('hidden');
      btn.setAttribute('aria-expanded', String(!open));
      btn.textContent = open ? 'Ubah' : 'Tutup';
    });
  });

  // Cari + filter jenis.
  var q = document.getElementById('role-search');
  var count = document.getElementById('role-count');
  var kind = 'semua';
  var rows = Array.prototype.slice.call(document.querySelectorAll('.role-row'));
  function apply() {
    var needle = q.value.trim().toLowerCase(), shown = 0;
    rows.forEach(function (tr) {
      var ok = (kind === 'semua' || tr.dataset.kind === kind) && (!needle || tr.dataset.name.indexOf(needle) !== -1);
      tr.style.display = ok ? '' : 'none';
      var form = document.getElementById('role-form-' + tr.querySelector('[data-role-toggle]').dataset.roleToggle);
      if (!ok) { form.classList.add('hidden'); }
      else shown++;
    });
    count.textContent = shown + ' / ' + rows.length + ' peran';
  }
  q.addEventListener('input', apply);
  document.querySelectorAll('.role-filter').forEach(function (btn) {
    btn.addEventListener('click', function () {
      kind = btn.dataset.roleFilter;
      document.querySelectorAll('.role-filter').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('bg-[var(--ypl-navy)]', on);
        b.classList.toggle('text-white', on);
        b.classList.toggle('bg-slate-100', !on);
        b.classList.toggle('text-slate-600', !on);
      });
      apply();
    });
  });
  apply();
})();
</script>

<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5 mt-6">
  <h2 class="font-extrabold mb-3 text-[var(--ypl-navy)]">Buat Peran Baru</h2>
  <form method="post" action="{{ route('sa.roles.store') }}" class="space-y-3">@csrf
    <div class="grid sm:grid-cols-3 gap-3">
      <div><label class="text-[13px] font-bold">Slug * <span class="font-normal text-slate-400">huruf kecil/angka/_</span></label><input name="slug" required pattern="[a-z0-9_]+" maxlength="30" placeholder="cth: wali_kelas" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Nama *</label><input name="name" required maxlength="60" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Prioritas *</label><input type="number" name="priority" required value="50" min="0" max="999" class="field mt-1"></div>
    </div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><input name="description" maxlength="255" class="field mt-1"></div>
    <div><span class="text-[13px] font-bold">Scope koleksi</span><div class="flex flex-wrap gap-2 mt-1">@foreach(\App\Models\Role::COLLECTIONS as $c)<label class="text-xs font-semibold inline-flex items-center gap-1.5 bg-slate-50 border rounded-full px-3 py-1.5"><input type="checkbox" name="collections[]" value="{{ $c }}" checked> {{ $c }}</label>@endforeach</div></div>
    <button class="btn-primary">Buat Peran</button>
  </form>
</div>
@endsection
