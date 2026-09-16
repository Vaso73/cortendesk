@extends('layouts.app')

@section('title', __('audit.pages.connections.title'))
@section('subtitle', __('audit.section'))

@section('content')
    @livewire(App\Livewire\ConnectionLog::class)
@endsection
