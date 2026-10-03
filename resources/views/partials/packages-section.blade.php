@if ($packages->isNotEmpty())
<section id="packages" class="section-padding bg-white">
    <div class="container-site reveal" x-intersect.once="$el.classList.add('is-visible')">
        <div class="text-center">
            <p class="section-label">{{ __('site.packages.label') }}</p>
            <h2 class="section-title mt-3">{{ __('site.packages.title') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sharm-mist">
                {{ __('site.packages.subtitle') }}
            </p>
            <p class="mx-auto mt-3 flex max-w-2xl items-center justify-center gap-1.5 text-sm font-medium text-sharm-deep">
                <svg class="h-4 w-4 text-sharm-sea" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                {{ __('site.packages.location') }}
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ($packages as $index => $package)
                <article
                    class="package-card reveal {{ $package->is_featured ? 'featured' : '' }}"
                    x-intersect.once="$el.classList.add('is-visible')"
                    style="animation-delay: {{ $index * 100 }}ms"
                >
                    <div class="package-card-image-wrap">
                        @if ($package->image_url)
                            <img
                                src="{{ $package->image_url }}"
                                alt="{{ $package->name }}"
                                class="package-card-image"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center text-sm text-sharm-mist/70">
                                {{ $package->name }}
                            </div>
                        @endif
                        @if ($package->is_featured)
                            <span class="package-card-badge">{{ __('site.packages.popular') }}</span>
                        @endif
                        @if ($package->is_discounted)
                            <span class="package-card-discount-badge">-{{ $package->discount_percent }}%</span>
                        @endif
                    </div>
                    <div class="package-card-body">
                        <h3 class="font-display text-2xl text-sharm-deep">{{ $package->name }}</h3>
                        <p class="mt-4 flex-1 text-sm leading-relaxed text-sharm-mist">{{ $package->description }}</p>
                        <div class="mt-6">
                            @if ($package->is_discounted)
                                <p class="text-sm text-sharm-mist line-through">{{ $package->formatted_price }}</p>
                                <p class="font-display text-4xl tracking-tight text-sharm-desert">{{ $package->formatted_discounted_price }}</p>
                            @else
                                <p class="font-display text-4xl tracking-tight text-sharm-desert">{{ $package->formatted_price }}</p>
                            @endif
                        </div>
                        <button
                            type="button"
                            @click="$dispatch('open-booking-modal', {{ $package->id }})"
                            class="btn-primary mt-8 w-full text-center"
                        >{{ __('site.packages.book') }}</button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@else
<section id="packages" class="section-padding bg-white">
    <div class="container-site text-center">
        <p class="section-label">{{ __('site.packages.label') }}</p>
        <h2 class="section-title mt-3">{{ __('site.packages.title') }}</h2>
        <p class="mx-auto mt-4 max-w-lg text-sharm-mist">{{ __('site.packages.no_packages') }}</p>
    </div>
</section>
@endif
