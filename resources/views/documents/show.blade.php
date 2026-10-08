@extends('layout')
@section('title', $document->title)

@section('hero')
@if(!$document->isGovernance())
<div class="text-white shadow-xl shadow-[#002f80]/25" style="background:linear-gradient(120deg,#000f2e,#002f80 60%,#0a4fc4)">
  <div class="max-w-6xl mx-auto px-4 py-6 sm:py-8 w-full">
    <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wider">
      <span class="bg-white/15 border border-white/20 px-3 py-1 rounded-full">{{ $document->category->name ?? 'Tanpa kategori' }}</span>
      <span class="bg-white/15 border border-white/20 px-3 py-1 rounded-full">{{ ucfirst($document->visibility) }}</span>
      <span class="bg-white/15 border border-white/20 px-3 py-1 rounded-full">{{ $document->source_type === 'editor' ? 'Editor' : 'PDF' }}</span>
      <span class="bg-green-400/25 border border-green-300/40 px-3 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $document->status)) }}</span>
      <span class="bg-white/15 border border-white/20 px-3 py-1 rounded-full">v{{ $document->version }}</span>
      @if($document->doc_type)<span class="bg-white/15 border border-white/20 px-3 py-1 rounded-full">{{ strtoupper(str_replace('_', ' ', $document->doc_type)) }}</span>@endif
    </div>
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-3">{{ $document->title }}</h1>
    @if($document->subtitle)<p class="text-slate-200 text-sm mt-1 font-reading italic">{{ $document->subtitle }}</p>@endif
    @if($document->tags->isNotEmpty())
      <div class="flex flex-wrap gap-1.5 mt-2">@include('components.tag-badges', ['tags' => $document->tags])</div>
    @endif
    <p class="text-slate-300 text-sm mt-2 inline-flex items-center gap-1.5">Oleh <strong class="text-white">{{ $document->user->name }}</strong> · <span class="inline-flex items-center gap-1">@include('components.icon', ['name' => 'eye', 'class' => 'w-3.5 h-3.5']) {{ number_format($document->views_count) }}</span> · <span class="inline-flex items-center gap-1">@include('components.icon', ['name' => 'download', 'class' => 'w-3.5 h-3.5']) {{ number_format($document->downloads_count) }}</span></p>
    @if($document->description)<p class="text-slate-200 text-sm mt-2 max-w-2xl">{{ $document->description }}</p>@endif
    <div class="flex flex-wrap gap-2 mt-4 text-[13px] font-bold">
      <a href="{{ route('reader.show', $document) }}" class="bg-[#c90018] text-white rounded-full px-5 py-2 shadow hover:brightness-110 inline-flex items-center gap-2">📖 Baca Imersif</a>
      @if($document->file_path && \App\Models\Setting::feature('download'))
        @auth
          <a href="{{ route('documents.download', $document) }}" class="bg-white text-[#002f80] rounded-full px-5 py-2 shadow hover:brightness-95 inline-flex items-center gap-2">@include('components.icon', ['name' => 'download', 'class' => 'w-4 h-4']) Unduh PDF</a>
          <a href="{{ route('documents.downloadHtml', $document) }}" class="rounded-full px-5 py-2 border border-white/40 bg-white/10 backdrop-blur hover:bg-white/20 inline-flex items-center gap-2">@include('components.icon', ['name' => 'file', 'class' => 'w-4 h-4']) Unduh HTML</a>
        @else
          <a href="{{ route('login') }}" class="bg-white/20 border border-white/40 text-white rounded-full px-5 py-2 inline-flex items-center gap-2 hover:bg-white/30">@include('components.icon', ['name' => 'lock', 'class' => 'w-4 h-4']) Masuk untuk mengunduh</a>
        @endauth
      @endif
      @if(\App\Models\Setting::feature('like'))
      @auth
        <form method="post" action="{{ route('documents.like', $document) }}" class="inline">@csrf<button class="rounded-full px-5 py-2 border backdrop-blur inline-flex items-center gap-2 {{ $liked ? 'bg-[#c90018] border-[#c90018] text-white' : 'border-white/40 bg-white/10 hover:bg-white/20' }}">@include('components.icon', ['name' => 'heart', 'class' => 'w-4 h-4']) {{ number_format($likesCount) }} Suka</button></form>
      @else
        <span class="rounded-full px-5 py-2 border border-white/40 bg-white/10 inline-flex items-center gap-2 opacity-90">@include('components.icon', ['name' => 'heart', 'class' => 'w-4 h-4']) {{ number_format($likesCount) }} · <a href="{{ route('login') }}" class="underline">Masuk untuk menyukai</a></span>
      @endauth
      @endif
      @if($canEmbed && \App\Models\Setting::feature('embed'))
        <button type="button" onclick="openEmbedModal(event)" class="rounded-full px-5 py-2 border border-white/40 bg-white/10 backdrop-blur hover:bg-white/20 inline-flex items-center gap-2">@include('components.icon', ['name' => 'code', 'class' => 'w-4 h-4']) Sematkan</button>
      @endif
      @auth
        @if(\App\Models\Setting::feature('bookmark'))
          @include('components.bookmark-button', [
            'document' => $document,
            'marked' => $isMarked,
            'labelOn' => 'Tersimpan di Bookmark',
            'labelOff' => 'Simpan ke Bookmark',
            'btnClass' => 'rounded-full px-5 py-2 border inline-flex items-center gap-2 backdrop-blur cursor-pointer shadow-sm',
            'iconClass' => 'w-4 h-4',
            'activeClass' => 'bg-amber-400/25 border-amber-300 text-amber-200 hover:bg-amber-400/35',
            'inactiveClass' => 'border-white/40 bg-white/10 hover:bg-white/20 text-white',
          ])
        @endif
        @if(\App\Models\Setting::feature('share'))<a href="{{ route('documents.share', $document) }}" class="rounded-full px-5 py-2 border border-white/40 bg-white/10 backdrop-blur hover:bg-white/20 inline-flex items-center gap-2">@include('components.icon', ['name' => 'share', 'class' => 'w-4 h-4']) Bagikan</a>@endif
        @if(auth()->user()->isAdmin() || $document->user_id === auth()->id())
          @if($document->source_type === 'editor')
            <a href="{{ route('editor.edit', $document) }}" class="rounded-full px-5 py-2 border border-white/40 bg-white/10 backdrop-blur hover:bg-white/20 inline-flex items-center gap-2">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Ubah Isi</a>
          @else
            <a href="{{ route('documents.edit', $document) }}" class="rounded-full px-5 py-2 border border-white/40 bg-white/10 backdrop-blur hover:bg-white/20 inline-flex items-center gap-2">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Ubah</a>
          @endif
        @endif
      @endauth
    </div>
  </div>
</div>
@endif
@endsection

@section('content')
@if($document->isGovernance())
{{-- Layout mockup BAB 13 untuk dokumen governance --}}
@include('components.governance-detail', [
    'document' => $document,
    'impactMap' => $impactMap,
    'transitions' => $transitions ?? [],
    'isOverdue' => $isOverdue ?? false,
])

{{-- Aksi dokumen (ringkas, latar terang) --}}
<div class="flex flex-wrap gap-2 mt-4 text-[13px] font-bold">
  <a href="{{ route('reader.show', $document) }}" class="btn-ghost !py-2">📖 Baca Imersif</a>
  @if($document->file_path && \App\Models\Setting::feature('download'))
    @auth
      <a href="{{ route('documents.download', $document) }}" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'download', 'class' => 'w-4 h-4']) Unduh PDF</a>
      <a href="{{ route('documents.downloadHtml', $document) }}" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'file', 'class' => 'w-4 h-4']) Unduh HTML</a>
    @else
      <a href="{{ route('login') }}" class="btn-ghost !py-2">Masuk untuk mengunduh</a>
    @endauth
  @endif
  @if(\App\Models\Setting::feature('like'))
    @auth
      <form method="post" action="{{ route('documents.like', $document) }}" class="inline">@csrf<button class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'heart', 'class' => 'w-4 h-4']) {{ number_format($likesCount) }} Suka</button></form>
    @endauth
  @endif
  @if($canEmbed && \App\Models\Setting::feature('embed'))
    <button type="button" onclick="openEmbedModal(event)" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'code', 'class' => 'w-4 h-4']) Sematkan</button>
  @endif
  @auth
    @if(\App\Models\Setting::feature('bookmark'))
      @include('components.bookmark-button', [
        'document' => $document,
        'marked' => $isMarked,
        'labelOn' => 'Tersimpan di Bookmark',
        'labelOff' => 'Simpan ke Bookmark',
        'btnClass' => 'btn-ghost !py-2 inline-flex items-center gap-2 cursor-pointer',
        'iconClass' => 'w-4 h-4',
        'activeClass' => '!border-amber-400 !text-amber-600',
        'inactiveClass' => '',
      ])
    @endif
    @if(\App\Models\Setting::feature('share'))<a href="{{ route('documents.share', $document) }}" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'share', 'class' => 'w-4 h-4']) Bagikan</a>@endif
    @if(auth()->user()->isAdmin() || $document->user_id === auth()->id())
      @if($document->source_type === 'editor')
        <a href="{{ route('editor.edit', $document) }}" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Ubah Isi</a>
      @else
        <a href="{{ route('documents.edit', $document) }}" class="btn-ghost !py-2 inline-flex items-center gap-2">@include('components.icon', ['name' => 'pen', 'class' => 'w-4 h-4']) Ubah</a>
      @endif
    @endif
  @endauth
</div>
@else
@include('components.back-bar', ['trail' => array_filter([['Beranda', route('home')], $document->category ? [$document->category->name, route('documents.index', ['kategori' => $document->category->slug])] : null, [$document->title]])])

{{-- Phase 3 — Document Passport + Lifecycle + Connected Knowledge --}}
<div class="grid lg:grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
    <h2 class="font-extrabold tracking-tight text-[#002f80]">DOCUMENT PASSPORT</h2>
    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-[13px]">
      <div><dt class="text-[11px] font-bold text-slate-400">DOC ID</dt><dd class="font-bold">{{ $document->doc_code ?? '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">TYPE</dt><dd class="font-bold">{{ $document->doc_type ? strtoupper(str_replace('_', ' ', $document->doc_type)) : '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">OWNER</dt><dd class="font-bold">{{ $document->user->name }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">APPROVER</dt><dd class="font-bold">{{ $document->approver->name ?? '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">EFFECTIVE</dt><dd class="font-bold">{{ $document->effective_at?->format('d M Y') ?? '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">REVIEW</dt><dd class="font-bold">{{ $document->review_at?->format('d M Y') ?? '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">CLASS</dt><dd class="font-bold">{{ $document->classification ? strtoupper($document->classification) : '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">STATUS</dt><dd class="font-bold">{{ strtoupper(str_replace('_', ' ', $document->status)) }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">PIC</dt><dd class="font-bold">{{ $document->pic->name ?? $document->pic_name ?? '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold text-slate-400">UNIT</dt><dd class="font-bold">{{ $document->unit ?? '—' }}</dd></div>
      @if(!empty($document->audience))
        <div class="col-span-2"><dt class="text-[11px] font-bold text-slate-400">AUDIENCE</dt><dd class="flex flex-wrap gap-1 mt-1">@foreach($document->audience as $a)<span class="text-[11px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-0.5 rounded-full">{{ strtoupper(str_replace('_', ' ', $a)) }}</span>@endforeach</dd></div>
      @endif
    </dl>
    @if(!empty($transitions))
      <div class="flex flex-wrap gap-2 mt-4">
        @foreach($transitions as $next)
          <form method="post" action="{{ route('documents.transition', $document) }}" class="inline">@csrf
            <input type="hidden" name="to" value="{{ $next }}">
            <button class="text-xs font-bold rounded-full px-4 py-2 {{ $next === 'active' ? 'bg-[#002f80] text-white' : ($next === 'archived' ? 'bg-[#c90018] text-white' : 'border border-[#002f80]/30 text-[#002f80] hover:bg-[#002f80] hover:text-white') }} transition">→ {{ strtoupper(str_replace('_', ' ', $next)) }}</button>
          </form>
        @endforeach
      </div>
      <p class="text-[11px] text-slate-400 mt-2">Alur: DRAFT → REVIEW → APPROVAL → ACTIVE → REVIEW DUE → ARCHIVED</p>
    @endif
  </div>

  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
    <h2 class="font-extrabold tracking-tight text-[#002f80]">RELATED KNOWLEDGE</h2>
    <div class="mt-2 space-y-1.5">
      @forelse($document->outgoingRelations as $rel)
        @if($rel->system)
          <div class="flex items-center gap-2 text-sm">
            <span class="text-[10px] font-bold bg-green-700/10 text-green-700 px-2 py-1 rounded-full shrink-0">SYSTEM</span>
            <a href="{{ route('systems.show', $rel->system) }}" class="font-bold truncate hover:text-[#002f80] flex-1">{{ $rel->system->name }}</a>
            @can('relate', $document)
              <form method="post" action="{{ route('documents.relations.destroy', [$document, $rel]) }}" class="inline">@csrf @method('DELETE')<button class="text-[#c90018] text-xs font-bold" aria-label="Hapus relasi">✕</button></form>
            @endcan
          </div>
        @elseif($rel->related)
          <div class="flex items-center gap-2 text-sm">
            <span class="text-[10px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-1 rounded-full shrink-0">{{ strtoupper($rel->relation_type) }}</span>
            <a href="{{ route('documents.show', $rel->related) }}" class="font-bold truncate hover:text-[#002f80] flex-1">{{ $rel->related->title }}</a>
            @can('relate', $document)
              <form method="post" action="{{ route('documents.relations.destroy', [$document, $rel]) }}" class="inline">@csrf @method('DELETE')<button class="text-[#c90018] text-xs font-bold" aria-label="Hapus relasi">✕</button></form>
            @endcan
          </div>
        @endif
      @empty
        <p class="text-[13px] text-slate-400">Belum ada relasi eksplisit. Tambahkan Handbook → Policy → SOP → Form di bawah.</p>
      @endforelse
    </div>
    @can('relate', $document)
      <form method="post" action="{{ route('documents.relations.store', $document) }}" class="flex gap-2 mt-3">@csrf
        <input name="related_slug" placeholder="slug-dokumen-terkait" class="field !text-xs flex-1" aria-label="Slug dokumen terkait">
        <select name="relation_type" class="field !text-xs !w-auto" aria-label="Tipe relasi">@foreach(\App\Models\DocumentRelation::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select>
        <button class="btn-primary !py-2 !text-xs shrink-0">Tambah</button>
      </form>
      <form method="post" action="{{ route('documents.relations.store', $document) }}" class="flex gap-2 mt-2">@csrf
        <select name="system_slug" class="field !text-xs flex-1" aria-label="Sistem terkait"><option value="">— Pilih sistem —</option>@foreach(\App\Models\System::orderBy('name')->get() as $s)<option value="{{ $s->slug }}">{{ $s->name }}</option>@endforeach</select>
        <button class="btn-ghost !py-2 !text-xs shrink-0">Tautkan Sistem</button>
      </form>
    @endcan
    @if($document->versions->isNotEmpty())
      <h3 class="text-xs font-extrabold tracking-widest text-slate-400 mt-4 mb-1">VIEW HISTORY</h3>
      <ol class="text-xs space-y-1 max-h-36 overflow-y-auto">
        @foreach($document->versions as $v)
          <li class="flex gap-2"><span class="text-slate-400 shrink-0">{{ $v->created_at->format('d M Y') }}</span><span><strong>v{{ $v->version }}</strong> {{ $v->from_status }} → <strong>{{ $v->to_status }}</strong> · {{ $v->user->name ?? 'sistem' }}{{ $v->change_note ? ' · '.$v->change_note : '' }}</span></li>
        @endforeach
      </ol>
    @endif
  </div>
</div>
@endif

<div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start mt-6">
  {{-- Kiri: pratinjau dokumen --}}
  <div class="min-w-0">
    {{-- Fase 1: penampil + atur tampilan + lanjut baca --}}
    <div id="reader" data-theme="{{ $pref->theme }}" class="rounded-3xl shadow-xl overflow-hidden border {{ $pref->theme === 'dark' ? 'bg-[#141920] border-white/10' : 'bg-white border-[#d6d6d6]' }}">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 border-b text-sm sticky top-0 z-10 {{ $pref->theme === 'dark' ? 'bg-[#1c2431] text-stone-100 border-white/10' : 'bg-slate-50/90 backdrop-blur text-slate-700' }}">
        <div class="flex items-center gap-1.5">
          <button id="btn-prev" aria-label="Halaman sebelumnya" class="w-8 h-8 rounded-full border border-current/20 grid place-items-center hover:bg-[#002f80] hover:text-white hover:border-[#002f80] transition">@include('components.icon', ['name' => 'chev-left', 'class' => 'w-4 h-4'])</button>
          <span class="text-xs font-bold tabular-nums px-1"><span id="pg-now">1</span> <span class="opacity-50">/</span> <span id="pg-total">…</span></span>
          <button id="btn-next" aria-label="Halaman berikutnya" class="w-8 h-8 rounded-full border border-current/20 grid place-items-center hover:bg-[#002f80] hover:text-white hover:border-[#002f80] transition">@include('components.icon', ['name' => 'chev-right', 'class' => 'w-4 h-4'])</button>
        </div>
        <label class="text-xs font-semibold flex items-center gap-1.5">Zoom
          <select id="sel-zoom" class="rounded-full text-xs font-bold px-2 py-1 bg-transparent border border-current/20 outline-none cursor-pointer">
            @foreach([50,75,100,125,150,200] as $z)<option value="{{ $z }}" {{ (int)$pref->zoom === $z ? 'selected' : '' }}>{{ $z }}%</option>@endforeach
          </select>
        </label>
        <label class="text-xs font-semibold flex items-center gap-1.5">Tampilan
          <select id="sel-layout" class="rounded-full text-xs font-bold px-2 py-1 bg-transparent border border-current/20 outline-none cursor-pointer">
            <option value="single" {{ $pref->layout === 'single' ? 'selected' : '' }}>Single page</option>
            <option value="double" {{ $pref->layout === 'double' ? 'selected' : '' }}>Double page</option>
          </select>
        </label>
        <button id="btn-theme" class="text-xs font-bold rounded-full px-3 py-1.5 border border-current/20 hover:bg-[#002f80] hover:text-white hover:border-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => $pref->theme === 'dark' ? 'sun' : 'moon', 'class' => 'w-3.5 h-3.5']) {{ $pref->theme === 'dark' ? 'Mode Terang' : 'Mode Gelap' }}</button>
        <button id="btn-full" class="text-xs font-bold rounded-full px-3 py-1.5 border border-current/20 hover:bg-[#002f80] hover:text-white hover:border-[#002f80] transition inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'maximize', 'class' => 'w-3.5 h-3.5']) Layar Penuh</button>
        @auth @if($position)<span id="resume-hint" class="ml-auto text-[11px] font-semibold px-3 py-1.5 rounded-full bg-[#002f80]/10 text-[#002f80] inline-flex items-center gap-1">@include('components.icon', ['name' => 'bookmark', 'class' => 'w-3 h-3']) Terakhir: hal. {{ $position->page }}</span>@endif @endauth
      </div>
      <div id="pages" class="flex flex-wrap justify-center items-start gap-5 p-5 max-h-[78vh] overflow-auto {{ $pref->theme === 'dark' ? 'bg-[#141920]' : 'bg-[#dde2e8]' }}"></div>
    </div>
    <p class="text-xs text-slate-400 mt-2">Tips: gunakan tombol panah pada keyboard untuk pindah halaman. Posisi bacamu tersimpan otomatis.</p>
    @if($safeContent)
      <details class="mt-4 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 text-sm"><summary class="cursor-pointer font-bold text-[#002f80]">Lihat teks sumber (editor)</summary><div class="prose max-w-none mt-2 text-slate-700">{!! $safeContent !!}</div></details>
    @endif
  </div>

  {{-- Kanan: Rekomendasi pintar --}}
  <aside class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-4">
    <div class="flex items-center gap-2 text-[#002f80] font-extrabold">
      @include('components.icon', ['name' => 'tag', 'class' => 'w-4.5 h-4.5'])
      <h2 class="text-sm">REKOMENDASI UNTUK ANDA</h2>
    </div>
    @if($recommendations->isNotEmpty())
      <div class="mt-2 divide-y divide-slate-100">
        @forelse($recommendations as $rec)
          <a href="{{ route('documents.show', $rec['document']) }}" class="flex items-start gap-3 py-2.5 group">
            <span class="w-9 h-9 rounded-xl grid place-items-center text-white text-sm font-extrabold shrink-0" style="background:{{ ['linear-gradient(135deg,#001c55,#002f80)','linear-gradient(135deg,#002f80,#3b6fd4)','linear-gradient(135deg,#7a0010,#c90018)','linear-gradient(135deg,#334155,#64748b)'][$loop->iteration % 4] }}">{{ $loop->iteration }}</span>
            <span class="min-w-0">
              <span class="block font-bold text-slate-800 text-sm leading-snug line-clamp-2 group-hover:text-[#002f80]">{{ $rec['document']->title }}</span>
              <span class="block text-[11px] text-slate-400 mt-0.5">
                @if($rec['reasons'])
                  <span class="text-[10px] font-bold bg-[#002f80]/5 text-[#002f80] px-2 py-0.5 rounded-full">{{ $rec['reasons'][0] }}</span>
                @endif
                <span class="ml-2 inline-flex items-center gap-1">@include('components.icon', ['name' => 'eye', 'class' => 'w-3 h-3']) {{ number_format($rec['document']->views_count) }} kali dibaca</span>
              </span>
            </span>
          </a>
        @empty
          <p class="text-[13px] text-slate-400">Belum ada rekomendasi untuk dokumen ini.</p>
        @endforelse
      </div>
    @else
      <p class="text-[13px] text-slate-400 mt-1">Belum ada rekomendasi untuk dokumen ini.</p>
    @endif
  </aside>
</div>

{{-- Bawah: dokumen populer (paling sering dibaca) --}}
@if($popular->isNotEmpty())
<section class="mt-10">
  <div class="flex items-center gap-2 mb-4">
    <span class="w-10 h-10 rounded-2xl grid place-items-center text-white" style="background:linear-gradient(135deg,#7a0010,#c90018)">@include('components.icon', ['name' => 'flame', 'class' => 'w-5 h-5'])</span>
    <div>
      <h2 class="text-xl font-extrabold tracking-tight text-[#002f80]">Dokumen Populer</h2>
      <p class="text-xs text-slate-500">Dokumen yang paling sering dibaca komunitas.</p>
    </div>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($popular as $p)
      @include('components.doc-card', ['d' => $p])
    @endforeach
  </div>
</section>
@endif

<style>#reader:fullscreen #pages { max-height: 100vh; }</style>

@if($canEmbed && \App\Models\Setting::feature('embed'))
{{-- Modal kode semat --}}
<div id="embed-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-[#000f2e]/60 backdrop-blur-sm" onclick="closeEmbedModal()"></div>
  <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg">
    <div class="flex items-center justify-between p-5 pb-3 border-b border-slate-100">
      <h2 class="font-extrabold text-[#002f80]">Sematkan Dokumen</h2>
      <button type="button" onclick="closeEmbedModal()" aria-label="Tutup" class="w-8 h-8 rounded-full grid place-items-center text-slate-400 hover:bg-slate-100 hover:text-[#c90018]">@include('components.icon', ['name' => 'x', 'class' => 'w-4 h-4'])</button>
    </div>
    <div class="p-5 space-y-3">
      <p class="text-xs text-slate-500">Tempel kode berikut ke situs/blog. Pratinjau mengikuti visibilitas dokumen ({{ $document->visibility }}).</p>
      <textarea id="embed-code" readonly rows="4" class="field font-mono !text-xs">{{ $embedCode }}</textarea>
      <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('embed-code').value);this.textContent='Disalin!'" class="btn-primary w-full !py-2.5">Salin Kode</button>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
const PDF_URL = @json($document->fileUrl());
const START_PAGE = @json(auth()->check() && $position ? (int)$position->page : 1);
const IS_AUTH = @json(auth()->check());
const SAVE_POS_URL = @json(auth()->check() ? route('api.position', $document) : null);
const SAVE_PREF_URL = @json(auth()->check() ? route('api.prefs') : null);
const CSRF = @json(csrf_token());
let pdf = null, page = START_PAGE, zoom = @json((int)$pref->zoom), layout = @json($pref->layout), theme = @json($pref->theme);
let saveTimer = null;

async function render() {
  if (!PDF_URL) { document.getElementById('pages').innerHTML = '<p class="text-sm p-4">Dokumen ini tidak memiliki file PDF.</p>'; return; }
  pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise;
  document.getElementById('pg-total').textContent = pdf.numPages;
  page = Math.min(Math.max(1, page), pdf.numPages);
  document.getElementById('pg-now').textContent = page;
  const box = document.getElementById('pages'); box.innerHTML = '';
  const pages = layout === 'double' && page < pdf.numPages ? [page, page + 1] : [page];
  for (const n of pages) {
    const p = await pdf.getPage(n);
    const vp = p.getViewport({ scale: (zoom / 100) * 1.35 });
    const cv = document.createElement('canvas');
    cv.className = 'rounded-lg shadow-2xl max-w-full h-auto';
    if (theme === 'dark') cv.style.filter = 'invert(0.92) hue-rotate(180deg)';
    cv.height = vp.height; cv.width = vp.width;
    box.appendChild(cv);
    await p.render({ canvasContext: cv.getContext('2d'), viewport: vp }).promise;
  }
  const hint = document.getElementById('resume-hint');
  if (hint) hint.lastChild.textContent = ' Terakhir: hal. ' + page;
  if (IS_AUTH) { clearTimeout(saveTimer); saveTimer = setTimeout(savePosition, 1200); }
}
function savePosition() {
  fetch(SAVE_POS_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ page, total_pages: pdf ? pdf.numPages : null, percent: pdf ? Math.round(page / pdf.numPages * 100) : 0 }) });
}
function savePrefs() {
  if (!IS_AUTH) return;
  fetch(SAVE_PREF_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ theme, zoom, layout }) });
}
document.getElementById('btn-prev').onclick = () => { page -= layout === 'double' ? 2 : 1; render(); };
document.getElementById('btn-next').onclick = async () => { pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise; page = Math.min(page + (layout === 'double' ? 2 : 1), pdf.numPages); render(); };
document.getElementById('sel-zoom').onchange = e => { zoom = +e.target.value; savePrefs(); render(); };
document.getElementById('sel-layout').onchange = e => { layout = e.target.value; savePrefs(); render(); };
document.getElementById('btn-theme').onclick = () => { theme = theme === 'dark' ? 'light' : 'dark'; savePrefs(); setTimeout(() => location.reload(), 350); };
document.getElementById('btn-full').onclick = () => {
  const r = document.getElementById('reader');
  if (document.fullscreenElement) document.exitFullscreen();
  else if (r.requestFullscreen) r.requestFullscreen();
};
document.addEventListener('keydown', e => {
  if (/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) return;
  if (e.key === 'ArrowRight') document.getElementById('btn-next').click();
  if (e.key === 'ArrowLeft') document.getElementById('btn-prev').click();
});
render();
@if($canEmbed && \App\Models\Setting::feature('embed'))
function openEmbedModal(e) { if (e) e.preventDefault(); document.getElementById('embed-modal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeEmbedModal() { document.getElementById('embed-modal').classList.add('hidden'); document.body.style.overflow = ''; }
@endif
</script>
@endpush
@endsection