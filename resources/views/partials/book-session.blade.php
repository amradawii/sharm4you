@if ($packages->isNotEmpty())
<section id="book-session" class="section-padding bg-sharm-sand-soft">
    <div
        class="container-site reveal max-w-3xl"
        x-intersect.once="$el.classList.add('is-visible')"
    >
        <div class="text-center">
            <p class="section-label">{{ __('site.booking.label') }}</p>
            <h2 class="section-title mt-3">{{ __('site.booking.title') }}</h2>
            <p class="mx-auto mt-4 max-w-lg text-sharm-mist">{{ __('site.booking.subtitle') }}</p>
        </div>

        <div x-show="success && !modalOpen" x-cloak class="mt-10 rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="mt-4 font-display text-xl text-sharm-deep">{{ __('site.booking.success_title') }}</h3>
            <p class="mt-2 text-sm text-sharm-mist">{{ __('site.booking.success') }}</p>
        </div>

        <form x-show="!success && !modalOpen" @submit.prevent="submit()" class="mt-10 space-y-5">
            @include('partials.booking-fields', ['phoneRef' => 'phoneInput', 'nameId' => 'booking-name', 'emailId' => 'booking-email', 'dateRef' => 'dateInput', 'dateId' => 'booking-date'])

            <button type="submit" class="btn-primary w-full" :disabled="loading || (form.package_id && form.session_date && availableSlots().length === 0)">
                <span x-show="!loading">{{ __('site.booking.submit') }}</span>
                <span x-show="loading" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('site.booking.submitting') }}
                </span>
            </button>
        </form>
    </div>
</section>
@endif
