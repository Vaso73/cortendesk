@extends('layouts.app')

@section('title', __('settings.setup.title'))

@section('content')
    @livewire(App\Livewire\SetupWizard::class)
@endsection