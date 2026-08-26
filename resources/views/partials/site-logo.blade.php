@php
    $variant = $variant ?? 'default';
    $size = $size ?? 'header';
    $attrs = match ($size) {
        'admin' => ['width' => 52, 'height' => 52],
        'footer' => ['width' => 112, 'height' => 112],
        default => ['width' => 80, 'height' => 80],
    };
@endphp

<img
    src="{{ asset('assets/logo.png') }}"
    alt="{{ __('site.brand') }}"
    class="site-logo site-logo-{{ $size }} {{ $variant === 'light' ? 'site-logo-light' : '' }}"
    width="{{ $attrs['width'] }}"
    height="{{ $attrs['height'] }}"
    decoding="async"
>
