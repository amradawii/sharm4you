@php
    $phoneRef = $phoneRef ?? 'phoneInput';
    $nameId = $nameId ?? 'booking-name';
    $emailId = $emailId ?? 'booking-email';
    $dateRef = $dateRef ?? 'dateInput';
    $dateId = $dateId ?? 'booking-date';
    $compactPackages = $compactPackages ?? false;
@endphp

<div>
    <label for="{{ $nameId }}" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.booking.name') }}</label>
    <input
        id="{{ $nameId }}"
        type="text"
        x-model="form.name"
        class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
        :class="errors.name ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
        placeholder="{{ __('site.booking.name_placeholder') }}"
    >
    <p class="mt-1 text-xs text-red-500" x-show="errors.name" x-text="errors.name?.[0]"></p>
</div>

<div>
    <p class="mb-3 block text-sm font-medium text-sharm-deep">{{ __('site.booking.package') }}</p>
    <div
        class="grid grid-cols-3 {{ $compactPackages ? 'gap-2 sm:gap-3' : 'grid-cols-1 gap-3 sm:grid-cols-3' }}"
        :class="{ 'rounded-xl ring-2 ring-red-400/30': errors.package_id }"
    >
        @foreach ($packages as $package)
            <label
                class="booking-package-option {{ $compactPackages ? 'booking-package-option-compact' : '' }}"
                :class="{ 'is-selected': String(form.package_id) === '{{ $package->id }}' }"
            >
                <input
                    type="radio"
                    name="package_id_{{ $nameId }}"
                    value="{{ $package->id }}"
                    x-model="form.package_id"
                    class="sr-only"
                >
                <span class="booking-package-option-name">{{ $package->name }}</span>
                <span class="booking-package-option-price">
                    @if ($package->is_discounted)
                        <span class="block text-xs line-through opacity-70">{{ $package->formatted_price }}</span>
                        <span class="block">{{ $package->formatted_discounted_price }}</span>
                        <span class="mt-1 inline-block rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">-{{ $package->discount_percent }}%</span>
                    @else
                        {{ $package->formatted_price }}
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    <p class="mt-1.5 text-xs text-red-500" x-show="errors.package_id" x-text="errors.package_id?.[0]"></p>
</div>

<div>
    <label for="{{ $dateId }}" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.booking.date') }}</label>
    <p class="mb-2 text-xs text-sharm-mist">{{ __('site.booking.validation.timezone_label') }}</p>
    <input
        id="{{ $dateId }}"
        type="text"
        x-ref="{{ $dateRef }}"
        x-model="form.session_date"
        autocomplete="off"
        class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
        :class="errors.session_date ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
        placeholder="{{ __('site.booking.date_placeholder') }}"
    >
    <p class="mt-1 text-xs text-red-500" x-show="errors.session_date" x-text="errors.session_date?.[0]"></p>
</div>

<div data-booking-field="session_time" tabindex="-1" x-show="form.package_id && form.session_date" x-cloak x-transition>
    <div x-show="availableSlots().length > 0">
        <p class="mb-3 block text-sm font-medium text-sharm-deep">{{ __('site.booking.time') }}</p>
        <div
            class="grid grid-cols-3 gap-2 sm:grid-cols-4"
            :class="{ 'rounded-xl ring-2 ring-red-400/30': errors.session_time }"
        >
            <template x-for="slot in availableSlots()" :key="slot">
                <button
                    type="button"
                    @click="form.session_time = slot"
                    class="booking-time-option"
                    :class="{ 'is-selected': form.session_time === slot }"
                    x-text="formatSlot(slot)"
                ></button>
            </template>
        </div>
    </div>

    <div
        x-show="availableSlots().length === 0"
        class="rounded-xl border border-sharm-deep/10 bg-sharm-sand-soft px-4 py-4 text-center"
    >
        <p class="text-sm font-medium text-sharm-deep">{{ __('site.booking.no_slots_title') }}</p>
        <p class="mt-1 text-xs leading-relaxed text-sharm-mist">{{ __('site.booking.no_slots') }}</p>
    </div>

</div>
<p class="mt-1.5 text-xs text-red-500" x-show="errors.session_time" x-text="errors.session_time?.[0]"></p>

<div>
    <label for="{{ $phoneRef }}-field" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.booking.phone') }}</label>
    <input
        id="{{ $phoneRef }}-field"
        type="tel"
        x-ref="{{ $phoneRef }}"
        class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
        :class="(errors.phone || errors.country_code) ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
        placeholder="{{ __('site.booking.phone_placeholder') }}"
    >
    <p class="mt-1 text-xs text-red-500" x-show="errors.phone" x-text="errors.phone?.[0]"></p>
    <p class="mt-1 text-xs text-red-500" x-show="errors.country_code" x-text="errors.country_code?.[0]"></p>
</div>

<div>
    <label for="{{ $emailId }}" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.booking.email') }}</label>
    <input
        id="{{ $emailId }}"
        type="email"
        x-model="form.email"
        class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
        :class="errors.email ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
        placeholder="{{ __('site.booking.email_placeholder') }}"
    >
    <p class="mt-1 text-xs text-red-500" x-show="errors.email" x-text="errors.email?.[0]"></p>
</div>
