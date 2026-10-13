@extends('layout')
@section('title', \Illuminate\Support\Str::limit($faq->question, 60))
@section('content')
<p class="page-eyebrow text-[var(--ypl-red)] mt-2">FAQ</p>
<h1 class="page-title mt-1">{{ $faq->question }}</h1>
<div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-6 mt-4 prose font-reading max-w-none text-slate-700">
  {!! nl2br(e($faq->answer)) !!}
</div>
<h2 class="text-sm font-extrabold tracking-widest text-slate-500 mt-6 mb-2">DOKUMEN TERKAIT</h2>
<div class="grid sm:grid-cols-2 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-sm text-slate-400 sm:col-span-2 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada dokumen tertaut ke FAQ ini.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
