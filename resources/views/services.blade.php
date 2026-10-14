@extends('layouts.app')

@php
    $seoKey = 'services';
@endphp

@section('title', __('seo.services.title'))
@section('meta_description', __('seo.services.description'))
@section('meta_keywords', __('seo.services.keywords'))

@section('content')
<div
    @if ($packages->isNotEmpty())
        x-data="bookingForm(@js($packagesSlots))"
        @keydown.escape.window="if (modalOpen) closeModal()"
    @endif
>
    @include('partials.packages-section', ['compactTop' => true])
    @include('partials.book-session')
</div>
@endsection
