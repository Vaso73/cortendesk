@extends('layouts.app')

@section('title', __('audit.pages.file_transfers.title'))
@section('subtitle', __('audit.section'))

@section('content')
    @livewire(App\Livewire\FileTransferLog::class)
@endsection
