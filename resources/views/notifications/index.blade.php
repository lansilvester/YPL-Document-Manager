@extends('layout')
@section('title', 'Notifikasi')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dashboard', route('dashboard')], ['Notifikasi']]])
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0 grad-navy">@include('components.icon', ['name' => 'bell', 'class' => 'w-5 h-5'])</span>
  <div class="flex-1">
    <h1 class="page-title">Notifikasi</h1>
    <p class="text-sm text-slate-500">Pemberitahuan governance: dokumen memasuki masa review atau diarsipkan.</p>
  </div>
  @if(auth()->user()->unreadNotifications->isNotEmpty())
    <form method="post" action="{{ route('notifications.read-all') }}">@csrf
      <button class="btn-ghost !py-1.5 !text-[13px]">Tandai semua terbaca</button>
    </form>
  @endif
</div>

<div class="mt-5 space-y-2">
  @forelse($notifications as $n)
    @php($unread = $n->read_at === null)
    <div class="bg-white rounded-2xl border {{ $unread ? 'border-[var(--ypl-navy)]/40 shadow-sm' : 'border-[var(--ypl-gray)]' }} p-4 flex items-start gap-3">
      <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 {{ ($n->data['kind'] ?? '') === 'archived' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-600' }}">
        @include('components.icon', ['name' => ($n->data['kind'] ?? '') === 'archived' ? 'archive' : 'clock', 'class' => 'w-5 h-5'])
      </span>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <p class="font-bold text-slate-800 truncate">{{ $n->data['title'] ?? 'Dokumen' }}</p>
          @if($unread)<span class="text-[10px] font-extrabold tracking-widest text-[var(--ypl-red)] shrink-0">BARU</span>@endif
        </div>
        <p class="text-sm text-slate-500">{{ $n->data['message'] ?? '' }}</p>
        <p class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->translatedFormat('d M Y · H:i') }}</p>
      </div>
      <form method="post" action="{{ route('notifications.read', $n->id) }}" class="shrink-0">@csrf
        <button class="{{ $unread ? 'btn-primary' : 'btn-ghost' }} !py-1.5 !text-[13px]">Buka</button>
      </form>
    </div>
  @empty
    <div class="bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-10 text-center">
      <p class="text-slate-500 font-semibold">Belum ada notifikasi.</p>
      <p class="text-sm text-slate-400 mt-1">Anda akan diberi tahu saat dokumen yang Anda kelola perlu ditinjau.</p>
    </div>
  @endforelse
</div>

<div class="mt-5">{{ $notifications->links() }}</div>
@endsection
