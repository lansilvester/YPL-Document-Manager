@php
$map = [
  'view' => ['Lihat', 'background:#e0effe;color:#0369a1;border-color:#bae0fd'],
  'upload' => ['Unggah', 'background:#dcfce7;color:#15803d;border-color:#bbf7d0'],
  'update' => ['Ubah', 'background:#fef3c7;color:#b45309;border-color:#fde68a'],
  'delete' => ['Hapus', 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0'],
  'download' => ['Unduh', 'background:#ede9fe;color:#6d28d9;border-color:#ddd6fe'],
  'bookmark' => ['Bookmark', 'background:#fce7f3;color:#be185d;border-color:#fbcfe8'],
  'like' => ['Suka', 'background:#ffedd5;color:#c2410c;border-color:#fed7aa'],
  'register' => ['Daftar', 'background:#e0e7ff;color:#4338ca;border-color:#c7d2fe'],
  'login' => ['Masuk', 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6'],
  'role_approve' => ['Setujui Peran', 'background:#dbe4f7;color:#002f80;border-color:#b9c9ec'],
  'user_delete' => ['Hapus Akun', 'background:#f3d9d4;color:#7c2d12;border-color:#e8b4a6'],
  'settings_update' => ['Pengaturan', 'background:#e2e6ec;color:#263245;border-color:#c2cad6'],
];
[$label, $style] = $map[$action] ?? [ucfirst(str_replace('_', ' ', $action)), 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6'];
@endphp
<span class="inline-block text-[11px] font-bold border rounded-full px-2.5 py-0.5 whitespace-nowrap" style="{{ $style }}">{{ $label }}</span>
