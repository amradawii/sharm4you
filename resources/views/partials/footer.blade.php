@php
    $homeUrl = home_url();
    $galleryUrl = localized_url(current_locale(), 'gallery');
    $aboutUrl = localized_url(current_locale(), 'about');
    $servicesUrl = localized_url(current_locale(), 'services');
@endphp

<footer class="border-t border-sharm-deep/5 bg-sharm-deep text-white/80">
    <div class="container-site section-padding !pb-8">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <a href="{{ $homeUrl }}" class="site-logo-link inline-block">
                    @include('partials.site-logo', ['variant' => 'light', 'size' => 'footer'])
                </a>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/55">
                    {{ __('site.footer.tagline') }}
                </p>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sharm-reef">{{ __('site.footer.explore') }}</p>
                <nav class="mt-4 flex flex-col gap-2.5 text-sm">
                    <a href="{{ $homeUrl }}" class="transition hover:text-white">{{ __('site.nav.home') }}</a>
                    <a href="{{ $aboutUrl }}" class="transition hover:text-white">{{ __('site.nav.about') }}</a>
                    <a href="{{ $servicesUrl }}" class="transition hover:text-white">{{ __('site.nav.services') }}</a>
                    <a href="{{ $galleryUrl }}" class="transition hover:text-white">{{ __('site.nav.gallery') }}</a>
                    <a href="{{ $homeUrl }}#contact" @click="navigate($event, '{{ $homeUrl }}#contact')" class="transition hover:text-white">{{ __('site.nav.contact') }}</a>
                </nav>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sharm-reef">{{ __('site.footer.connect') }}</p>
                <div class="mt-4 flex flex-col gap-2.5 text-sm">
                    <a href="mailto:{{ __('site.contact.email') }}" class="inline-flex items-center gap-2 transition hover:text-white">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        {{ __('site.contact.email') }}
                    </a>
                    <a href="tel:{{ __('site.contact.phone_raw') }}" class="inline-flex items-center gap-2 transition hover:text-white">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                        {{ __('site.contact.phone') }}
                    </a>
                    <span class="inline-flex items-center gap-2 text-white/45">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        {{ __('site.footer.location') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 text-xs text-white/35 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ __('site.brand') }}. {{ __('site.footer.rights') }}</p>
            <p>{{ __('site.footer.made_in') }}</p>
        </div>
    </div>
</footer>
