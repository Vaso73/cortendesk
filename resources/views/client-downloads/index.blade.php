@extends('layouts.app')

@section('title', __('settings.downloads.title'))
@section('subtitle', __('settings.common.system'))

@section('content')
    @livewire(App\Livewire\ClientDownloadManager::class)
@endsection
