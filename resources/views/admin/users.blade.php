@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Kelola Pengguna')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Kelola Pengguna']]])
@php
$roleStyle = [
  'superadmin' => 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0',
  'admin' => 'background:#dbe4f7;color:#002f80;border-color:#b9c9ec',
  'staff' => 'background:#e2eaf6;color:#33507e;border-color:#c3d2ea',
  'anggota' => 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6',
];
$roleLabel = ['superadmin' => 'SUPER ADMIN', 'admin' => 'ADMIN', 'staff' => 'STAFF', 'anggota' => 'ANGGOTA'];
$audienceLabel = ['siswa' => 'Siswa', 'orang_tua' => 'Orang Tua', 'guru' => 'Guru', 'karyawan' => 'Karyawan', 'pimpinan' => 'Pimpinan', 'mitra' => 'Mitra', 'publik' => 'Publik'];
$allCount = $roleCounts->sum();
@endphp

<div class="flex items-center gap-3 mb-4">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy))">@include('components.icon', ['name' => 'users', 'class' => 'w-5 h-5'])</span>
  <div class="flex-1">
    <h1 class="page-title">Kelola Pengguna</h1>
    <p class="text-sm text-slate-500">Daftar akun komunitas. @if(! $isSuperAdmin)Akun Super Admin tidak ditampilkan untuk admin.@endif</p>
  </div>
</div>

{{-- Pencarian --}}
<form method="get" action="{{ route('admin.users') }}" class="flex flex-col sm:flex-row gap-2 mb-3">
  <div class="relative flex-1">
    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">@include('components.icon', ['name' => 'search', 'class' => 'w-4 h-4'])</span>
    <input name="q" value="{{ $q }}" placeholder="Cari nama atau email…" class="field pl-10 !py-2.5">
    @if($role !== '')<input type="hidden" name="role" value="{{ $role }}">@endif
  </div>
  <div class="flex gap-2">
    <button class="btn-primary !py-2.5 !px-6">Cari</button>
    @if($q !== '' || $role !== '')
      <a href="{{ route('admin.users') }}" class="btn-ghost !py-2.5 inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'x', 'class' => 'w-4 h-4']) Reset</a>
    @endif
  </div>
</form>

{{-- Filter peran --}}
<div class="flex gap-2 mb-4 text-[13px] font-semibold overflow-x-auto pb-1">
  <a href="{{ route('admin.users', ['q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $role === '' ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)] shadow' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">Semua ({{ number_format($allCount) }})</a>
  @foreach(['anggota' => 'Anggota', 'staff' => 'Staff', 'admin' => 'Admin'] as $val => $label)
    <a href="{{ route('admin.users', ['role' => $val, 'q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $role === $val ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)] shadow' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">{{ $label }} ({{ number_format($roleCounts[$val] ?? 0) }})</a>
  @endforeach
</div>

{{-- Tabel pengguna --}}
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-4 py-3">Nama</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Tanggal Terdaftar</th>
          <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($users as $u)
          @php
          $extras = [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'roleLabel' => $roleLabel[$u->role] ?? strtoupper($u->role),
            'roleStyle' => $roleStyle[$u->role] ?? $roleStyle['anggota'],
            'audience' => $u->audience,
            'audienceLabel' => $u->audience ? ($audienceLabel[$u->audience] ?? ucfirst($u->audience)) : null,
            'avatar' => $u->avatar_path ? asset('storage/'.$u->avatar_path) : null,
            'created' => $u->created_at?->format('d M Y, H:i'),
            'wa' => $u->wa_number ?? '—',
            'academic' => $u->academic_info ?? '—',
            'birth' => $u->birth_place || $u->birth_date ? trim(($u->birth_place ?? '—').', '.($u->birth_date ? $u->birth_date->format('d M Y') : '—'), ', ') : 'Belum diisi',
            'verified' => (bool) $u->ypl_verified,
            'docs' => (int) $u->documents_count,
            'marks' => (int) $u->bookmarks_count,
            'url' => route('admin.users.update', $u),
          ];
          @endphp
          <tr class="hover:bg-slate-50/70 transition" data-extras='@json($extras)'>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                @if($u->avatar_path)
                  <img src="{{ asset('storage/'.$u->avatar_path) }}" class="w-9 h-9 rounded-xl object-cover shadow">
                @else
                  <span class="w-9 h-9 rounded-xl grid place-items-center text-white text-xs font-extrabold shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0">
                  <div class="font-bold truncate">{{ $u->name }}</div>
                  <span class="text-[10px] font-bold rounded-full px-2 py-0.5 border" style="{{ $roleStyle[$u->role] ?? $roleStyle['anggota'] }}">{{ $roleLabel[$u->role] ?? strtoupper($u->role) }}</span>
                  @if($u->audience)<span class="text-[10px] font-bold rounded-full px-2 py-0.5 border border-[var(--ypl-navy)]/25 bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] ml-1">{{ $audienceLabel[$u->audience] ?? ucfirst($u->audience) }}</span>@endif
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $u->created_at?->format('d M Y') }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-1.5">
                <button type="button" onclick="openUserDetail(this)" title="Lihat detail" class="w-8 h-8 rounded-full grid place-items-center border border-[var(--ypl-gray)] text-slate-500 hover:text-[var(--ypl-navy)] hover:border-[var(--ypl-navy)]/40 hover:bg-[var(--ypl-navy)]/5 transition">@include('components.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])</button>
                <button type="button" onclick="openUserEdit(this)" title="Edit user" class="w-8 h-8 rounded-full grid place-items-center border border-[var(--ypl-gray)] text-slate-500 hover:text-[var(--ypl-navy)] hover:border-[var(--ypl-navy)]/40 hover:bg-[var(--ypl-navy)]/5 transition">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4'])</button>
                @if($u->id !== auth()->id() && ! $u->isSuperAdmin())
                  <form method="post" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Hapus akun {{ $u->name }} secara permanen?" class="inline">@csrf @method('DELETE')
                    <button class="w-8 h-8 rounded-full grid place-items-center border border-[var(--ypl-gray)] text-slate-500 hover:text-[var(--ypl-red)] hover:border-[var(--ypl-red)]/40 hover:bg-[var(--ypl-red)]/5 transition" title="Hapus user">@include('components.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])</button>
                  </form>
                @else
                  <span class="w-8 h-8 rounded-full grid place-items-center border border-[var(--ypl-gray)] text-slate-300 cursor-not-allowed" title="Tidak dapat dihapus">@include('components.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])</span>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Tidak ada pengguna ditemukan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
</div>

{{-- Modal detail --}}
<div id="detail-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-[var(--ypl-navy-900)]/60 backdrop-blur-sm" onclick="closeDetail()"></div>
  <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="absolute top-0 inset-x-0 h-1.5" style="background:linear-gradient(90deg,var(--ypl-navy),var(--ypl-navy-500))"></div>
    <div class="flex items-center justify-between p-5 pb-2">
      <h2 class="font-extrabold text-[var(--ypl-navy)]">Detail Pengguna</h2>
      <button type="button" onclick="closeDetail()" aria-label="Tutup" class="w-8 h-8 rounded-full grid place-items-center text-slate-400 hover:bg-slate-100 hover:text-[var(--ypl-red)]">@include('components.icon', ['name' => 'x', 'class' => 'w-4 h-4'])</button>
    </div>
    <div class="px-5 pb-5">
      <div class="flex items-center gap-3">
        <img id="dt-avatar" class="hidden w-14 h-14 rounded-2xl object-cover shadow">
        <span id="dt-initial" class="hidden w-14 h-14 rounded-2xl grid place-items-center text-white text-xl font-extrabold shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))"></span>
        <div class="min-w-0">
          <div id="dt-name" class="font-extrabold text-lg truncate"></div>
          <span id="dt-role" class="text-[10px] font-bold rounded-full px-2 py-0.5 border inline-block mt-0.5"></span>
        </div>
      </div>
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 mt-4 text-sm">
        <div><dt class="text-[11px] text-slate-400 font-semibold">Email</dt><dd id="dt-email" class="font-bold break-all"></dd></div>
        <div><dt class="text-[11px] text-slate-400 font-semibold">Tanggal terdaftar</dt><dd id="dt-created" class="font-bold"></dd></div>
        <div><dt class="text-[11px] text-slate-400 font-semibold">Nomor WA</dt><dd id="dt-wa" class="font-bold"></dd></div>
        <div><dt class="text-[11px] text-slate-400 font-semibold">Tempat, tanggal lahir</dt><dd id="dt-birth" class="font-bold"></dd></div>
        <div class="col-span-2"><dt class="text-[11px] text-slate-400 font-semibold">Info akademik</dt><dd id="dt-academic" class="font-bold"></dd></div>
        <div><dt class="text-[11px] text-slate-400 font-semibold">Verifikasi YPL</dt><dd id="dt-verified" class="font-bold"></dd></div>
        <div><dt class="text-[11px] text-slate-400 font-semibold">Audiens</dt><dd id="dt-audience" class="font-bold">—</dd></div>
        <div class="grid grid-cols-2 gap-3"><div><dt class="text-[11px] text-slate-400 font-semibold">Dokumen</dt><dd id="dt-docs" class="font-bold"></dd></div><div><dt class="text-[11px] text-slate-400 font-semibold">Bookmark</dt><dd id="dt-marks" class="font-bold"></dd></div></div>
      </dl>
    </div>
  </div>
</div>

{{-- Modal edit --}}
<div id="edit-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-[var(--ypl-navy-900)]/60 backdrop-blur-sm" onclick="closeEdit()"></div>
  <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
    <div class="sticky top-0 flex items-center justify-between p-5 pb-3 bg-white/95 backdrop-blur rounded-t-3xl border-b border-slate-100">
      <h2 class="font-extrabold text-[var(--ypl-navy)]">Edit User</h2>
      <button type="button" onclick="closeEdit()" aria-label="Tutup" class="w-8 h-8 rounded-full grid place-items-center text-slate-400 hover:bg-slate-100 hover:text-[var(--ypl-red)]">@include('components.icon', ['name' => 'x', 'class' => 'w-4 h-4'])</button>
    </div>
    <form id="edit-form" method="post" action="{{ route('admin.users.update', $users->first() ?? 1) }}" class="p-5 space-y-3">@csrf @method('PUT')
      <div><label class="text-[13px] font-bold">Nama lengkap</label><input id="edit-name" name="name" required class="field mt-1 !py-2 !text-[13px]">
        @error('name')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>
      <div><label class="text-[13px] font-bold">Email</label><input id="edit-email" name="email" type="email" required class="field mt-1 !py-2 !text-[13px]">
        @error('email')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>

      {{-- Area peran: diisi JS sesuai aturan --}}
      <div id="role-sa-none" class="hidden"><p class="text-xs font-semibold text-[var(--ypl-red-600)] bg-[#fbe0e4] border border-[#f3b7c0] rounded-xl px-3 py-2">Akun Super Admin — peran dikunci, tidak dapat diubah.</p></div>
      <div id="role-sa-select" class="hidden">
        <label class="text-[13px] font-bold">Peran</label>
        <select name="role" class="field mt-1 !py-2 !text-[13px]">
          @foreach(['anggota', 'staff', 'admin'] as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
        </select>
      </div>
      <div id="role-admin-promote" class="hidden">
        <p class="text-xs font-semibold text-[var(--ypl-navy)] bg-[#dbe4f7] border border-[#b9c9ec] rounded-xl px-3 py-2">Akan dinaikkan menjadi <strong>ADMIN</strong>. Admin hanya dapat menaikkan peran.</p>
        <input type="hidden" name="role" value="admin">
      </div>
      <div id="role-admin-none" class="hidden"><p class="text-xs font-semibold text-[#33507e] bg-[#e2eaf6] border border-[#c3d2ea] rounded-xl px-3 py-2">Sudah berperan <strong>ADMIN</strong> — peran tidak berubah.</p></div>

      <div>
        <label class="text-[13px] font-bold">Audiens (BAB 11)</label>
        <select id="edit-audience" name="audience" class="field mt-1 !py-2 !text-[13px]">
          <option value="">— Umum (ikut peran sistem) —</option>
          @foreach(['siswa' => 'Siswa', 'orang_tua' => 'Orang Tua', 'guru' => 'Guru', 'karyawan' => 'Karyawan', 'pimpinan' => 'Pimpinan', 'mitra' => 'Mitra', 'publik' => 'Publik'] as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
          @endforeach
        </select>
        <p class="text-[11px] text-slate-400 mt-1">Menentukan konten unggulan di beranda &amp; dashboard pengguna.</p>
      </div>

      <div class="flex gap-2 pt-1">
        <button type="button" onclick="closeEdit()" class="btn-ghost flex-1">Batal</button>
        <button class="btn-primary flex-1 !py-2.5">Simpan</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
const EDITOR_ROLE = @json(auth()->user()->role);
function show(el){ el.classList.remove('hidden'); }
function hide(el){ el.classList.add('hidden'); }
function openModal(id){ document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id){ document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
function closeDetail(){ closeModal('detail-modal'); }
function closeEdit(){ closeModal('edit-modal'); }

function openUserDetail(btn){
  const e = JSON.parse(btn.closest('tr').dataset.extras);
  document.getElementById('dt-name').textContent = e.name;
  document.getElementById('dt-email').textContent = e.email;
  document.getElementById('dt-created').textContent = e.created;
  document.getElementById('dt-wa').textContent = e.wa;
  document.getElementById('dt-birth').textContent = e.birth;
  document.getElementById('dt-academic').textContent = e.academic;
  document.getElementById('dt-docs').textContent = e.docs;
  document.getElementById('dt-marks').textContent = e.marks;
  const ver = document.getElementById('dt-verified');
  ver.textContent = e.verified ? 'Terverifikasi YPL' : 'Belum verifikasi';
  ver.style.color = e.verified ? '#0f7a3d' : '#c90018';
  document.getElementById('dt-audience').textContent = e.audienceLabel || '—';
  const role = document.getElementById('dt-role');
  role.textContent = e.roleLabel; role.style = e.roleStyle;
  const av = document.getElementById('dt-avatar'), ini = document.getElementById('dt-initial');
  if (e.avatar) { av.src = e.avatar; show(av); hide(ini); }
  else { hide(av); ini.textContent = e.name.charAt(0).toUpperCase(); show(ini); }
  openModal('detail-modal');
}

function setRoleArea(e){
  const saNone = document.getElementById('role-sa-none');
  const saSel = document.getElementById('role-sa-select');
  const adPromote = document.getElementById('role-admin-promote');
  const adNone = document.getElementById('role-admin-none');
  [saNone, saSel, adPromote, adNone].forEach(hide);
  if (EDITOR_ROLE === 'superadmin') {
    if (e.role === 'superadmin') show(saNone);
    else { show(saSel); saSel.querySelector('select').value = e.role; }
  } else {
    if (e.role === 'admin') show(adNone);
    else show(adPromote);
  }
}

function openUserEdit(btn){
  const e = JSON.parse(btn.closest('tr').dataset.extras);
  document.getElementById('edit-form').action = e.url;
  document.getElementById('edit-name').value = e.name;
  document.getElementById('edit-email').value = e.email;
  document.getElementById('edit-audience').value = e.audience || '';
  setRoleArea(e);
  openModal('edit-modal');
}

function reopenEditFromErrors(){
  document.getElementById('edit-form').action = '/admin/pengguna/1';
  document.getElementById('edit-name').value = @json(old('name', ''));
  document.getElementById('edit-email').value = @json(old('email', ''));
  setRoleArea({ role: 'admin' });
  openModal('edit-modal');
}

document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeDetail(); closeEdit(); } });
@if($errors->has('name') || $errors->has('email')) document.addEventListener('DOMContentLoaded', reopenEditFromErrors); @endif
</script>
@endpush
@endsection