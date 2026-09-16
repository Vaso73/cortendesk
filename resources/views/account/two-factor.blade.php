@extends('layouts.app')

@section('title', __('auth.two_factor.title'))
@section('subtitle', __('auth.common.account'))

@section('content')
    @if (session('twofactor_enforced'))
        <div class="alert alert-warning">
            <i class="ri-shield-keyhole-line me-1"></i>{{ __('auth.two_factor.enforced') }}
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            @livewire(App\Livewire\TwoFactorSettings::class)
        </div>
    </div>
@endsection
