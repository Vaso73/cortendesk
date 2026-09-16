@extends('layouts.app')

@section('title', __('identity.roles.title'))
@section('subtitle', __('identity.roles.subtitle'))

@section('content')
    @livewire(App\Livewire\RoleList::class)
@endsection
