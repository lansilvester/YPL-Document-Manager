@extends('layout')
@section('title', 'Kelola Pengguna')
@section('content')
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#7a0010,#c90018)">@include('components.icon', ['name' => 'users', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="text-2xl font-extrabold tracking-tight text-[#002f80]">Kelola Pengguna</h1>
    <p class="text-sm text-slate-500">Wilayah Super Admin — akun superadmin tidak dapat diubah.</p>
  </div>
</div>
<div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm mt-5 overflow-hidden">
  <div class="divide-y divide-slate-100">
    @foreach($users as $u)
      <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-52">
          <div class="font-bold">{{ $u->name }} @if($u->isSuperAdmin())<span class="text-[11px] font-bold bg-[#c90018]/10 text-[#c90018] border border-[#c90018]/25 rounded-full px-2 py-0.5">SUPER ADMIN</span>@endif</div>
          <div class="text-xs text-slate-400">{{ $u->email }} · peran: <strong class="text-[#002f80]">{{ $u->role }}</strong></div>
        </div>
        @unless($u->isSuperAdmin())
          <form method="post" action="{{ route('sa.users.role', $u) }}" class="flex gap-2">@csrf @method('PUT')
            <select name="role" class="field !w-auto !rounded-full !py-1.5 !text-[13px]">
              @foreach(['anggota', 'staff', 'admin'] as $r)<option value="{{ $r }}" {{ $u->role === $r ? 'selected' : '' }}>{{ $r }}</option>@endforeach
            </select>
            <button class="text-[13px] font-bold bg-[#002f80] text-white rounded-full px-4 py-1.5">Simpan</button>
          </form>
          <form method="post" action="{{ route('sa.users.destroy', $u) }}" onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }}?')">@csrf @method('DELETE')<button class="text-[13px] font-bold text-[#c90018] hover:underline">Hapus</button></form>
        @endunless
      </div>
    @endforeach
  </div>
  <div class="p-4 border-t border-slate-100">{{ $users->links() }}</div>
</div>
@endsection
