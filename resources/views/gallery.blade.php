@extends('layouts.app')

@php
    $seoKey = 'gallery';
    $servicesUrl = localized_url(current_locale(), 'services');
@endphp

@section('title', __('seo.gallery.title'))
@section('meta_description', __('seo.gallery.description'))
@section('meta_keywords', __('seo.gallery.keywords'))

@section('content')
    <section class="bg-sharm-sand">
        @include('partials.featured-polaroid-slider', ['featuredPhotos' => $featuredPhotos ?? collect()])

        <div class="container-site px-4 pt-10 pb-6 text-center sm:px-6 lg:px-8 lg:pt-14">
            <h2 class="section-title">{{ __('site.gallery.page_title') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-sharm-mist sm:text-base">
                {{ __('site.gallery.page_subtitle') }}
            </p>
        </div>

        @if ($albums->count() > 0)
            <div
                class="container-site reveal grid gap-5 px-4 pb-16 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8 lg:pb-20"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                @foreach ($albums as $index => $album)
                    <a
                        href="{{ localized_url(current_locale(), 'gallery/' . $album->slug) }}"
                        class="group relative overflow-hidden rounded-2xl bg-sharm-deep/5 shadow-lg shadow-sharm-deep/[0.04] transition duration-300 hover:-translate-y-1 hover:shadow-xl"
                        style="animation-delay: {{ $index * 80 }}ms"
                    >
                        <div class="aspect-[4/3] overflow-hidden">
                            @if ($album->cover_url)
                                <img
                                    src="{{ $album->cover_url }}"
                                    alt="{{ $album->name }}"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full items-center justify-center bg-sharm-sand-dark">
                                    <svg class="h-16 w-16 text-sharm-mist/20" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="absolute inset-0 flex items-end bg-gradient-to-t from-sharm-deep/60 via-sharm-deep/10 to-transparent p-5">
                            <div>
                                <h2 class="font-display text-xl font-medium text-white sm:text-2xl">{{ $album->name }}</h2>
                                <p class="mt-1 text-xs text-white/70">{{ $album->photos_count }} {{ __('site.gallery.photos_count') }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="container-site px-4 pb-16 text-center sm:px-6 lg:px-8">
                <p class="text-sm text-sharm-mist">{{ __('site.gallery.no_albums') }}</p>
            </div>
        @endif

        <div class="container-site px-4 pb-16 text-center sm:px-6 lg:px-8">
            <a href="{{ $servicesUrl }}#book-session" class="btn-primary">{{ __('site.nav.book') }}</a>
        </div>
    </section>
@endsection
