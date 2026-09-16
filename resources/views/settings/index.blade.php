@extends('layouts.app')

@section('title', __('settings.tabs.server'))
@section('subtitle', __('settings.common.system'))

@section('content')
    @livewire(App\Livewire\SettingsPage::class)
@endsection
