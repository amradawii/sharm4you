@extends('layouts.app')

@php
    $seoKey = 'gallery_album';
    $homeUrl = home_url();
    $galleryUrl = localized_url(current_locale(), 'gallery');
    $servicesUrl = localized_url(current_locale(), 'services');
@endphp

@section('title', $album->name . ' — ' . __('seo.gallery_album.title'))
@section('meta_description', __('seo.gallery_album.description') . ' ' . $album->name)
@section('meta_keywords', __('seo.gallery_album.keywords'))

@if ($photos->isNotEmpty())
    @section('og_image', $photos->first()->url)
@endif

@section('content')
    <section
        class="section-padding !pt-6 sm:!pt-8 lg:!pt-10 bg-sharm-sand"
        x-data="{
            lightbox: false,
            current: 0,
            photos: {{ Js::from($photos->map(fn ($p) => ['src' => $p->url, 'alt' => $p->alt])) }},
            open(index) {
                this.current = index
                this.lightbox = true
                document.body.style.overflow = 'hidden'
            },
            close() {
                this.lightbox = false
                document.body.style.overflow = ''
            },
            prev() {
                this.current = (this.current - 1 + this.photos.length) % this.photos.length
            },
            next() {
                this.current = (this.current + 1) % this.photos.length
            }
        }"
        @keydown.escape.window="close()"
        @keydown.arrow-left.window="lightbox && prev()"
        @keydown.arrow-right.window="lightbox && next()"
    >
        <div class="container-site">
            <div
                class="reveal text-center"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <a href="{{ $galleryUrl }}" class="section-label transition hover:bg-sharm-sea/15">
                    &larr; {{ __('site.gallery.back_to_gallery') }}
                </a>
                <h1 class="section-title mt-4">{{ $album->name }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">
                    {{ $photos->count() }} {{ __('site.gallery.photos_count') }}
                </p>
            </div>

            @if ($photos->isNotEmpty())
                <div
                    class="gallery-masonry reveal mt-12"
                    x-intersect.once="$el.classList.add('is-visible')"
                >
                    @foreach ($photos as $index => $photo)
                        <figure class="gallery-masonry-item">
                            <button
                                type="button"
                                class="gallery-masonry-trigger group"
                                @click="open({{ $index }})"
                            >
                                <img
                                    src="{{ $photo->url }}"
                                    alt="{{ $photo->alt }}"
                                    class="gallery-masonry-photo transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                    decoding="async"
                                >
                                <div class="absolute inset-0 flex items-end bg-gradient-to-t from-sharm-deep/50 to-transparent p-4 opacity-0 transition duration-300 group-hover:opacity-100">
                                    <span class="text-left text-xs text-white/90">{{ $photo->alt }}</span>
                                </div>
                            </button>
                        </figure>
                    @endforeach
                </div>
            @else
                <div class="mt-16 text-center">
                    <p class="text-sm text-sharm-mist">{{ __('site.gallery.no_photos') }}</p>
                </div>
            @endif

            <div class="mt-12 text-center">
                <a href="{{ $servicesUrl }}#book-session" class="btn-primary">{{ __('site.nav.book') }}</a>
            </div>
        </div>

        {{-- Lightbox --}}
        <div
            x-show="lightbox"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/95 p-4"
            @click.self="close()"
        >
            <button
                type="button"
                class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center text-2xl text-white/80 transition hover:text-white"
                @click="close()"
                aria-label="Close"
            >&times;</button>

            <button
                type="button"
                class="absolute left-4 top-1/2 z-10 hidden -translate-y-1/2 text-3xl text-white/70 transition hover:text-white sm:block"
                @click="prev()"
                aria-label="Previous"
            >&lsaquo;</button>

            <button
                type="button"
                class="absolute right-4 top-1/2 z-10 hidden -translate-y-1/2 text-3xl text-white/70 transition hover:text-white sm:block"
                @click="next()"
                aria-label="Next"
            >&rsaquo;</button>

            <div
                class="relative max-h-[85vh] max-w-5xl"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <img
                    :src="photos[current]?.src"
                    :alt="photos[current]?.alt"
                    class="max-h-[85vh] w-auto max-w-full object-contain"
                >
            </div>
        </div>
    </section>
@endsection
