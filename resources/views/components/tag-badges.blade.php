@php
$palettes = [
  'navy' => 'background:#dbe4f7;color:#002f80;border-color:#b9c9ec',
  'red' => 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0',
  'steel' => 'background:#e2eaf6;color:#33507e;border-color:#c3d2ea',
  'gold' => 'background:#f7ecd2;color:#8a6100;border-color:#e8d3a0',
  'slate' => 'background:#e8ebef;color:#475569;border-color:#cbd2da',
  'silver' => 'background:#eef0f2;color:#5b6470;border-color:#d6d6d6',
  'charcoal' => 'background:#e2e6ec;color:#263245;border-color:#c2cad6',
  // nilai lama (kompatibilitas)
  'emerald' => 'background:#dbe4f7;color:#002f80;border-color:#b9c9ec',
  'sky' => 'background:#e2eaf6;color:#33507e;border-color:#c3d2ea',
  'violet' => 'background:#e2e6ec;color:#263245;border-color:#c2cad6',
  'amber' => 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0',
  'rose' => 'background:#fbe0e4;color:#a30014;border-color:#f3b7c0',
];
@endphp
@foreach($tags as $t)
  <span class="inline-flex items-center gap-1 text-[11px] font-bold border rounded-full px-2.5 py-0.5" style="{{ $palettes[$t->color] ?? $palettes['silver'] }}">@include('components.icon', ['name' => 'tag', 'class' => 'w-3 h-3']) {{ $t->name }}</span>
@endforeach
