{{-- Booking Modal (shared by Services inline form and Home packages section) --}}
@if ($packages->isNotEmpty())
<div
    x-show="modalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('site.booking.title') }}"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        class="absolute inset-0 bg-sharm-deep/50"
        @click="closeModal()"
    ></div>

    <div
        class="booking-modal-panel relative z-10"
        @click.stop
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
    >
        <button
            type="button"
            class="absolute right-4 top-4 rounded-lg p-1.5 text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
            @click="closeModal()"
            aria-label="Close"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="text-center">
            <p class="section-label">{{ __('site.booking.label') }}</p>
            <h2 class="section-title mt-3 text-2xl">{{ __('site.booking.title') }}</h2>
        </div>

        <div x-show="success" x-cloak class="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="mt-3 font-display text-lg text-sharm-deep">{{ __('site.booking.success_title') }}</h3>
            <p class="mt-1 text-sm text-sharm-mist">{{ __('site.booking.success') }}</p>
        </div>

        <form x-ref="modalForm" x-show="!success" @submit.prevent="submit()" novalidate class="mt-8 space-y-5">
            @include('partials.booking-fields', [
                'phoneRef' => 'modalPhoneInput',
                'nameId' => 'modal-booking-name',
                'emailId' => 'modal-booking-email',
                'dateRef' => 'modalDateInput',
                'dateId' => 'modal-booking-date',
                'compactPackages' => true,
            ])

            <button type="submit" class="btn-primary w-full" :disabled="loading">
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
</div>
@endif
