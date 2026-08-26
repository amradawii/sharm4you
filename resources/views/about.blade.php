@extends('layouts.app')

@php
    $seoKey = 'about';
    $homeUrl = home_url();
    $servicesUrl = localized_url(current_locale(), 'services');
    $aboutImage = asset('assets/images/quick-about.webp');
@endphp

@section('title', __('seo.about.title'))
@section('meta_description', __('seo.about.description'))
@section('meta_keywords', __('seo.about.keywords'))

@section('og_image', $aboutImage)

@section('content')
    @include('partials.page-banner', [
        'title' => __('site.about.title'),
        'subtitle' => __('site.about.intro'),
    ])

    {{-- Why Us --}}
    <section class="section-padding bg-sharm-sand">
        <div class="container-site reveal" x-intersect.once="$el.classList.add('is-visible')">
            <div class="text-center">
                <p class="section-label">{{ __('site.why_us.label') }}</p>
                <h2 class="section-title mt-3">{{ __('site.why_us.title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">{{ __('site.about.why_subtitle') }}</p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach (__('site.why_us.items') as $item)
                    <article class="why-card">
                        <div class="why-icon">
                            @include('partials.why-us-icon', ['icon' => $item['icon']])
                        </div>
                        <h3 class="why-card-title mt-6">{{ $item['title'] }}</h3>
                        <p class="mt-4 text-sm leading-relaxed text-sharm-mist">{{ $item['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="section-padding bg-white">
        <div class="container-site reveal" x-intersect.once="$el.classList.add('is-visible')">
            <div class="text-center">
                <p class="section-label">{{ __('site.about.values_label') }}</p>
                <h2 class="section-title mt-3">{{ __('site.about.values_title') }}</h2>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-2">
                @foreach (__('site.about.values') as $value)
                    <article class="rounded-2xl border border-sharm-deep/8 bg-sharm-sand-soft/50 p-8">
                        <h3 class="font-display text-2xl text-sharm-deep">{{ $value['title'] }}</h3>
                        <p class="mt-4 text-sm leading-relaxed text-sharm-mist">{{ $value['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="contact-section-padding bg-[#FBF9F6]">
        <div
            class="container-site reveal text-center"
            x-intersect.once="$el.classList.add('is-visible')"
        >
            <p class="section-label">{{ __('site.about.cta_label') }}</p>
            <h2 class="section-title mt-2">{{ __('site.about.cta_title') }}</h2>
            <p class="mx-auto mt-3 max-w-lg text-sharm-mist">{{ __('site.about.cta_subtitle') }}</p>
            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ $servicesUrl }}" class="btn-primary">{{ __('site.about.cta_services') }}</a>
                <a href="{{ localized_url(current_locale(), 'gallery') }}" class="btn-outline">{{ __('site.hero.cta_gallery') }}</a>
            </div>
        </div>
    </section>
@endsection
