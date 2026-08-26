@extends('layouts.app')

@section('title', __('seo.my_photos.title'))
@section('meta_description', __('seo.my_photos.description'))

@section('content')
@include('partials.page-banner', [
    'label' => __('site.my_photos.label'),
    'title' => __('site.my_photos.title'),
    'subtitle' => __('site.my_photos.subtitle'),
])

<section class="section-padding bg-sharm-sand">
    <div class="container-site px-4 sm:px-6 lg:px-8">
        @if ($submitted && $booking)
            {{-- Delivered photos --}}
            <div class="mx-auto max-w-5xl">
                <div class="rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-soft sm:p-8">
                    <div class="flex flex-col gap-1 border-b border-sharm-deep/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="section-label">{{ __('site.my_photos.ready_label') }}</p>
                            <h2 class="mt-2 font-display text-2xl text-sharm-deep sm:text-3xl">{{ __('site.my_photos.ready_title', ['name' => $booking->name]) }}</h2>
                            <p class="mt-1 text-sm text-sharm-mist">
                                {{ __('site.my_photos.booking') }}: <span class="font-mono font-semibold text-sharm-deep">{{ $booking->booking_number }}</span>
                                @if ($booking->package)
                                    · {{ $booking->package->name }}
                                @endif
                                @if ($booking->session_date)
                                    · {{ $booking->session_date->translatedFormat('M j, Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="mt-3 shrink-0 rounded-full bg-sharm-sea/10 px-3 py-1.5 text-xs font-medium text-sharm-sea sm:mt-0">
                            {{ trans_choice('site.my_photos.photo_count', $photos->count(), ['count' => $photos->count()]) }}
                        </span>
                    </div>

                    <p class="mt-4 text-xs text-sharm-mist">{{ __('site.my_photos.download_hint') }}</p>

                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        @foreach ($photos as $photo)
                            <a
                                href="{{ $photo->download_url }}"
                                download="{{ $photo->original_name }}"
                                class="group relative block aspect-square overflow-hidden rounded-xl border border-sharm-deep/10 bg-sharm-sand"
                            >
                                <img
                                    src="{{ $photo->download_url }}"
                                    alt="{{ $booking->name }} — {{ config('app.name') }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <span class="absolute inset-0 flex items-center justify-center bg-sharm-deep/0 transition group-hover:bg-sharm-deep/40">
                                    <span class="flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-sharm-deep opacity-0 shadow-sm transition group-hover:opacity-100">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        {{ __('site.my_photos.download') }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-xl bg-sharm-sand/70 p-4 text-center text-xs text-sharm-mist">
                        {{ __('site.my_photos.thanks') }}
                    </div>
                </div>
            </div>
        @else
            {{-- Lookup form --}}
            <div class="mx-auto max-w-md">
                <div class="rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-soft sm:p-8">
                    <h2 class="font-display text-xl text-sharm-deep">{{ __('site.my_photos.form_title') }}</h2>
                    <p class="mt-1.5 text-sm text-sharm-mist">{{ __('site.my_photos.form_subtitle') }}</p>

                    @if (session('lookup_error'))
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                            {{ session('lookup_error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('my-photos.lookup', ['locale' => current_locale() === config('locales.default') ? null : current_locale()]) }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label for="booking_number" class="mb-1.5 block text-xs font-medium text-sharm-deep">{{ __('site.my_photos.booking_label') }}</label>
                            <input
                                type="text"
                                id="booking_number"
                                name="booking_number"
                                value="{{ old('booking_number') }}"
                                placeholder="SH-XXXXXX"
                                autocomplete="off"
                                required
                                class="w-full rounded-xl border-sharm-deep/15 bg-sharm-sand/40 px-4 py-3 font-mono text-sm uppercase tracking-wide text-sharm-deep placeholder:text-sharm-mist/50 focus:border-sharm-sea focus:ring-sharm-sea"
                            >
                            @error('booking_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="delivery_code" class="mb-1.5 block text-xs font-medium text-sharm-deep">{{ __('site.my_photos.code_label') }}</label>
                            <input
                                type="text"
                                id="delivery_code"
                                name="delivery_code"
                                placeholder="XXXXXX"
                                autocomplete="off"
                                required
                                class="w-full rounded-xl border-sharm-deep/15 bg-sharm-sand/40 px-4 py-3 font-mono text-sm uppercase tracking-widest text-sharm-deep placeholder:text-sharm-mist/50 focus:border-sharm-sea focus:ring-sharm-sea"
                            >
                            @error('delivery_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">{{ __('site.my_photos.submit') }}</button>
                    </form>

                    <p class="mt-4 text-center text-[11px] text-sharm-mist">{{ __('site.my_photos.help') }}</p>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
