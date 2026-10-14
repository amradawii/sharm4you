<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $seoKey = $seoKey ?? 'home';
        $seo = trans("seo.{$seoKey}");
        // Packages are passed only by routes that show the booking modal
        // (home, services). Other pages get an empty collection so the modal
        // gate and asset includes stay no-ops without a per-page query.
        $packages = $packages ?? collect();
        $packagesSlots = $packagesSlots ?? [];
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif

    <title>@yield('title', $seo['title'] ?? config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', $seo['description'] ?? '')">
    @if (trim($__env->yieldContent('meta_keywords')) !== '' || ! empty($seo['keywords']))
        <meta name="keywords" content="@yield('meta_keywords', $seo['keywords'] ?? '')">
    @endif

    <link rel="canonical" href="@yield('canonical', url()->current())">

    @foreach (alternate_urls() as $locale => $alternateUrl)
        <link rel="alternate" hreflang="{{ config("locales.supported.{$locale}.hreflang") }}" href="{{ $alternateUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ localized_url('en') }}">

    <meta property="og:locale" content="{{ og_locale() }}">
    <meta property="og:title" content="@yield('title', $seo['title'] ?? config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', $seo['description'] ?? '')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $seo['title'] ?? config('app.name'))">
    <meta name="twitter:description" content="@yield('meta_description', $seo['description'] ?? '')">
    @hasSection('og_image')
        <meta name="twitter:image" content="@yield('og_image')">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.5.0/css/flag-icons.min.css">

    @if ($packages->isNotEmpty())
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/css/intlTelInput.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
        <style>
            .iti { width: 100%; }
            .iti__country-container { border-radius: 0.75rem 0 0 0.75rem; }
        </style>
    @endif

    @stack('head')

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @include('partials.alpine-toast-store')

    @yield('json_ld')
</head>
<body
    x-data="{
        mobileOpen: false,
        langOpen: false,
        scrolled: false,
        init() {
            this.onScroll()
            if (window.location.hash) {
                setTimeout(() => this.scrollToSection(window.location.hash), 350)
            }
        },
        onScroll() {
            this.scrolled = window.scrollY > 20
        },
        scrollToSection(hash, duration = 1400) {
            if (!hash) return
            const id = hash.replace('#', '')
            const el = document.getElementById(id)
            if (!el) return
            this.mobileOpen = false
            const headerOffset = 148
            const start = window.scrollY
            const target = el.getBoundingClientRect().top + start - headerOffset
            const distance = target - start
            let startTime = null
            const ease = (t) => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2
            const step = (time) => {
                if (startTime === null) startTime = time
                const progress = Math.min((time - startTime) / duration, 1)
                window.scrollTo(0, start + distance * ease(progress))
                if (progress < 1) requestAnimationFrame(step)
            }
            requestAnimationFrame(step)
        },
        navigate(event, href) {
            const url = new URL(href, window.location.origin)
            if (!url.hash) return
            const samePage = url.pathname.replace(/\/$/, '') === window.location.pathname.replace(/\/$/, '')
            if (samePage) {
                event.preventDefault()
                this.scrollToSection(url.hash)
                history.pushState(null, '', url.hash)
            }
        }
    }"
    @scroll.window="onScroll()"
    class="font-sans"
>
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.whatsapp-float')

    @if ($packages->isNotEmpty())
        <div
            x-data="bookingForm(@js($packagesSlots))"
            @open-booking-modal.window="openModal($event.detail)"
            @keydown.escape.window="if (modalOpen) closeModal()"
        >
            @include('partials.booking-modal')
        </div>
        @include('partials.booking-form-scripts')
    @endif

    @stack('scripts')

    <x-toast />
</body>
</html>
