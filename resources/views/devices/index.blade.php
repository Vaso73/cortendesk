@extends('layouts.app')

@section('title', __('devices.pages.devices_title'))
@section('subtitle', __('devices.common.manage'))

@section('content')
    @livewire(App\Livewire\DeviceList::class)
@endsection
