@extends('layouts.app')

@section('title', __('devices.pages.groups_title'))
@section('subtitle', __('devices.common.manage'))

@section('content')
    @livewire(App\Livewire\GroupList::class)
@endsection
