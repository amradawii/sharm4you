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
<a href="mailto:{{ __('site.contact.email') }}" class="top-bar-link">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <span>{{ __('site.contact.email') }}</span>
                </a>
                <a href="tel:{{ __('site.contact.phone_raw') }}" class="top-bar-link" aria-label="{{ __('site.contact.phone') }}">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                </a>
                <a href="https://wa.me/{{ ltrim(__('site.contact.phone_raw'), '+') }}" target="_blank" rel="noopener noreferrer" class="top-bar-link" aria-label="WhatsApp">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                    </svg>
                </a>
                <a href="https://www.instagram.com/sharm_4_you/" target="_blank" rel="noopener noreferrer" class="top-bar-link" aria-label="Instagram">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="5" />
                        <circle cx="12" cy="12" r="4" />
                        <circle cx="17.25" cy="6.75" r="0.75" fill="currentColor" />
                    </svg>
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
