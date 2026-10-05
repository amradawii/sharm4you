@php
    $homeUrl = home_url();
    $galleryUrl = localized_url(current_locale(), 'gallery');
    $aboutUrl = localized_url(current_locale(), 'about');
    $servicesUrl = localized_url(current_locale(), 'services');
    $activeNav = current_nav_key();
    $navItems = [
        'home' => ['url' => $homeUrl, 'label' => __('site.nav.home')],
        'about' => ['url' => $aboutUrl, 'label' => __('site.nav.about')],
        'services' => ['url' => $servicesUrl, 'label' => __('site.nav.services')],
        'gallery' => ['url' => $galleryUrl, 'label' => __('site.nav.gallery')],
    ];
@endphp

<header
    class="site-header sticky top-0 z-40"
    :class="scrolled && 'is-scrolled'"
>
    {{-- Top bar --}}
    <div class="top-bar">
        <div class="container-site flex items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
            <p class="hidden text-[11px] tracking-wide text-white/75 sm:block">{{ __('site.topbar.location') }}</p>

            <div class="flex items-center gap-2.5 sm:ml-auto sm:gap-5">
                <a href="tel:{{ __('site.contact.phone_raw') }}" class="top-bar-link">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    <span>{{ __('site.contact.phone') }}</span>
                </a>
                <a href="mailto:{{ __('site.contact.email') }}" class="top-bar-link">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <span>{{ __('site.contact.email') }}</span>
                </a>
            </div>

            <div class="shrink-0 lg:hidden">
                @include('partials.lang-switcher', ['compact' => true])
            </div>
        </div>
    </div>

    {{-- Logo (far left) · nav cluster (far right): Home … Gallery · Book · Lang --}}
    <div class="header-main-row">
        <div class="container-site flex items-center justify-between gap-4 px-4 py-2 sm:px-6 sm:py-2.5 lg:px-8">
            <a href="{{ $homeUrl }}" class="site-logo-link shrink-0">
                @include('partials.site-logo')
            </a>

            <nav class="header-nav hidden shrink-0 items-center lg:flex">
                @foreach ($navItems as $key => $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="nav-link {{ $activeNav === $key ? 'nav-link-active' : '' }}"
                        @if ($activeNav === $key) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach

                <a
                    href="{{ $servicesUrl }}#book-session"
                    class="btn-primary header-book-btn"
                    @if ($packages->isNotEmpty()) @click.prevent="$dispatch('open-booking-modal')" @endif
                >
                    {{ __('site.nav.book') }}
                </a>

                @include('partials.lang-switcher')
            </nav>

            <button
                type="button"
                class="header-menu-toggle shrink-0 lg:hidden"
                @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen"
                aria-label="{{ __('site.nav.toggle_menu') }}"
            >
                <span class="block h-0.5 w-5 rounded-full bg-sharm-deep transition duration-300" :class="mobileOpen && 'translate-y-1.5 rotate-45'"></span>
                <span class="block h-0.5 w-5 rounded-full bg-sharm-deep transition duration-300" :class="mobileOpen && 'opacity-0'"></span>
                <span class="block h-0.5 w-5 rounded-full bg-sharm-deep transition duration-300" :class="mobileOpen && '-translate-y-1.5 -rotate-45'"></span>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div
        x-show="mobileOpen"
        x-collapse
        x-cloak
        class="border-t border-sharm-deep/5 bg-white/98 backdrop-blur-xl lg:hidden"
    >
        <div class="container-site space-y-4 px-4 py-4 sm:px-6">
            <nav class="flex flex-col gap-1">
                @foreach ($navItems as $key => $item)
                    <a
                        href="{{ $item['url'] }}"
                        @click="mobileOpen = false"
                        class="rounded-xl px-3 py-3 text-sm font-medium transition {{ $activeNav === $key ? 'bg-sharm-sea/10 text-sharm-sea' : 'text-sharm-deep hover:bg-sharm-sand hover:text-sharm-sea' }}"
                        @if ($activeNav === $key) aria-current="page" @endif
                    >{{ $item['label'] }}</a>
                @endforeach

                <a
                    href="{{ $servicesUrl }}#book-session"
                    @if ($packages->isNotEmpty()) @click.prevent="mobileOpen = false; $dispatch('open-booking-modal')" @else @click="mobileOpen = false" @endif
                    class="btn-primary mt-2 w-full text-center"
                >{{ __('site.nav.book') }}</a>
            </nav>
        </div>
    </div>
</header>
