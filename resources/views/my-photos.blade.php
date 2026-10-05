@extends('layouts.app')

@section('title', __('seo.my_photos.title'))
@section('meta_description', __('seo.my_photos.description'))
@section('robots', 'noindex, nofollow')

@section('content')
@include('partials.page-banner', [
    'label' => __('site.my_photos.label'),
    'title' => __('site.my_photos.title'),
    'subtitle' => __('site.my_photos.subtitle'),
])

<section class="section-padding bg-sharm-sand">
    <div class="container-site px-4 sm:px-6 lg:px-8">
        @if ($submitted && $booking)
            {{-- Delivered photos --}}
            <div class="mx-auto max-w-5xl">
                <div
                    class="rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-soft sm:p-8"
                    x-data="myPhotosPage()"
                    @keydown.escape.window="if (lightbox) close()"
                    @keydown.arrow-left.window="if (lightbox) prev()"
                    @keydown.arrow-right.window="if (lightbox) next()"
                >
                    <div class="flex flex-col gap-1 border-b border-sharm-deep/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="section-label">{{ __('site.my_photos.ready_label') }}</p>
                            <h2 class="mt-2 font-display text-2xl text-sharm-deep sm:text-3xl">{{ __('site.my_photos.ready_title', ['name' => $booking->name]) }}</h2>
                            <p class="mt-1 text-sm text-sharm-mist">
                                {{ __('site.my_photos.booking') }}: <span class="font-mono font-semibold text-sharm-deep">{{ $booking->booking_number }}</span>
                                @if ($booking->package)
                                    · {{ $booking->package->name }}
                                @endif
                                @if ($booking->session_date)
                                    · {{ $booking->session_date->translatedFormat('M j, Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="mt-3 flex shrink-0 items-center gap-3 sm:mt-0">
                            <span class="rounded-full bg-sharm-sea/10 px-3 py-1.5 text-xs font-medium text-sharm-sea">
                                {{ trans_choice('site.my_photos.photo_count', $photos->count(), ['count' => $photos->count()]) }}
                            </span>
                            <button
                                type="button"
                                class="btn-primary gap-2"
                                @click="downloadAll()"
                                :disabled="downloading"
                            >
                                <template x-if="!downloading">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        {{ __('site.my_photos.download_all') }}
                                    </span>
                                </template>
                                <template x-if="downloading">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span x-text="downloadLabel"></span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-sharm-mist">{{ __('site.my_photos.download_hint') }}</p>

                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        @foreach ($photos as $index => $photo)
                            <button
                                type="button"
                                class="group relative block aspect-square overflow-hidden rounded-xl border border-sharm-deep/10 bg-sharm-sand"
                                @click="open({{ $index }})"
                            >
                                <img
                                    src="{{ $photo->preview_url }}"
                                    alt="{{ $booking->name }} — {{ config('app.name') }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <span class="absolute inset-0 flex items-center justify-center bg-sharm-deep/0 transition group-hover:bg-sharm-deep/40">
                                    <span class="flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-sharm-deep opacity-0 shadow-sm transition group-hover:opacity-100">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Lightbox --}}
                    <div
                        x-show="lightbox"
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/95 p-4"
                        @click.self="close()"
                    >
                        <button
                            type="button"
                            class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center text-2xl text-white/80 transition hover:text-white"
                            @click="close()"
                            aria-label="{{ __('site.my_photos.close') }}"
                        >&times;</button>

                        <button
                            type="button"
                            class="absolute left-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-sharm-deep/60 text-3xl text-white/80 transition hover:text-white sm:left-4"
                            @click="prev()"
                            aria-label="{{ __('site.my_photos.previous') }}"
                        >&lsaquo;</button>

                        <button
                            type="button"
                            class="absolute right-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-sharm-deep/60 text-3xl text-white/80 transition hover:text-white sm:right-4"
                            @click="next()"
                            aria-label="{{ __('site.my_photos.next') }}"
                        >&rsaquo;</button>

                        <div class="relative max-h-[85vh] max-w-5xl">
                            <img
                                :src="photos[current]?.src"
                                :alt="photos[current]?.alt"
                                class="max-h-[85vh] w-auto max-w-full object-contain"
                            >
                            <div class="mt-4 flex items-center justify-center gap-3">
                                <a
                                    :href="photos[current]?.download_url"
                                    :download="photos[current]?.filename"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white/95 px-4 py-2.5 text-sm font-medium text-sharm-deep shadow-sm transition hover:bg-white"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    {{ __('site.my_photos.download_lightbox') }}
                                </a>
                                <span class="text-sm text-white/70" x-text="(current + 1) + ' / ' + photos.length"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 rounded-xl bg-sharm-sand/70 p-4 text-center text-xs text-sharm-mist">
                        {{ __('site.my_photos.thanks') }}
                    </div>
                </div>
            </div>
        @else
            {{-- Lookup form --}}
            <div class="mx-auto max-w-md">
                <div class="rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-soft sm:p-8">
                    <h2 class="font-display text-xl text-sharm-deep">{{ __('site.my_photos.form_title') }}</h2>
                    <p class="mt-1.5 text-sm text-sharm-mist">{{ __('site.my_photos.form_subtitle') }}</p>

                    @if (session('lookup_error'))
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                            {{ session('lookup_error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ localized_url(current_locale(), 'my-photos') }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label for="booking_number" class="mb-1.5 block text-xs font-medium text-sharm-deep">{{ __('site.my_photos.booking_label') }}</label>
                            <input
                                type="text"
                                id="booking_number"
                                name="booking_number"
                                value="{{ is_string(old('booking_number')) ? old('booking_number') : '' }}"
                                placeholder="SH-XXXXXX"
                                autocomplete="off"
                                required
                                class="w-full rounded-xl border-sharm-deep/15 bg-sharm-sand/40 px-4 py-3 font-mono text-sm uppercase tracking-wide text-sharm-deep placeholder:text-sharm-mist/50 focus:border-sharm-sea focus:ring-sharm-sea"
                            >
                            @error('booking_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="delivery_code" class="mb-1.5 block text-xs font-medium text-sharm-deep">{{ __('site.my_photos.code_label') }}</label>
                            <input
                                type="text"
                                id="delivery_code"
                                name="delivery_code"
                                placeholder="XXXXXX"
                                autocomplete="off"
                                required
                                class="w-full rounded-xl border-sharm-deep/15 bg-sharm-sand/40 px-4 py-3 font-mono text-sm uppercase tracking-widest text-sharm-deep placeholder:text-sharm-mist/50 focus:border-sharm-sea focus:ring-sharm-sea"
                            >
                            @error('delivery_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">{{ __('site.my_photos.submit') }}</button>
                    </form>

                    <p class="mt-4 text-center text-[11px] text-sharm-mist">{{ __('site.my_photos.help') }}</p>
                </div>
            </div>
        @endif
    </div>
</section>

@if ($submitted && $booking)
<script>
    window.myPhotosPage = function () {
        return {
            lightbox: false,
            current: 0,
            photos: {{ Illuminate\Support\Js::from($photos->map(fn ($p) => [
                'src' => $p->preview_url,
                'alt' => $booking->name . ' — ' . config('app.name'),
                'download_url' => $p->download_url,
                'filename' => $p->filename,
            ])) }},
            downloading: false,
            downloadLabel: '',
            downloadAllUrl: @json(localized_url(current_locale(), 'my-photos/download-all')),
            bookingNumber: @json($booking->booking_number),
            deliveryCode: @json($booking->delivery_code),
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            preparingLabel: @json(__('site.my_photos.download_preparing')),
            doneLabel: @json(__('site.my_photos.download_done')),
            failedLabel: @json(__('site.my_photos.download_failed')),
            networkLabel: @json(__('site.my_photos.network_error')),

            open(index) {
                this.current = index
                this.lightbox = true
                document.body.style.overflow = 'hidden'
            },
            close() {
                this.lightbox = false
                document.body.style.overflow = ''
            },
            prev() {
                this.current = (this.current - 1 + this.photos.length) % this.photos.length
            },
            next() {
                this.current = (this.current + 1) % this.photos.length
            },

            async downloadAll() {
                if (this.downloading) return
                this.downloading = true
                this.downloadLabel = this.preparingLabel
                try {
                    const res = await fetch(this.downloadAllUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            booking_number: this.bookingNumber,
                            delivery_code: this.deliveryCode,
                        }),
                    })
                    if (!res.ok) {
                        const data = await window.readJsonResponse(res, this.failedLabel)
                        window.toastFromResponse(data, this.failedLabel)
                        return
                    }
                    const blob = await res.blob()
                    const url = URL.createObjectURL(blob)
                    const a = document.createElement('a')
                    a.href = url
                    a.download = this.bookingNumber + '-photos.zip'
                    document.body.appendChild(a)
                    a.click()
                    document.body.removeChild(a)
                    URL.revokeObjectURL(url)
                    this.downloadLabel = this.doneLabel
                    setTimeout(() => { this.downloading = false }, 2000)
                } catch (e) {
                    window.showToast('error', this.networkLabel)
                } finally {
                    if (this.downloading && this.downloadLabel !== this.doneLabel) {
                        this.downloading = false
                    }
                }
            },
        }
    }
</script>
@endif
@if ($errors->any() || session('lookup_error'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.showToast('error', @js($errors->first() ?: session('lookup_error')))
            })
        </script>
    @endpush
@endif
@endsection
