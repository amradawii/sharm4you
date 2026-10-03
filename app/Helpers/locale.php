<?php

if (! function_exists('locale_prefixes')) {
    /** @return list<string> */
    function locale_prefixes(): array
    {
        $default = config('locales.default', 'en');

        return array_values(array_filter(
            array_keys(config('locales.supported', [])),
            fn (string $code) => $code !== $default
        ));
    }
}

if (! function_exists('is_locale_prefix')) {
    function is_locale_prefix(string $segment): bool
    {
        return in_array($segment, locale_prefixes(), true);
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('localized_url')) {
    function localized_url(string $locale, ?string $path = null): string
    {
        $default = config('locales.default', 'en');

        if ($path === null) {
            $segments = explode('/', trim(request()->path(), '/'));

            if ($segments !== [] && is_locale_prefix($segments[0])) {
                array_shift($segments);
            }

            $relativePath = implode('/', $segments);
        } else {
            $relativePath = trim($path, '/');
            $segments = $relativePath === '' ? [] : explode('/', $relativePath);

            if ($segments !== [] && is_locale_prefix($segments[0])) {
                array_shift($segments);
                $relativePath = implode('/', $segments);
            }
        }

        if ($locale === $default) {
            return url($relativePath !== '' ? '/'.$relativePath : '/');
        }

        $prefixed = $locale.($relativePath !== '' ? '/'.$relativePath : '');

        return url('/'.$prefixed);
    }
}

if (! function_exists('home_url')) {
    function home_url(?string $locale = null): string
    {
        return localized_url($locale ?? current_locale(), '');
    }
}

if (! function_exists('alternate_urls')) {
    function alternate_urls(): array
    {
        $urls = [];

        foreach (array_keys(config('locales.supported', [])) as $locale) {
            $urls[$locale] = localized_url($locale);
        }

        return $urls;
    }
}

if (! function_exists('og_locale')) {
    function og_locale(): string
    {
        return match (app()->getLocale()) {
            'ru' => 'ru_RU',
            'it' => 'it_IT',
            'ro' => 'ro_RO',
            'bg' => 'bg_BG',
            'tr' => 'tr_TR',
            default => 'en_US',
        };
    }
}

if (! function_exists('current_nav_key')) {
    function current_nav_key(): string
    {
        $path = trim(request()->path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        if ($segments !== [] && is_locale_prefix($segments[0])) {
            array_shift($segments);
        }

        $path = implode('/', $segments);

        if ($path === '') {
            return 'home';
        }

        if (str_starts_with($path, 'gallery')) {
            return 'gallery';
        }

        return match ($path) {
            'about' => 'about',
            'services' => 'services',
            default => '',
        };
    }
}
