{{--
  Blok "Terkait dengan topik ini" (B2) — ringkasan relasi + tautan ke Peta Pengetahuan.
  Variabel: $document, $impactMap (dari App\Services\ImpactMap::for).
--}}
@php
    $refs = $impactMap['references'] ?? collect();
    $backs = $impactMap['referenced_by'] ?? collect();
    $total = $refs->count() + $backs->count();
@endphp
<section class="mt-6 bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-[var(--ypl-navy)]">
            @include('components.icon', ['name' => 'link', 'class' => 'w-4.5 h-4.5'])
            <h2 class="text-sm font-extrabold tracking-widest">TERKAIT DENGAN TOPIK INI</h2>
        </div>
        <a href="{{ route('library.graph', ['dokumen' => $document->slug]) }}" class="text-xs font-bold text-[var(--ypl-red)] hover:underline inline-flex items-center gap-1">
            Buka di Peta Pengetahuan @include('components.icon', ['name' => 'chev-right', 'class' => 'w-3 h-3'])
        </a>
    </div>

    @if($total === 0)
        <p class="text-[13px] text-slate-400 mt-3">Belum ada topik terkait. Relasi dapat ditambahkan dari detail dokumen.</p>
    @else
        <div class="flex flex-wrap gap-2 mt-3">
            @foreach($refs as $ref)
                <a href="{{ $ref['route'] }}" class="inline-flex items-center gap-1.5 text-xs font-bold bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)] hover:bg-[var(--ypl-navy)] hover:text-white transition rounded-full px-3 py-1.5">
                    @include('components.icon', ['name' => $ref['target_type'] === 'system' ? 'tool' : 'file', 'class' => 'w-3 h-3'])
                    <span class="max-w-[16rem] truncate">{{ $ref['title'] }}</span>
                </a>
            @endforeach
            @foreach($backs as $back)
                <a href="{{ route('documents.show', $back['slug']) }}" class="inline-flex items-center gap-1.5 text-xs font-bold bg-[var(--ypl-red)]/5 text-[var(--ypl-red)] hover:bg-[var(--ypl-red)] hover:text-white transition rounded-full px-3 py-1.5" title="Dirujuk oleh">
                    <span aria-hidden="true">↩</span>
                    <span class="max-w-[16rem] truncate">{{ $back['source_title'] }}</span>
                </a>
            @endforeach
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Warna navy = dirujuk dokumen ini; merah = dokumen lain yang merujuk balik.</p>
    @endif
</section>
