@php
    $current = config('locales.supported.'.current_locale());
    $compact = $compact ?? false;
@endphp

<div class="relative" @click.outside="langOpen = false">
    <button
        type="button"
        class="lang-switcher-trigger {{ $compact ? 'lang-switcher-trigger-compact' : '' }}"
        @click="langOpen = !langOpen"
        :aria-expanded="langOpen"
        aria-label="{{ __('site.topbar.language') }}"
    >
        <span class="fi fi-{{ $current['flag_code'] ?? '' }} rounded-sm" aria-hidden="true"></span>
        @if ($compact)
            <span class="uppercase tracking-wide">{{ current_locale() }}</span>
        @else
            <span>{{ $current['native'] }}</span>
        @endif
        <svg class="h-3 w-3 opacity-60 transition duration-200" :class="langOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div
        x-show="langOpen"
        x-cloak
        x-transition
        class="lang-switcher-menu {{ $compact ? 'lang-switcher-menu-compact' : '' }}"
    >
        @foreach (config('locales.supported') as $code => $locale)
            <a
                href="{{ localized_url($code) }}"
                class="lang-switcher-option {{ current_locale() === $code ? 'is-active' : '' }}"
                @click="langOpen = false"
            >
                <span class="fi fi-{{ $locale['flag_code'] ?? '' }} rounded-sm" aria-hidden="true"></span>
                <span>{{ $locale['native'] }}</span>
            </a>
        @endforeach
    </div>
</div>
