@extends('admin.layouts.app')

@section('title', 'Booking ' . $booking->booking_number)
@section('page_title', 'Booking ' . $booking->booking_number)
@section('breadcrumb', 'Deliver photos to the customer')

@section('content')
<div
    x-data="deliveryManager({
        uploadUrl: '{{ route('admin.bookings.photos.upload', $booking) }}',
        deliveryUrl: '{{ route('admin.bookings.delivery', $booking) }}',
        deleteBase: '{{ url('admin/bookings/photos') }}',
        photos: {{ Illuminate\Support\Js::from($photosJson) }},
        storage: {{ Illuminate\Support\Js::from($storage) }},
        existingCode: {{ Illuminate\Support\Js::from($booking->delivery_code) }},
        existingMessage: {{ Illuminate\Support\Js::from($whatsappMessage) }},
    })"
    x-cloak
>
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-sharm-mist transition hover:text-sharm-deep">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Back to Bookings
    </a>

    <div class="mt-5 grid gap-6 lg:grid-cols-3">
        {{-- Left: booking info --}}
        <div class="admin-card p-5 lg:col-span-1">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-sharm-deep">Booking details</h2>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $booking->status_color }}">{{ $booking->status_label }}</span>
            </div>

            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Booking number</dt>
                    <dd class="font-mono font-semibold text-sharm-deep">{{ $booking->booking_number }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Source</dt>
                    <dd>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $booking->source_color }}">{{ $booking->source_label }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Customer</dt>
                    <dd class="text-sharm-deep">{{ $booking->name }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Phone</dt>
                    <dd><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->full_phone) }}" target="_blank" rel="noopener" class="text-sharm-sea hover:underline">{{ $booking->full_phone }}</a></dd>
                </div>
                @if ($booking->email)
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Email</dt>
                        <dd><a href="mailto:{{ $booking->email }}" class="text-sharm-sea hover:underline">{{ $booking->email }}</a></dd>
                    </div>
                @endif
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Package</dt>
                    <dd class="text-sharm-deep">{{ $booking->package?->name_en ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Session</dt>
                    <dd class="text-sharm-deep">{{ $booking->session_datetime_label }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-sharm-mist">Amount collected</dt>
                    <dd class="font-semibold text-sharm-deep">{{ $booking->amount_label }}</dd>
                </div>
            </dl>

            {{-- Storage meter --}}
            <div class="mt-6 border-t border-sharm-deep/10 pt-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-sharm-deep">R2 storage</span>
                    <span class="text-sharm-mist"><span x-text="storage.used_label"></span> / <span x-text="storage.limit_label"></span> free</span>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-sharm-sand-dark">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="storage.percent >= 90 ? 'bg-red-500' : (storage.percent >= 70 ? 'bg-amber-500' : 'bg-emerald-500')"
                        :style="`width: ${Math.min(100, storage.percent)}%`"
                    ></div>
                </div>
                <p class="mt-1.5 text-[10px] text-sharm-mist" x-text="`${storage.percent}% used — pay-as-you-go begins past the 10 GB free tier`"></p>
            </div>
        </div>

        {{-- Right: delivery --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Upload panel --}}
            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-sharm-deep">Upload photos for the customer</h2>
                <p class="mt-1 text-xs text-sharm-mist">Drag up to {{ $maxPerDrop = 10 }} images per drop (max 30 total, up to 10 MB each). Images are compressed and stored privately on Cloudflare R2 — nothing is kept on this server.</p>

                {{-- Dropzone --}}
                <label
                    class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-sharm-deep/15 bg-sharm-sand/40 px-6 py-8 text-center transition hover:border-sharm-sea/40 hover:bg-sharm-sand/70"
                    :class="dragging ? 'border-sharm-sea/60 bg-sharm-sand' : ''"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop($event)"
                >
                    <svg class="h-8 w-8 text-sharm-mist" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    <span class="mt-2 text-sm font-medium text-sharm-deep">Drop images here or click to browse</span>
                    <span class="mt-0.5 text-[11px] text-sharm-mist">JPEG, PNG, WebP · max 10 MB each</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" x-ref="fileInput" @change="onSelect($event)">
                </label>

                {{-- Preview grid --}}
                <template x-if="queue.length">
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-sharm-deep"><span x-text="queue.length"></span> ready to upload</p>
                            <button type="button" class="text-[11px] text-sharm-mist hover:text-red-500" @click="clearQueue()" :disabled="uploading">Clear</button>
                        </div>
                        <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            <template x-for="(item, idx) in queue" :key="item.uid">
                                <div class="relative aspect-square overflow-hidden rounded-lg border border-sharm-deep/10 bg-sharm-sand">
                                    <img :src="item.preview" class="h-full w-full object-cover" :class="item.status === 'done' ? 'opacity-40' : ''" alt="">
                                    {{-- per-item overlay --}}
                                    <div x-show="item.status === 'uploading'" class="absolute inset-0 flex items-center justify-center bg-sharm-deep/40">
                                        <svg class="h-5 w-5 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    </div>
                                    <div x-show="item.status === 'done'" class="absolute inset-0 flex items-center justify-center">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                                    </div>
                                    <div x-show="item.status === 'error'" class="absolute inset-0 flex items-center justify-center bg-red-500/70" :title="item.error">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-red-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span>
                                    </div>
                                    <button
                                        type="button"
                                        x-show="!uploading && item.status === 'pending'"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-sharm-deep/60 text-white hover:bg-red-500"
                                        @click="removeFromQueue(idx)"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <button type="button" class="btn-primary" @click="uploadAll()" :disabled="uploading || !hasPending">
                                <span x-show="!uploading">Upload <span x-text="pendingCount"></span> photo(s)</span>
                                <span x-show="uploading" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Uploading <span x-text="uploadProgress"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Uploaded photos --}}
            <div class="admin-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-sharm-deep">Delivered photos (<span x-text="photos.length"></span>)</h2>
                </div>

                <template x-if="!photos.length">
                    <p class="mt-3 rounded-xl bg-sharm-sand/60 px-4 py-8 text-center text-xs text-sharm-mist">No photos uploaded yet.</p>
                </template>

                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5" x-show="photos.length">
                    <template x-for="photo in photos" :key="photo.id">
                        <div class="group relative aspect-square overflow-hidden rounded-lg border border-sharm-deep/10 bg-sharm-sand">
                            <img :src="photo.url" class="h-full w-full object-cover" loading="lazy" :alt="photo.original_name">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-sharm-deep/80 to-transparent p-1.5">
                                <p class="truncate text-[9px] text-white/90" x-text="photo.size_label"></p>
                            </div>
                            <button
                                type="button"
                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-sharm-deep/60 text-white opacity-0 transition group-hover:opacity-100 hover:bg-red-500"
                                @click="deletePhoto(photo)"
                                title="Delete"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Delivery code + WhatsApp --}}
            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-sharm-deep">Share with the customer</h2>
                <p class="mt-1 text-xs text-sharm-mist">Generate an access code, then send the ready-made message on WhatsApp. The customer opens the link and enters the booking number + code to view and download their photos.</p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" class="btn-primary" @click="generateCode()" :disabled="generating">
                        <span x-show="!generating" x-text="code ? 'Regenerate code' : 'Generate access code'"></span>
                        <span x-show="generating">Working…</span>
                    </button>

                    <template x-if="code">
                        <div class="flex items-center gap-2 rounded-lg border border-sharm-deep/10 bg-sharm-sand/60 px-3 py-2">
                            <span class="text-[11px] uppercase tracking-wide text-sharm-mist">Code</span>
                            <span class="font-mono text-sm font-semibold text-sharm-deep" x-text="code"></span>
                            <button type="button" class="text-sharm-mist hover:text-sharm-sea" @click="copyText(code, 'Code copied.')" title="Copy code">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m11.25 0v-.375c0-.621-.504-1.125-1.125-1.125H15.75m0 0a3 3 0 0 0-3-3H10.5" /></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="code">
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 p-3">
                            <pre class="whitespace-pre-wrap break-words font-sans text-xs text-sharm-deep" x-text="whatsappMessage"></pre>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a
                                :href="`https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->full_phone) }}?text=${encodeURIComponent(whatsappMessage)}`"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-600"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                Send on WhatsApp
                            </a>
                            <button type="button" class="rounded-lg border border-sharm-deep/15 px-4 py-2.5 text-sm text-sharm-deep transition hover:bg-sharm-sand" @click="copyText(whatsappMessage, 'Message copied.')">Copy message</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deliveryManager(config) {
    return {
        uploadUrl: config.uploadUrl,
        deliveryUrl: config.deliveryUrl,
        deleteBase: config.deleteBase,
        csrf: document.querySelector('meta[name="csrf-token"]').content,

        photos: config.photos || [],
        storage: config.storage,
        code: config.existingCode || '',
        whatsappMessage: config.existingMessage || '',

        queue: [],
        dragging: false,
        uploading: false,
        uploadIndex: 0,
        generating: false,

        // Limits
        maxPerDrop: 10,
        maxTotal: 30,
        maxBytes: 10 * 1024 * 1024,
        allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],

        get hasPending() { return this.queue.some(i => i.status === 'pending') },
        get pendingCount() { return this.queue.filter(i => i.status === 'pending').length },
        get uploadProgress() {
            const total = this._batchTotal || 0
            return total ? `${this.uploadIndex}/${total}` : ''
        },

        onSelect(e) {
            this.addFiles(Array.from(e.target.files || []))
            e.target.value = ''
        },
        onDrop(e) {
            this.dragging = false
            this.addFiles(Array.from(e.dataTransfer.files || []))
        },

        addFiles(files) {
            if (files.length > this.maxPerDrop) {
                window.showToast('warning', `You can add up to ${this.maxPerDrop} images per drop.`)
                files = files.slice(0, this.maxPerDrop)
            }
            for (const file of files) {
                if (this.queue.length >= this.maxTotal) {
                    window.showToast('warning', `Preview is limited to ${this.maxTotal} images. Upload these first, then add more.`)
                    break
                }
                if (!this.allowedTypes.includes(file.type)) {
                    window.showToast('error', `${file.name}: only JPEG, PNG, and WebP are allowed.`)
                    continue
                }
                if (file.size > this.maxBytes) {
                    window.showToast('error', `${file.name}: exceeds the 10 MB limit.`)
                    continue
                }
                this.queue.push({
                    uid: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                    file,
                    preview: URL.createObjectURL(file),
                    status: 'pending',
                    error: '',
                })
            }
        },

        removeFromQueue(idx) {
            const item = this.queue[idx]
            if (item?.preview) URL.revokeObjectURL(item.preview)
            this.queue.splice(idx, 1)
        },

        clearQueue() {
            this.queue.forEach(i => i.preview && URL.revokeObjectURL(i.preview))
            this.queue = []
        },

        async uploadAll() {
            if (this.uploading || !this.hasPending) return
            this.uploading = true
            const pending = this.queue.filter(i => i.status === 'pending')
            this._batchTotal = pending.length
            this.uploadIndex = 0

            for (const item of pending) {
                this.uploadIndex++
                item.status = 'uploading'
                try {
                    const fd = new FormData()
                    fd.append('photo', item.file)
                    const res = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                        body: fd,
                    })
                    const data = await res.json()
                    if (res.ok && data.success) {
                        item.status = 'done'
                        this.photos.push(data.photo)
                        this.storage = data.storage
                    } else {
                        item.status = 'error'
                        item.error = data.message || 'Upload failed.'
                    }
                } catch (e) {
                    item.status = 'error'
                    item.error = 'Network error.'
                }
            }

            this.uploading = false
            const failed = this.queue.filter(i => i.status === 'error').length
            const done = this.queue.filter(i => i.status === 'done').length
            if (done) window.showToast('success', `${done} photo(s) uploaded.`)
            if (failed) window.showToast('error', `${failed} photo(s) failed.`)

            // Keep only failed items so the admin can retry; drop the successful.
            this.queue.filter(i => i.status === 'done').forEach(i => i.preview && URL.revokeObjectURL(i.preview))
            this.queue = this.queue.filter(i => i.status === 'error')
            this.queue.forEach(i => { i.status = 'pending'; i.error = '' })
        },

        async deletePhoto(photo) {
            if (!confirm('Delete this photo? This removes it from storage permanently.')) return
            try {
                const res = await fetch(`${this.deleteBase}/${photo.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await res.json()
                if (res.ok && data.success) {
                    this.photos = this.photos.filter(p => p.id !== photo.id)
                    this.storage = data.storage
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        async generateCode() {
            this.generating = true
            try {
                const res = await fetch(this.deliveryUrl, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await res.json()
                if (res.ok && data.success) {
                    this.code = data.code
                    this.whatsappMessage = data.whatsapp_message
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            } finally {
                this.generating = false
            }
        },

        async copyText(text, msg) {
            try {
                await navigator.clipboard.writeText(text)
                window.showToast('success', msg)
            } catch (e) {
                window.showToast('error', 'Could not copy.')
            }
        },
    }
}
</script>
@endpush
