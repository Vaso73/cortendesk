@extends('layouts.app')

@section('title', __('audit.pages.logins.title'))
@section('subtitle', __('audit.section'))

@section('content')
    @livewire(App\Livewire\LoginLogList::class)
@endsection
