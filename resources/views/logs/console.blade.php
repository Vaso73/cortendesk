@extends('layouts.app')

@section('title', __('audit.pages.console.title'))
@section('subtitle', __('audit.section'))

@section('content')
    @livewire(App\Livewire\ConsoleAuditList::class)
@endsection
