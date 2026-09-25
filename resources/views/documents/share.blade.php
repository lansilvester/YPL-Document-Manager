@extends('layout')
@section('title', 'Bagikan')
@section('content')
<div class="max-w-lg mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-2">Bagikan: {{ $document->title }}</h1>
  <label class="text-sm">Tautan internal</label>
  <div class="flex gap-2 mb-4"><input id="share-url" readonly value="{{ $url }}" class="flex-1 border rounded px-3 py-2 text-sm"><button onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.textContent='Disalin!'" class="bg-emerald-700 text-white rounded px-3 text-sm">Salin</button></div>
  <div class="flex flex-wrap gap-2 text-sm">
    <a class="border rounded px-3 py-1.5" target="_blank" href="https://wa.me/?text={{ urlencode($document->title.' '.$url) }}">WhatsApp</a>
    <a class="border rounded px-3 py-1.5" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}">Facebook</a>
    <a class="border rounded px-3 py-1.5" target="_blank" href="https://twitter.com/intent/tweet?text={{ urlencode($document->title) }}&url={{ urlencode($url) }}">X / Twitter</a>
    <a class="border rounded px-3 py-1.5" target="_blank" href="https://t.me/share/url?url={{ urlencode($url) }}&text={{ urlencode($document->title) }}">Telegram</a>
  </div>
</div>
@endsection
