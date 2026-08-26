@extends('layouts.app')

@php
    $seoKey = 'home';
    $homeUrl = home_url();
    $galleryUrl = localized_url(current_locale(), 'gallery');
    $servicesUrl = localized_url(current_locale(), 'services');
    $heroImage = asset('assets/images/hero.jpg');
    $photographerImage = asset('assets/images/quick-about.webp');
@endphp

@section('title', __('seo.home.title'))
@section('meta_description', __('seo.home.description'))
@section('meta_keywords', __('seo.home.keywords'))

@section('og_image', $heroImage)

@section('json_ld')
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => config('app.name'),
                    'description' => __('seo.home.description'),
                    'inLanguage' => app()->getLocale(),
                ],
                [
                    '@type' => 'LocalBusiness',
                    '@id' => url('/').'#localbusiness',
                    'name' => config('app.name'),
                    'description' => __('seo.home.description'),
                    'url' => url('/'),
                    'telephone' => __('site.contact.phone_raw'),
                    'email' => __('site.contact.email'),
                    'image' => $heroImage,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Sharm El Sheikh',
                        'addressRegion' => 'South Sinai',
                        'addressCountry' => 'EG',
                    ],
                    'areaServed' => [
                        ['@type' => 'City', 'name' => 'Sharm El Sheikh'],
                        ['@type' => 'City', 'name' => 'Dahab'],
                        ['@type' => 'Place', 'name' => 'South Sinai'],
                    ],
                    'priceRange' => '$$',
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <section class="hero-section">
        <div class="absolute inset-0 overflow-hidden">
            <img
                src="{{ $heroImage }}"
                alt="{{ __('site.hero.image_alt') }}"
                class="h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-sharm-deep/25"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-sharm-deep/38 via-sharm-deep/12 to-sharm-deep/50"></div>
        </div>

        <div
            class="hero-content container-site reveal relative z-10 flex w-full flex-col items-center px-4 text-center sm:px-6 lg:px-8 lg:py-0"
            x-intersect.once="$el.classList.add('is-visible')"
        >
            <span class="hero-label">{{ __('site.hero.label') }}</span>
            <h1 class="hero-title mt-4 font-display font-bold leading-tight text-white">
                <span class="block">{{ __('site.hero.title_line1') }}</span>
                <span class="block">{{ __('site.hero.title_line2') }}</span>
            </h1>
            <p class="hero-subtitle mt-6 leading-relaxed text-white/90">
                {{ __('site.hero.subtitle') }}
            </p>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ $galleryUrl }}" class="btn-primary hero-cta">{{ __('site.hero.cta_gallery') }}</a>
                <button type="button" @click="$dispatch('open-booking-modal')" class="btn-ghost-light hero-cta">{{ __('site.hero.cta_book') }}</button>
            </div>
        </div>

        <div class="hero-lottie-wrap" aria-hidden="true">
            <div id="hero-lottie" class="hero-lottie"></div>
        </div>
    </section>

    {{-- Featured Gallery --}}
    @if ($featuredPhotos->isNotEmpty())
    @php
        $spanPattern = [
            'md:col-span-2 md:row-span-2', '', '', 'md:row-span-2',
            '', '', 'md:col-span-2', '', '', 'md:row-span-2', '', '',
        ];
    @endphp
    <section id="gallery" class="section-padding bg-sharm-sand-soft">
        <div class="container-site reveal" x-intersect.once="$el.classList.add('is-visible')">
            <div class="text-center">
                <p class="section-label">{{ __('site.gallery.label') }}</p>
                <h2 class="section-title mt-3">{{ __('site.gallery.title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">
                    {{ __('site.gallery.subtitle') }}
                </p>
            </div>

            <div class="gallery-bento mt-12">
                @foreach ($featuredPhotos as $index => $photo)
                    <figure
                        class="group relative bg-sharm-deep/5 {{ $spanPattern[$index] ?? '' }}"
                        style="animation-delay: {{ $index * 60 }}ms"
                    >
                        <img
                            src="{{ $photo->url }}"
                            alt="{{ $photo->alt }}"
                            class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="absolute inset-0 bg-sharm-deep/0 transition duration-500 group-hover:bg-sharm-deep/20"></div>
                    </figure>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <a href="{{ $galleryUrl }}" class="btn-outline-minimal">{{ __('site.gallery.cta_full') }}</a>
            </div>
        </div>
    </section>
    @endif

    {{-- Packages (cards only — booking opens in modal) --}}
    @include('partials.packages-section')

    {{-- Our Team --}}
    <section id="photographers" class="section-padding bg-[#FAFAF8]">
        <div class="container-site">
            <div
                class="reveal grid items-center gap-8 lg:grid-cols-2 lg:gap-12"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="order-2 lg:order-1">
                    <p class="section-label">{{ __('site.photographers.label') }}</p>
                    <h2 class="section-title mt-2">{{ __('site.photographers.title') }}</h2>
                    <p class="mt-4 max-w-lg text-sm leading-relaxed text-sharm-mist">
                        {{ __('site.photographers.intro') }}
                    </p>
                    <ul class="mt-5 space-y-2.5">
                        @foreach (__('site.photographers.points') as $point)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-sharm-mist">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sharm-sea"></span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-7 text-center lg:text-left">
                        <a href="{{ $servicesUrl }}" class="btn-primary">{{ __('site.photographers.cta') }}</a>
                    </div>
                </div>

                <div class="order-1 overflow-hidden rounded-2xl shadow-xl shadow-sharm-deep/[0.06] lg:order-2">
                    <img
                        src="{{ $photographerImage }}"
                        alt="{{ __('site.photographers.image_alt') }}"
                        class="aspect-[5/4] w-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    @php
        $testimonials = $testimonials ?? collect();
        $marqueeReviews = $testimonials->isNotEmpty() ? $testimonials->concat($testimonials) : collect();
    @endphp
    <section id="testimonials" class="section-padding bg-sharm-sand">
        <div class="container-site reveal" x-intersect.once="$el.classList.add('is-visible')">
            <div class="text-center">
                <p class="section-label">{{ __('site.testimonials.label') }}</p>
                <h2 class="section-title mt-3">{{ __('site.testimonials.title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">
                    {{ __('site.testimonials.subtitle') }}
                </p>
            </div>
        </div>

        @if ($marqueeReviews->isNotEmpty())
            <div class="testimonials-marquee mt-12">
                <div class="testimonials-marquee-track">
                    @foreach ($marqueeReviews as $review)
                        @include('partials.review-card', ['review' => $review])
                    @endforeach
                </div>
            </div>
        @else
            <p class="container-site mt-12 text-center text-sm text-sharm-mist">{{ __('site.testimonials.empty') }}</p>
        @endif

        <div class="container-site">
            @include('partials.review-form')
        </div>
    </section>

    {{-- FAQs --}}
    <section id="faqs" class="section-padding bg-white">
        <div
            class="container-site reveal max-w-3xl"
            x-data="{ open: null }"
            x-intersect.once="$el.classList.add('is-visible')"
        >
            <div class="text-center">
                <p class="section-label">{{ __('site.faqs.label') }}</p>
                <h2 class="section-title mt-3">{{ __('site.faqs.title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">
                    {{ __('site.faqs.subtitle') }}
                </p>
            </div>

            <div class="faq-list">
                @foreach (__('site.faqs.items') as $index => $faq)
                    <div class="faq-item">
                        <button
                            type="button"
                            class="faq-trigger"
                            :class="open === {{ $index }} && 'is-open'"
                            @click="open = open === {{ $index }} ? null : {{ $index }}"
                            :aria-expanded="open === {{ $index }}"
                        >
                            <span class="text-sm font-medium text-sharm-deep sm:text-base">{{ $faq['q'] }}</span>
                            <span
                                class="faq-icon"
                                :class="open === {{ $index }} && 'is-open'"
                            >+</span>
                        </button>
                        <div x-show="open === {{ $index }}" x-collapse x-cloak>
                            <p class="faq-answer">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="contact-section contact-section-padding">
        @include('partials.banner-curves')

        <div class="container-site reveal relative z-10 text-center" x-intersect.once="$el.classList.add('is-visible')">
            <p class="section-label">{{ __('site.contact.label') }}</p>
            <h2 class="section-title mt-2">
                {{ __('site.contact.title') }}
            </h2>
            <p class="mx-auto mt-3 max-w-lg text-sharm-mist">
                {{ __('site.contact.subtitle') }}
            </p>
            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <a href="mailto:{{ __('site.contact.email') }}" class="btn-primary gap-2">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    {{ __('site.contact.email') }}
                </a>
                <a href="tel:{{ __('site.contact.phone_raw') }}" class="btn-outline gap-2">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    {{ __('site.contact.phone') }}
                </a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/lottie-web@5.12.2/build/player/lottie.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('hero-lottie')
            if (!container || typeof lottie === 'undefined') return

            lottie.loadAnimation({
                container,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: @json(asset('lottie/eyes_hours.json')),
            })
        })
    </script>
@endpush
