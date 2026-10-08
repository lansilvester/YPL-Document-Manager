@extends('layout')
@section('title', $document->title)
@section('content')

@include('components.governance-detail', [
    'document' => $document,
    'impactMap' => $impactMap,
    'transitions' => $transitions ?? [],
    'isOverdue' => $isOverdue ?? false,
])
@endsection
