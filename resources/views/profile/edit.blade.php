@extends('layout')
@section('title', 'Profil Saya')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Profil Saya']]])
@php
$roleStyle = [
  'superadmin' => 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0',
  'admin' => 'background:#dbe4f7;color:#002f80;border-color:#b9c9ec',
  'staff' => 'background:#e2eaf6;color:#33507e;border-color:#c3d2ea',
  'anggota' => 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6',
][$user->role] ?? 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6';
$roleLabel = ['superadmin' => 'SUPER ADMIN', 'admin' => 'ADMIN', 'staff' => 'STAFF', 'anggota' => 'ANGGOTA'][$user->role] ?? strtoupper($user->role);
@endphp

{{-- Kartu identitas --}}
<div class="rounded-3xl overflow-hidden text-white shadow-xl shadow-[var(--ypl-navy)]/25" style="background:linear-gradient(120deg,var(--ypl-navy-900),var(--ypl-navy) 60%,var(--ypl-navy-500))">
  <div class="p-6 sm:p-8 flex flex-wrap items-center gap-5">
    @if($user->avatar_path)
      <img src="{{ asset('storage/'.$user->avatar_path) }}" class="w-20 h-20 rounded-3xl object-cover shadow-lg border-2 border-white/40">
    @else
      <span class="w-20 h-20 rounded-3xl grid place-items-center text-3xl font-extrabold bg-white/15 border-2 border-white/30">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
    @endif
    <div class="flex-1 min-w-52">
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="page-title">{{ $user->name }}</h1>
        <span class="text-[11px] font-bold border rounded-full px-2.5 py-0.5" style="{{ $roleStyle }}">{{ $roleLabel }}</span>
      </div>
      <p class="text-sm text-slate-300 mt-0.5">{{ $user->academic_info ?? $user->email }}</p>
      <p class="text-xs text-slate-400 mt-1 inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'clock', 'class' => 'w-3.5 h-3.5']) Terdaftar sejak {{ $user->created_at->format('d M Y') }}</p>
    </div>
    <a href="#" onclick="openProfileModal(event)" class="bg-white text-[var(--ypl-navy)] rounded-full px-5 py-2 text-[13px] font-bold inline-flex items-center gap-2 hover:brightness-95">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Edit profile</a>
  </div>
  <div class="grid grid-cols-2 lg:grid-cols-4 border-t border-white/15 divide-x divide-white/15 text-center">
    @foreach([['Unggahan', $stats['unggahan'], 'file'], ['Dibaca orang', $stats['dilihat'], 'eye'], ['Bookmarkku', $stats['bookmark'], 'star'], ['Dokumen kubaca', $stats['dibaca'], 'book']] as [$l, $v, $i])
      <div class="p-4"><div class="flex justify-center opacity-70">@include('components.icon', ['name' => $i, 'class' => 'w-4 h-4'])</div><div class="text-xl font-extrabold tabular-nums">{{ number_format($v) }}</div><div class="text-[11px] text-slate-300 font-semibold">{{ $l }}</div></div>
    @endforeach
  </div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mt-5">
  <div class="lg:col-span-2 space-y-5">
    {{-- Informasi --}}
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
      <h2 class="font-extrabold text-[var(--ypl-navy)] mb-3">Informasi Pribadi</h2>
      <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div><dt class="text-xs text-slate-400 font-semibold">Nama lengkap</dt><dd class="font-bold">{{ $user->name }}</dd></div>
        <div><dt class="text-xs text-slate-400 font-semibold">Email</dt><dd class="font-bold">{{ $user->email }}</dd></div>
        <div><dt class="text-xs text-slate-400 font-semibold">Tempat, tanggal lahir</dt><dd class="font-bold">{{ $user->birth_place || $user->birth_date ? trim(($user->birth_place ?? '—').', '.($user->birth_date ? $user->birth_date->format('d M Y') : '—'), ', ') : 'Belum diisi' }}</dd></div>
        <div><dt class="text-xs text-slate-400 font-semibold">Nomor WA</dt><dd class="font-bold">{{ $user->wa_number ?? 'Belum diisi' }}</dd></div>
        <div><dt class="text-xs text-slate-400 font-semibold">Info akademik</dt><dd class="font-bold">{{ $user->academic_info ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-400 font-semibold">Status verifikasi</dt><dd class="font-bold">{{ $user->ypl_verified ? 'Terverifikasi YPL' : 'Belum terverifikasi' }}</dd></div>
      </dl>
    </div>

    {{-- Dokumen yang diunggah/diubah --}}
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-extrabold text-[var(--ypl-navy)]">Dokumen Saya ({{ $stats['unggahan'] }})</h2>
        <a href="{{ route('documents.my') }}" class="text-xs font-bold text-[var(--ypl-navy)] hover:underline">Kelola semua</a>
      </div>
      <div class="divide-y divide-slate-100">
        @forelse($myDocs as $d)
          <div class="p-3.5 flex items-center gap-3">
            <span class="w-9 h-11 rounded-lg grid place-items-center text-white shrink-0" style="background:linear-gradient(160deg,var(--ypl-navy-800),var(--ypl-navy-600))">@include('components.icon', ['name' => 'file', 'class' => 'w-4 h-4'])</span>
            <div class="flex-1 min-w-0">
              <a href="{{ route('documents.show', $d) }}" class="font-bold text-sm hover:text-[var(--ypl-navy)] truncate block">{{ $d->title }}</a>
              <div class="text-xs text-slate-400">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ $d->updated_at->format('d M Y') }} · {{ number_format($d->views_count) }} dibaca</div>
            </div>
          </div>
        @empty
          <p class="p-6 text-center text-sm text-slate-400">Belum ada unggahan. <a href="{{ route('documents.create') }}" class="text-[var(--ypl-navy)] font-bold underline">Unggah PDF</a> atau <a href="{{ route('editor.create') }}" class="text-[var(--ypl-navy)] font-bold underline">tulis dokumen</a>.</p>
        @endforelse
      </div>
    </div>

    {{-- Riwayat dibaca --}}
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100"><h2 class="font-extrabold text-[var(--ypl-navy)]">Riwayat Dibaca</h2></div>
      <div class="divide-y divide-slate-100">
        @forelse($viewed as $log)
          @if($log->document)
            <div class="p-3.5 flex items-center gap-3">
              <span class="text-slate-300">@include('components.icon', ['name' => 'clock', 'class' => 'w-4 h-4'])</span>
              <div class="flex-1 min-w-0">
                <a href="{{ route('documents.show', $log->document) }}" class="font-bold text-sm hover:text-[var(--ypl-navy)] truncate block">{{ $log->document->title }}</a>
                <div class="text-xs text-slate-400">Dibaca {{ $log->created_at->diffForHumans() }}</div>
              </div>
            </div>
          @endif
        @empty
          <p class="p-6 text-center text-sm text-slate-400">Belum ada riwayat bacaan. Mulai dari <a href="{{ route('home') }}" class="text-[var(--ypl-navy)] font-bold underline">galeri dokumen</a>.</p>
        @endforelse
      </div>
    </div>

    {{-- Bookmark saya --}}
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-extrabold text-[var(--ypl-navy)] flex items-center gap-2">@include('components.icon', ['name' => 'star', 'class' => 'w-4 h-4']) Bookmark Saya ({{ $stats['bookmark'] }})</h2>
        <a href="{{ route('bookmarks.index') }}" class="text-xs font-bold text-[var(--ypl-navy)] hover:underline">Lihat semua bookmark</a>
      </div>
      <div class="divide-y divide-slate-100">
        @forelse($markDocs as $d)
          <div class="p-3.5 flex items-center gap-3">
            <span class="text-[var(--ypl-red)]">@include('components.icon', ['name' => 'heart', 'class' => 'w-4 h-4'])</span>
            <div class="flex-1 min-w-0">
              <a href="{{ route('documents.show', $d) }}" class="font-bold text-sm hover:text-[var(--ypl-navy)] truncate block">{{ $d->title }}</a>
              <div class="text-xs text-slate-400">{{ $d->category->name ?? 'Tanpa kategori' }}</div>
            </div>
          </div>
        @empty
          <p class="p-6 text-center text-sm text-slate-400">Belum ada bookmark. Buka dokumen lalu tekan <strong>Simpan ke Bookmark</strong>, atau <a href="{{ route('bookmarks.index') }}" class="text-[var(--ypl-navy)] font-bold underline">lihat daftar bookmark</a>.</p>
        @endforelse
      </div>
    </div>
  </div>

  <aside class="space-y-5">
    {{-- Preferensi audiens (BAB 11) --}}
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
      <h2 class="font-extrabold text-[var(--ypl-navy)] flex items-center gap-2">@include('components.icon', ['name' => 'cap', 'class' => 'w-4 h-4']) Preferensi Audiens</h2>
      @if($user->audience)
        <p class="text-sm text-slate-500 mt-2">Audiensmu saat ini: <strong class="text-[var(--ypl-navy)]">{{ str_replace('_', ' ', $user->audience) }}</strong>.</p>
      @endif
      @if($pendingRequest && $pendingRequest->requested_audience)
        <p class="text-sm text-slate-500 mt-2">Pengajuan audiens <strong>{{ str_replace('_', ' ', $pendingRequest->requested_audience) }}</strong> sedang menunggu persetujuan Admin YPL.</p>
      @else
        <p class="text-xs text-slate-400 mt-1 mb-3">Pilih pengalaman yang paling sesuai. Ditetapkan setelah disetujui Admin.</p>
        <form method="post" action="{{ route('profile.role-request') }}" class="space-y-3">@csrf
          <div><label class="text-[13px] font-bold">Audiens</label><select name="requested_audience" class="field mt-1 !py-2 !text-[13px]" required>
            <option value="">— Pilih audiens —</option>
            @foreach(['siswa' => 'Siswa', 'orang_tua' => 'Orang Tua', 'guru' => 'Guru', 'karyawan' => 'Karyawan', 'pimpinan' => 'Pimpinan', 'mitra' => 'Mitra', 'publik' => 'Publik'] as $val => $label)
              <option value="{{ $val }}" {{ $user->audience === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select></div>
          <button class="btn-ghost w-full">Kirim Pengajuan Audiens</button>
        </form>
      @endif
    </div>

    {{-- Peningkatan peran --}}
    @if($user->role === 'anggota')
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
      <h2 class="font-extrabold text-[var(--ypl-navy)] flex items-center gap-2">@include('components.icon', ['name' => 'shield', 'class' => 'w-4 h-4']) Peningkatan Peran</h2>
      @if($pendingRequest && $pendingRequest->requested_role)
        <p class="text-sm text-slate-500 mt-2">Pengajuanmu menjadi <strong>{{ $pendingRequest->requested_role }}</strong> sedang menunggu persetujuan Admin YPL.</p>
      @else
        <p class="text-xs text-slate-400 mt-1 mb-3">Peran Staff/Admin memerlukan persetujuan. Peran admin hanya dapat disetujui Super Admin.</p>
        <form method="post" action="{{ route('profile.role-request') }}" class="space-y-3">@csrf
          <div><label class="text-[13px] font-bold">Minta peran</label><select name="requested_role" class="field mt-1 !py-2 !text-[13px]"><option value="staff">Staff — kelola perpustakaan</option><option value="admin">Admin — kelola yayasan</option></select></div>
          <div><label class="text-[13px] font-bold">Alasan (opsional)</label><textarea name="reason" rows="2" placeholder="cth: Saya pustakawan SMP Lokon…" class="field mt-1 !py-2 !text-[13px]"></textarea></div>
          <button class="btn-ghost w-full">Kirim Pengajuan</button>
        </form>
      @endif
    </div>
    @endif
  </aside>
</div>

{{-- Modal edit profil --}}
<div id="profile-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-[var(--ypl-navy-900)]/60 backdrop-blur-sm" onclick="closeProfileModal()"></div>
  <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="sticky top-0 flex items-center justify-between p-5 pb-3 bg-white/95 backdrop-blur rounded-t-3xl border-b border-slate-100">
      <h2 class="font-extrabold text-[var(--ypl-navy)]">Edit profile</h2>
      <button type="button" onclick="closeProfileModal()" aria-label="Tutup" class="w-8 h-8 rounded-full grid place-items-center text-slate-400 hover:bg-slate-100 hover:text-[var(--ypl-red)]">@include('components.icon', ['name' => 'x', 'class' => 'w-4 h-4'])</button>
    </div>
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-5 space-y-3">@csrf @method('PUT')
      <p class="text-xs text-slate-400">Peran dan tanggal terdaftar dikunci dan tidak dapat diubah.</p>
      <div class="flex items-center gap-3">
        @if($user->avatar_path)
          <img src="{{ asset('storage/'.$user->avatar_path) }}" class="w-14 h-14 rounded-2xl object-cover shadow">
        @else
          <span class="w-14 h-14 rounded-2xl grid place-items-center text-white text-xl font-extrabold" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">{{ strtoupper(substr($user->name,0,1)) }}</span>
        @endif
        <div><label class="text-[13px] font-bold">Foto profil <span class="font-normal text-slate-400">(JPG/PNG/GIF/WebP, maks 2 MB)</span></label><input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="field mt-1 !py-2 !text-[13px] file:mr-3 file:rounded-full file:border-0 file:bg-[var(--ypl-navy)]/10 file:text-[var(--ypl-navy)] file:text-xs file:font-bold file:px-4 file:py-1.5">
        <p id="avatar-error" class="hidden text-xs font-semibold text-[var(--ypl-red)] mt-1"></p>
        @error('avatar')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>
      </div>
      <div><label class="text-[13px] font-bold">Nama lengkap</label><input name="name" required value="{{ old('name', $user->name) }}" class="field mt-1 !py-2 !text-[13px]"></div>
      <div class="grid grid-cols-2 gap-2">
        <div><label class="text-[13px] font-bold">Tempat lahir</label><input name="birth_place" value="{{ old('birth_place', $user->birth_place) }}" placeholder="cth: Tomohon" class="field mt-1 !py-2 !text-[13px]"></div>
        <div><label class="text-[13px] font-bold">Tanggal lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}" class="field mt-1 !py-2 !text-[13px]"></div>
      </div>
      <div><label class="text-[13px] font-bold">Nomor WA</label><input id="wa-input" name="wa_number" value="{{ old('wa_number', $user->wa_number) }}" placeholder="cth: 089612345678" inputmode="tel" class="field mt-1 !py-2 !text-[13px]">
        <div class="mt-1.5 rounded-xl bg-[var(--ypl-navy)]/[.04] border border-[var(--ypl-navy)]/10 px-3 py-2 text-[11px] leading-relaxed text-slate-500">
          Tulis dengan format mana pun — otomatis dirapikan menjadi <strong>+62…</strong>:
          <span class="font-mono">0896…</span>, <span class="font-mono">+62896…</span>, <span class="font-mono">62896…</span>, atau <span class="font-mono">896…</span> (spasi/tanda hubung boleh).
          <span id="wa-preview" class="hidden mt-1 font-bold text-[var(--ypl-navy)]"></span>
        </div>
        @error('wa_number')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>
      <div><label class="text-[13px] font-bold">Info akademik</label><input name="academic_info" value="{{ old('academic_info', $user->academic_info) }}" class="field mt-1 !py-2 !text-[13px]"></div>
      <div class="flex gap-2 pt-1">
        <button type="button" onclick="closeProfileModal()" class="btn-ghost flex-1">Batal</button>
        <button class="btn-primary flex-1 !py-2.5">Simpan Perubahan</button>
      </div>
    </form>
    <div class="border-t border-slate-100 px-5 py-4">
      <button type="button" id="btn-toggle-password" onclick="togglePasswordForm()" aria-expanded="false" class="w-full flex items-center justify-between gap-2 rounded-xl border border-[var(--ypl-gray)] px-4 py-2.5 text-[13px] font-bold text-[var(--ypl-navy)] hover:bg-[var(--ypl-navy)]/5 hover:border-[var(--ypl-navy)]/40 transition">
        <span class="inline-flex items-center gap-2">@include('components.icon', ['name' => 'lock', 'class' => 'w-4 h-4']) Ganti kata sandi</span>
        <span id="pw-caret" class="inline-flex items-center transition-transform duration-200">@include('components.icon', ['name' => 'chev-down', 'class' => 'w-4 h-4'])</span>
      </button>
      <div id="password-form-wrap" class="hidden mt-3">
        <form method="post" action="{{ route('profile.password') }}" class="space-y-3">@csrf @method('PUT')
          @if(session('password_changed'))<p class="text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">Kata sandi berhasil diperbarui.</p>@endif
          <div><label class="text-[13px] font-bold">Kata sandi saat ini</label><input type="password" name="current_password" required autocomplete="current-password" class="field mt-1 !py-2 !text-[13px]">
            @error('current_password')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>
          <div><label class="text-[13px] font-bold">Kata sandi baru</label><input type="password" name="password" required autocomplete="new-password" class="field mt-1 !py-2 !text-[13px]">
            @error('password')<p class="text-xs font-semibold text-[var(--ypl-red)] mt-1">{{ $message }}</p>@enderror</div>
          <div><label class="text-[13px] font-bold">Konfirmasi kata sandi baru</label><input type="password" name="password_confirmation" required autocomplete="new-password" class="field mt-1 !py-2 !text-[13px]"></div>
          <p class="text-[11px] text-slate-400">Min. 8 karakter: huruf besar, huruf kecil, angka, dan karakter khusus.</p>
          <button class="btn-primary w-full !py-2.5">Ganti Kata Sandi</button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function openProfileModal(e) { if (e) e.preventDefault(); document.getElementById('profile-modal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeProfileModal() { document.getElementById('profile-modal').classList.add('hidden'); document.body.style.overflow = ''; }
function togglePasswordForm() {
  var wrap = document.getElementById('password-form-wrap');
  var caret = document.getElementById('pw-caret');
  var btn = document.getElementById('btn-toggle-password');
  var open = wrap.classList.toggle('hidden');
  caret.style.transform = open ? '' : 'rotate(180deg)';
  btn.setAttribute('aria-expanded', open ? 'false' : 'true');
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeProfileModal(); });
// Pratinjau normalisasi nomor WA secara langsung (logika sama dengan server)
(function () {
  var input = document.getElementById('wa-input');
  var prev = document.getElementById('wa-preview');
  if (!input || !prev) return;
  function norm(v) {
    v = v.trim();
    var plus = v.charAt(0) === '+';
    var d = v.replace(/\D/g, '');
    if (!d) return null;
    var n = plus ? '+' + d : (d.indexOf('62') === 0 ? '+' + d : (d.charAt(0) === '0' ? '+62' + d.slice(1) : '+62' + d));
    return /^\+62\d{8,13}$/.test(n) ? n : null;
  }
  input.addEventListener('input', function () {
    var r = norm(input.value);
    if (!input.value.trim()) { prev.classList.add('hidden'); return; }
    prev.classList.remove('hidden');
    prev.textContent = r ? 'Tersimpan sebagai: ' + r : 'Format belum dikenali — contoh: 089612345678';
    prev.style.color = r ? '#002f80' : '#c90018';
  });
})();
// Pemeriksaan dini di browser: jenis & ukuran file, sebelum dikirim ke server
document.getElementById('avatar-input').addEventListener('change', function (e) {
  var err = document.getElementById('avatar-error');
  err.classList.add('hidden'); err.textContent = '';
  var f = e.target.files[0];
  if (!f) return;
  var okType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(f.type);
  if (!okType) {
    err.textContent = 'File "' + f.name + '" bukan gambar yang didukung. Gunakan JPG, PNG, GIF, atau WebP (foto iPhone HEIC perlu dikonversi dulu).';
    err.classList.remove('hidden'); e.target.value = '';
  } else if (f.size > 2 * 1024 * 1024) {
    err.textContent = 'Ukuran foto ' + (f.size / 1048576).toFixed(1) + ' MB melebihi batas 2 MB. Kecilkan dulu fotonya.';
    err.classList.remove('hidden'); e.target.value = '';
  }
});
@if($errors->any()) document.addEventListener('DOMContentLoaded', openProfileModal); @endif
@if($errors->has('current_password') || $errors->has('password'))
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('password-form-wrap').classList.remove('hidden');
  document.getElementById('pw-caret').style.transform = 'rotate(180deg)';
  document.getElementById('btn-toggle-password').setAttribute('aria-expanded', 'true');
});
@endif
</script>
@endpush
@endsection
