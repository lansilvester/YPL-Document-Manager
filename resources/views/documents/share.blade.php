@extends('layout')
@section('title', 'Bagikan')
@section('content')
<div class="max-w-lg mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl p-7 text-center">
  <div class="w-12 h-12 mx-auto rounded-2xl grid place-items-center text-white" style="background:linear-gradient(135deg,#002f80,#0046c0)">@include('components.icon', ['name' => 'share', 'class' => 'w-5 h-5'])</div>
  <h1 class="text-xl font-extrabold tracking-tight mt-3 text-[#002f80]">Bagikan Dokumen</h1>
  <p class="text-sm text-slate-500 mb-5">{{ $document->title }}</p>
  <div class="flex gap-2 mb-4 text-left">
    <input id="share-url" readonly value="{{ $url }}" class="field !rounded-full !py-2.5 text-xs">
    <button onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.textContent='Disalin!'" class="btn-primary shrink-0 !py-2.5">Salin</button>
  </div>
  <div class="grid grid-cols-2 gap-2 text-sm font-bold">
    <a class="btn-ghost" target="_blank" href="https://wa.me/?text={{ urlencode($document->title.' '.$url) }}">WhatsApp</a>
    <a class="btn-ghost" target="_blank" href="https://t.me/share/url?url={{ urlencode($url) }}&text={{ urlencode($document->title) }}">Telegram</a>
    <a class="btn-ghost" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}">Facebook</a>
    <a class="btn-ghost" target="_blank" href="https://twitter.com/intent/tweet?text={{ urlencode($document->title) }}&url={{ urlencode($url) }}">X / Twitter</a>
  </div>
</div>
@endsection
