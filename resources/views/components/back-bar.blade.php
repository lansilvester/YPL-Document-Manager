{{-- Bar navigasi kembali + breadcrumb hierarki.
  Pakai: @include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Judul Saat Ini']]]) --}}
@php
$links = array_values(array_filter($trail ?? [], fn ($t) => ! empty($t[1])));
$fallback = $fallback ?? ($links ? end($links)[1] : route('home'));
@endphp
<div class="flex items-center gap-3 mb-5">
  <button type="button" data-fallback="{{ $fallback }}" onclick="if (history.length > 1) { history.back(); } else { location.href = this.dataset.fallback; }" class="shrink-0 inline-flex items-center gap-1.5 text-[13px] font-bold border border-[#d6d6d6] bg-white rounded-full px-4 py-1.5 hover:border-[#002f80] hover:text-[#002f80] transition">@include('components.icon', ['name' => 'chev-left', 'class' => 'w-4 h-4']) Kembali</button>
  <nav class="flex items-center gap-1.5 text-[13px] text-slate-400 min-w-0 overflow-x-auto whitespace-nowrap" aria-label="Breadcrumb">
    @foreach($trail ?? [] as $i => $t)
      @if($i > 0)<span class="text-slate-300">/</span>@endif
      @if(! empty($t[1]) && !$loop->last)
        <a href="{{ $t[1] }}" class="hover:text-[#002f80] hover:underline shrink-0">{{ $t[0] }}</a>
      @else
        <span class="{{ $loop->last ? 'font-bold text-[#002f80] truncate' : '' }}">{{ $t[0] }}</span>
      @endif
    @endforeach
  </nav>
</div>
