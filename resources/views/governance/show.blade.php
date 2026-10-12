@extends('layout')
@section('title', $document->title)
@section('content')

@include('components.governance-detail', [
    'document' => $document,
    'impactMap' => $impactMap,
    'transitions' => $transitions ?? [],
    'isOverdue' => $isOverdue ?? false,
])

{{-- B2: blok terkait + tautan peta pengetahuan --}}
@include('components.related-topics', ['document' => $document, 'impactMap' => $impactMap])
@endsection
