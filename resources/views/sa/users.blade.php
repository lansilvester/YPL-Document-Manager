@extends('layout')
@section('main_width', 'max-w-[1400px]')
@section('title', 'Kelola Pengguna')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Pusat Kendali SA', route('sa.dashboard')], ['Kelola Pengguna']]])
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-red-800),var(--ypl-red))">@include('components.icon', ['name' => 'users', 'class' => 'w-5 h-5'])</span>
  <div class="flex-1">
    <h1 class="page-title">Kelola Pengguna</h1>
    <p class="text-sm text-slate-500">Wilayah Super Admin — akun superadmin tidak dapat diubah.</p>
  </div>
  <a href="{{ route('sa.settings') }}" class="btn-ghost !py-2 text-[13px]">Pengaturan Web</a>
  <a href="{{ route('sa.dashboard') }}" class="btn-ghost !py-2 text-[13px]">Dasbor SA</a>
</div>
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm mt-5 overflow-hidden">
  <div class="divide-y divide-slate-100">
    @foreach($users as $u)
      <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-52">
          <div class="font-bold">{{ $u->name }} @if($u->isSuperAdmin())<span class="text-[11px] font-bold bg-[var(--ypl-red)]/10 text-[var(--ypl-red)] border border-[var(--ypl-red)]/25 rounded-full px-2 py-0.5">SUPER ADMIN</span>@endif</div>
          <div class="text-xs text-slate-400">{{ $u->email }} · peran: <strong class="text-[var(--ypl-navy)]">{{ $u->role }}</strong>@if($u->audience) · audiens: <strong class="text-[var(--ypl-navy)]">{{ $u->audience }}</strong>@endif</div>
        </div>
        @unless($u->isSuperAdmin())
          <form method="post" action="{{ route('sa.users.role', $u) }}" class="flex flex-wrap gap-2 items-center">@csrf @method('PUT')
            <select name="role" class="field !w-auto !rounded-full !py-1.5 !text-[13px]">
              @foreach(['anggota', 'staff', 'admin'] as $r)<option value="{{ $r }}" {{ $u->role === $r ? 'selected' : '' }}>{{ $r }}</option>@endforeach
            </select>
            <select name="audience" class="field !w-auto !rounded-full !py-1.5 !text-[13px]" aria-label="Audiens">
              <option value="">— umum —</option>
              @foreach(['siswa', 'orang_tua', 'guru', 'karyawan', 'pimpinan', 'mitra', 'publik'] as $a)<option value="{{ $a }}" {{ $u->audience === $a ? 'selected' : '' }}>{{ $a }}</option>@endforeach
            </select>
            <button class="text-[13px] font-bold bg-[var(--ypl-navy)] text-white rounded-full px-4 py-1.5">Simpan</button>
          </form>
          <form method="post" action="{{ route('sa.users.destroy', $u) }}" data-confirm="Hapus akun ini secara permanen?" class="inline">@csrf @method('DELETE')<button class="text-[13px] font-bold text-[var(--ypl-red)] hover:underline">Hapus</button></form>
        @endunless
      </div>
    @endforeach
  </div>
  <div class="p-4 border-t border-slate-100">{{ $users->links() }}</div>
</div>
@endsection
