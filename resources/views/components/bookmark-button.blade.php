{{-- Tombol bookmark interaktif (AJAX toggle, fallback submit biasa).
  Props: document, marked(bool), btnClass, activeClass, inactiveClass, labelOn, labelOff, showLabel, iconClass --}}
@php
  $marked = (bool) ($marked ?? false);
  $baseBtnClass = $btnClass ?? 'rounded-full px-4 py-2 border text-sm font-semibold inline-flex items-center gap-2 transition-all duration-200 cursor-pointer';
  $activeClass = $activeClass ?? 'bg-amber-400/20 border-amber-300 text-amber-200 shadow-sm';
  $inactiveClass = $inactiveClass ?? 'border-white/40 bg-white/10 hover:bg-white/20 text-white';
  $iconClass = $iconClass ?? 'w-4 h-4';
  $labelOn = $labelOn ?? 'Tersimpan';
  $labelOff = $labelOff ?? 'Bookmark';
  $showLabel = $showLabel ?? true;
  $initialClass = $baseBtnClass . ' ' . ($marked ? $activeClass : $inactiveClass);
@endphp
<form method="post" action="{{ route('documents.bookmark', $document) }}" data-bookmark-form data-doc="{{ $document->id }}" class="inline-flex items-center m-0 p-0">
  @csrf
  <button type="submit"
    data-bookmark-btn
    data-marked="{{ $marked ? '1' : '0' }}"
    data-active-class="{{ $activeClass }}"
    data-inactive-class="{{ $inactiveClass }}"
    data-label-on="{{ $labelOn }}"
    data-label-off="{{ $labelOff }}"
    aria-pressed="{{ $marked ? 'true' : 'false' }}"
    aria-label="{{ $marked ? $labelOn : $labelOff }}"
    class="{{ $initialClass }}">
    <span data-icon-marked class="{{ $marked ? 'inline-flex' : 'hidden' }} items-center shrink-0">
      @include('components.icon', ['name' => 'bookmark-filled', 'class' => $iconClass])
    </span>
    <span data-icon-unmarked class="{{ $marked ? 'hidden' : 'inline-flex' }} items-center shrink-0">
      @include('components.icon', ['name' => 'bookmark', 'class' => $iconClass])
    </span>
    @if($showLabel)
      <span data-label class="leading-none">{{ $marked ? $labelOn : $labelOff }}</span>
    @endif
  </button>
</form>
