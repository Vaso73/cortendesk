@extends('layouts.app')

@section('title', __('address_books.page.title'))
@section('subtitle', __('address_books.page.subtitle'))

@section('content')
    @livewire(App\Livewire\AddressBookManager::class)
@endsection
