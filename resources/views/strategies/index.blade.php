@extends('layouts.app')

@section('title', __('settings.strategies.title'))
@section('subtitle', __('settings.common.manage'))

@section('content')
    @livewire(App\Livewire\StrategyList::class)
@endsection
