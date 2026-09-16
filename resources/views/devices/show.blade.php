@extends('layouts.app')

@section('title', __('devices.pages.device_title'))
@section('subtitle', __('devices.common.manage'))

@section('content')
    <div class="mb-3">
        <a href="{{ route('devices') }}" class="fs-13 text-muted"><i class="ri-arrow-left-line me-1"></i>{{ __('devices.pages.back_to_devices') }}</a>
    </div>
    @livewire(App\Livewire\DeviceDetail::class, ['deviceId' => $device], key('device-detail-'.$device))
@endsection
