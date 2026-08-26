@if ($featuredPhotos->isNotEmpty())
@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css">
@endpush

<section class="featured-polaroid-section overflow-x-clip" aria-label="{{ __('site.gallery.title') }}">
    <div class="container-site px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="section-label">{{ __('site.gallery.label') }}</p>
            <h1 class="section-title mt-3">{{ __('site.gallery.title') }}</h1>
        </div>
    </div>

    <div id="featured-polaroid-slider" class="splide featured-polaroid-splide mt-10" aria-label="{{ __('site.gallery.title') }}">
        <div class="splide__track">
            <ul class="splide__list">
                @foreach ($featuredPhotos as $photo)
                    <li class="splide__slide">
                        <div class="polaroid-card group">
                            <div class="polaroid-card-photo">
                                <img
                                    src="{{ $photo->url }}"
                                    alt="{{ $photo->alt }}"
                                    class="polaroid-card-image"
                                    loading="lazy"
                                    draggable="false"
                                >
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('featured-polaroid-slider')
            if (!el || typeof Splide === 'undefined') return

            const slideCount = el.querySelectorAll('.splide__slide').length
            const perPage = window.matchMedia('(min-width: 1024px)').matches
                ? 4
                : window.matchMedia('(min-width: 768px)').matches
                    ? 2
                    : 1

            new Splide(el, {
                type: slideCount > perPage ? 'loop' : 'slide',
                perPage: 4,
                perMove: 1,
                gap: '1rem',
                padding: { left: '1rem', right: '1rem' },
                autoplay: slideCount > 1,
                interval: 3200,
                pauseOnHover: true,
                pauseOnFocus: true,
                arrows: false,
                pagination: false,
                speed: 800,
                easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                breakpoints: {
                    1024: { perPage: 3, gap: '1rem', padding: { left: '1rem', right: '1rem' } },
                    768: { perPage: 2, gap: '0.875rem', padding: { left: '0.75rem', right: '0.75rem' } },
                    640: { perPage: 1, gap: '0.75rem', padding: { left: '1.5rem', right: '1.5rem' } },
                },
            }).mount()
        })
    </script>
@endpush
@endif
