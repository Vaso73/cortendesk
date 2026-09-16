@extends('layouts.app')

@section('title', __('audit.pages.alarms.title'))
@section('subtitle', __('audit.section'))

@section('content')
    @livewire(App\Livewire\AlarmLogList::class)
@endsection
