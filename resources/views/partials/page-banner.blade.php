<section class="page-banner">
    @include('partials.banner-curves')

    <div class="container-site relative z-10 px-4 py-12 text-center sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        @if (! empty($label))
            <p class="section-label">{{ $label }}</p>
        @endif
        <h1 class="section-title {{ ! empty($label) ? 'mt-3' : '' }}">{{ $title }}</h1>
        @if (! empty($subtitle))
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-sharm-mist sm:text-base">{{ $subtitle }}</p>
        @endif
    </div>
</section>
