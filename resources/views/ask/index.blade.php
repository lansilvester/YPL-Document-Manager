@extends('layout')
@section('title', 'Ask YPL')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">ASK YPL</p>
<h1 class="page-title">Tanya knowledge YPL</h1>
<p class="text-sm text-slate-500 mt-1">Dijawab dari dokumen resmi sesuai otoritas Anda — selalu dengan sitasi sumber.</p>

<form action="{{ route('ask.query') }}" method="post" class="mt-5 flex gap-2 bg-white rounded-full p-1.5 shadow-lg border border-[#d6d6d6]">@csrf
  <input name="question" value="{{ $question ?? '' }}" required minlength="3" maxlength="500" placeholder="cth: Bagaimana cara mengajukan purchase request?" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
  <button class="shrink-0 rounded-full font-bold text-sm px-6 py-2.5 text-white transition hover:brightness-110" style="background:linear-gradient(135deg,#002f80,#0046c0)">Tanya</button>
</form>

@if(!empty($examples))
  <div class="flex flex-wrap gap-2 mt-3">
    @foreach($examples as $ex)
      <form action="{{ route('ask.query') }}" method="post" class="inline">@csrf<input type="hidden" name="question" value="{{ $ex }}"><button class="text-xs font-semibold bg-white border border-[#d6d6d6] rounded-full px-3 py-1.5 hover:border-[#002f80] hover:text-[#002f80] transition">{{ $ex }}</button></form>
    @endforeach
  </div>
@endif

@if(isset($answer))
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 mt-5">
    <div class="flex items-center gap-2 text-xs font-bold tracking-widest {{ $found ? 'text-[#002f80]' : 'text-amber-600' }}">
      {{ $found ? (($ai ?? false) ? 'JAWABAN AI BERBASIS SUMBER 🧪' : 'JAWABAN BERBASIS SUMBER') : 'SUMBER TIDAK DITEMUKAN' }}
    </div>
    <div class="prose max-w-none mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $answer }}</div>
  </div>

  @if($found)
    <h2 class="section-label mt-6 mb-2">SUMBER ({{ count($sources) }})</h2>
    <div class="grid sm:grid-cols-2 gap-3">
      @foreach($sources as $hit)
        <a href="{{ route('documents.show', $hit['document']) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block">
          <div class="flex flex-wrap gap-1.5 text-[10px] font-bold">
            <span class="bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $hit['document']->doc_type ?? 'dokumen')) }}</span>
            <span class="{{ \App\Models\Document::statusBadgeFor($hit['document']->status) }} px-2 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $hit['document']->status)) }}</span>
            <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-full">v{{ $hit['document']->version }}</span>
          </div>
          <div class="font-bold text-sm text-slate-900 mt-2 leading-snug">{{ $hit['document']->title }}</div>
          <p class="text-[13px] text-slate-500 mt-1 line-clamp-2">“{{ $hit['passage'] }}”</p>
        </a>
      @endforeach
    </div>
  @endif
@endif

<p class="text-[11px] text-slate-400 mt-6">Ask YPL hanya mengutip dokumen yang boleh Anda akses (publik + sesuai peran). AI bukan sumber kebenaran — dokumen resmi tetap acuan.</p>
@endsection
