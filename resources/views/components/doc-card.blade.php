@php
$grads = [
  'linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy))',
  'linear-gradient(135deg,var(--ypl-navy),#3b6fd4)',
  'linear-gradient(135deg,var(--ypl-red-800),var(--ypl-red))',
  'linear-gradient(135deg,#334155,var(--ypl-muted))',
  'linear-gradient(135deg,var(--ypl-navy),#5b8bd6)',
  'linear-gradient(135deg,#4a1215,#8a1c28)',
];
$g = $grads[$d->id % count($grads)];
@endphp
<a href="{{ route('documents.show', $d) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] overflow-hidden flex flex-col shadow-sm">
  <div class="p-4 pb-3 text-white relative overflow-hidden" style="background:{{ $g }}">
    <span class="absolute -right-4 -bottom-5 opacity-15">@include('components.icon', ['name' => 'file', 'class' => 'w-28 h-28'])</span>
    <div class="relative flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider">
      <span class="bg-white/20 backdrop-blur px-2.5 py-1 rounded-full">{{ $d->category->name ?? 'Tanpa kategori' }}</span>
      <span class="bg-black/20 px-2.5 py-1 rounded-full">{{ $d->source_type === 'editor' ? 'Editor' : 'PDF' }}</span>
    </div>
    <div class="relative mt-6 text-4xl font-extrabold opacity-90 leading-none">{{ strtoupper(mb_substr($d->title, 0, 1)) }}</div>
  </div>
  <div class="p-4 flex flex-col flex-1">
    <h2 class="font-bold text-slate-900 leading-snug line-clamp-2">{{ $d->title }}</h2>
    @if($d->tags->isNotEmpty())
      <div class="flex flex-wrap gap-1 mt-2">@include('components.tag-badges', ['tags' => $d->tags->take(3)])</div>
    @endif
    <p class="text-[13px] text-slate-500 line-clamp-2 mt-1 flex-1">{{ $d->description }}</p>
    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
      <span class="w-6 h-6 rounded-full grid place-items-center text-white text-[10px] font-bold" style="background:{{ $g }}">{{ strtoupper(substr($d->user->name, 0, 1)) }}</span>
      <span class="truncate font-medium text-slate-600">{{ $d->user->name }}</span>
      <span class="ml-auto shrink-0 inline-flex items-center gap-1">@include('components.icon', ['name' => 'eye', 'class' => 'w-3.5 h-3.5']) {{ number_format($d->views_count) }}</span>
    </div>
  </div>
</a>
