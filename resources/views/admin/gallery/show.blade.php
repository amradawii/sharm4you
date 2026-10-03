@extends('admin.layouts.app')

@section('title', $album->name_en . ' — Gallery')
@section('page_title', $album->name_en)
@section('breadcrumb')
    <a href="{{ route('admin.gallery.index') }}" class="text-sharm-sea hover:underline">Gallery</a>
    <span class="mx-1.5">›</span>
    {{ $album->name_en }}
@endsection

@section('content')
<div
    x-data="photoManager()"
    x-cloak
>
    {{-- Stats bar --}}
    <div class="flex flex-wrap items-center gap-3 text-xs text-sharm-mist">
        <span class="rounded-md bg-sharm-sand px-2.5 py-1 font-medium">
            <span x-text="photos.length"></span> photo(s)
        </span>
        <span class="rounded-md bg-sharm-desert/10 px-2.5 py-1 font-medium text-sharm-desert">
            ⭐ <span x-text="featuredCount"></span> / 12 featured
        </span>
    </div>

    {{-- Upload Zone --}}
    <div
        class="admin-upload-zone mt-5"
        :class="dragOver && 'admin-upload-zone-active'"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
    >
        <input
            type="file"
            x-ref="fileInput"
            class="hidden"
            accept=".jpeg,.jpg,.png,.webp,.avif"
            multiple
            @change="handleFileSelect($event)"
        >

        <div class="flex flex-col items-center gap-2 py-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sharm-sea/10">
                <svg class="h-6 w-6 text-sharm-sea" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
            </div>
            <p class="text-sm font-medium text-sharm-deep">Drop images here or click to browse</p>
            <p class="text-xs text-sharm-mist">JPEG, PNG, WebP, AVIF — Max 3 MB each — Up to 5 at a time</p>
        </div>
    </div>

    {{-- Upload progress --}}
    <template x-if="uploadQueue.length > 0">
        <div class="mt-4 space-y-2">
            <template x-for="item in uploadQueue" :key="item.id">
                <div class="flex items-center gap-3 rounded-lg border border-sharm-deep/10 bg-white px-4 py-2.5">
                    <div class="h-8 w-8 shrink-0 overflow-hidden rounded bg-sharm-sand-dark">
                        <img :src="item.preview" class="h-full w-full object-cover" alt="">
                    </div>
                    <p class="min-w-0 flex-1 truncate text-xs text-sharm-deep" x-text="item.name"></p>
                    <div x-show="item.status === 'uploading'" class="shrink-0">
                        <svg class="h-4 w-4 animate-spin text-sharm-sea" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <div x-show="item.status === 'done'" class="shrink-0">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <div x-show="item.status === 'error'" class="shrink-0">
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Photo Grid --}}
    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <template x-for="photo in photos" :key="photo.id">
            <div class="admin-photo-card group">
                <div class="relative aspect-square overflow-hidden rounded-t-xl bg-sharm-sand-dark">
                    <img
                        :src="photo.url"
                        :alt="photo.alt_en || photo.original_name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    >

                    {{-- Overlay actions --}}
                    <div class="absolute inset-0 flex items-center justify-center gap-2 bg-sharm-deep/0 opacity-0 transition duration-200 group-hover:bg-sharm-deep/40 group-hover:opacity-100">
                        <button
                            type="button"
                            class="rounded-lg bg-white/90 p-2 text-sharm-deep shadow-sm transition hover:bg-white"
                            title="Edit alt text"
                            @click="openAltModal(photo)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-red-500/90 p-2 text-white shadow-sm transition hover:bg-red-600"
                            title="Delete photo"
                            @click="confirmDeletePhoto(photo)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>

                    {{-- Featured badge --}}
                    <button
                        type="button"
                        class="absolute left-2 top-2 rounded-lg p-1.5 transition"
                        :class="photo.is_featured
                            ? 'bg-sharm-desert text-white shadow-sm'
                            : 'bg-white/80 text-sharm-mist hover:bg-sharm-desert/20 hover:text-sharm-desert'"
                        :title="photo.is_featured ? 'Remove from featured' : 'Add to featured'"
                        @click="toggleFeatured(photo)"
                    >
                        <svg class="h-4 w-4" :fill="photo.is_featured ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </button>
                </div>

                {{-- Name --}}
                <div class="rounded-b-xl border-t border-sharm-deep/5 bg-white px-3 py-2">
                    <p class="truncate text-[11px] text-sharm-mist" x-text="photo.original_name"></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <div x-show="photos.length === 0 && uploadQueue.length === 0" class="mt-12 text-center">
        <svg class="mx-auto h-16 w-16 text-sharm-mist/30" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <p class="mt-4 text-sm text-sharm-mist">No photos yet. Drop images above to start uploading.</p>
    </div>

    {{-- Alt Text Edit Modal --}}
    <div
        x-show="altModalOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="altModalOpen = false"
        @keydown.escape.window="altModalOpen = false"
    >
        <div
            x-show="altModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-md rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl"
        >
            <h2 class="text-lg font-semibold text-sharm-deep">Edit Alt Text</h2>
            <p class="mt-1 text-xs text-sharm-mist" x-text="altForm.original_name"></p>

            <form @submit.prevent="submitAlt()" class="mt-5 space-y-4">
                <div>
                    <label class="admin-label">Alt — English</label>
                    <input type="text" x-model="altForm.alt_en" class="admin-input" placeholder="Describe the image in English">
                </div>
                <div>
                    <label class="admin-label">Alt — Русский</label>
                    <input type="text" x-model="altForm.alt_ru" class="admin-input" placeholder="Описание на русском">
                </div>
                <div>
                    <label class="admin-label">Alt — Italiano</label>
                    <input type="text" x-model="altForm.alt_it" class="admin-input" placeholder="Descrizione in italiano">
                </div>
                <div>
                    <label class="admin-label">Alt — Română</label>
                    <input type="text" x-model="altForm.alt_ro" class="admin-input" placeholder="Descriere în română">
                </div>
                <div>
                    <label class="admin-label">Alt — Български</label>
                    <input type="text" x-model="altForm.alt_bg" class="admin-input" placeholder="Описание на български">
                </div>
                <div>
                    <label class="admin-label">Alt — Türkçe</label>
                    <input type="text" x-model="altForm.alt_tr" class="admin-input" placeholder="Türkçe açıklama">
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand" @click="altModalOpen = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="submitting">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Photo Confirmation --}}
    <div
        x-show="deletePhotoTarget"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="deletePhotoTarget = null"
        @keydown.escape.window="deletePhotoTarget = null"
    >
        <div
            x-show="deletePhotoTarget"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-sm rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50">
                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-sharm-deep">Delete Photo</h3>
            <p class="mt-1 text-sm text-sharm-mist">This will permanently delete this photo. This cannot be undone.</p>
            <div class="mt-5 flex items-center justify-end gap-3">
                <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand" @click="deletePhotoTarget = null">Cancel</button>
                <button type="button" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700" @click="deletePhoto()" :disabled="submitting">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function photoManager() {
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif']
    const ALLOWED_EXTS  = ['.jpg', '.jpeg', '.png', '.webp', '.avif']
    const MAX_SIZE      = 3 * 1024 * 1024 // 3 MB
    const MAX_FILES     = 5

    return {
        photos: @json($photosJson),
        featuredCount: {{ $featuredCount }},

        dragOver: false,
        uploadQueue: [],
        submitting: false,

        altModalOpen: false,
        altForm: { id: null, original_name: '', alt_en: '', alt_ru: '', alt_it: '', alt_ro: '', alt_bg: '', alt_tr: '' },
        deletePhotoTarget: null,

        csrf: document.querySelector('meta[name="csrf-token"]').content,

        // ── File Validation (Alpine frontend) ──
        validateFiles(files) {
            const fileArray = Array.from(files)
            const errors = []

            if (fileArray.length > MAX_FILES) {
                errors.push(`Maximum ${MAX_FILES} files per upload. You selected ${fileArray.length}.`)
            }

            fileArray.forEach(file => {
                const ext = '.' + file.name.split('.').pop().toLowerCase()
                if (!ALLOWED_EXTS.includes(ext)) {
                    errors.push(`"${file.name}" — invalid type. Only JPEG, PNG, WebP, AVIF allowed.`)
                }
                if (file.size > MAX_SIZE) {
                    errors.push(`"${file.name}" — exceeds 3 MB limit.`)
                }
            })

            if (errors.length) {
                errors.forEach(e => window.showToast('error', e))
                return null
            }

            return fileArray.slice(0, MAX_FILES)
        },

        handleDrop(event) {
            this.dragOver = false
            const files = event.dataTransfer.files
            this.processFiles(files)
        },

        handleFileSelect(event) {
            const files = event.target.files
            this.processFiles(files)
            event.target.value = '' // reset so same file can be re-selected
        },

        async processFiles(files) {
            const validated = this.validateFiles(files)
            if (!validated) return

            // Build upload queue with previews
            const queueItems = validated.map(file => ({
                id: Date.now() + Math.random(),
                name: file.name,
                preview: URL.createObjectURL(file),
                status: 'uploading',
                file,
            }))

            this.uploadQueue = queueItems

            // Build FormData
            const formData = new FormData()
            validated.forEach(file => formData.append('photos[]', file))

            try {
                const res = await fetch(`{{ route('admin.gallery.photos.upload', $album) }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: formData,
                })

                const data = await res.json()

                if (!res.ok) {
                    queueItems.forEach(q => q.status = 'error')
                    window.toastFromResponse(data, 'Upload failed.')
                    return
                }

                // Mark done and add to grid
                queueItems.forEach(q => q.status = 'done')
                data.photos.forEach(p => this.photos.push(p))
                window.showToast('success', data.message)

                // Clear queue after a moment
                setTimeout(() => { this.uploadQueue = [] }, 1500)
            } catch (e) {
                queueItems.forEach(q => q.status = 'error')
                window.showToast('error', 'Network error during upload.')
            }
        },

        // ── Featured Toggle ──
        async toggleFeatured(photo) {
            try {
                const res = await fetch(`{{ url('admin/gallery/photos') }}/${photo.id}/featured`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                })

                const data = await res.json()

                if (res.ok) {
                    photo.is_featured = data.is_featured
                    this.featuredCount = data.featured_count
                    window.showToast('success', data.message)
                } else {
                    window.showToast('warning', data.message || 'Cannot feature this photo.')
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        // ── Alt Text ──
        openAltModal(photo) {
            this.altForm = {
                id: photo.id,
                original_name: photo.original_name,
                alt_en: photo.alt_en || '',
                alt_ru: photo.alt_ru || '',
                alt_it: photo.alt_it || '',
                alt_ro: photo.alt_ro || '',
                alt_bg: photo.alt_bg || '',
                alt_tr: photo.alt_tr || '',
            }
            this.altModalOpen = true
        },

        async submitAlt() {
            this.submitting = true

            try {
                const res = await fetch(`{{ url('admin/gallery/photos') }}/${this.altForm.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        alt_en: this.altForm.alt_en,
                        alt_ru: this.altForm.alt_ru,
                        alt_it: this.altForm.alt_it,
                        alt_ro: this.altForm.alt_ro,
                        alt_bg: this.altForm.alt_bg,
                        alt_tr: this.altForm.alt_tr,
                    }),
                })

                const data = await res.json()

                if (res.ok) {
                    // Update local
                    const p = this.photos.find(p => p.id === this.altForm.id)
                    if (p) {
                        p.alt_en = this.altForm.alt_en
                        p.alt_ru = this.altForm.alt_ru
                        p.alt_it = this.altForm.alt_it
                        p.alt_ro = this.altForm.alt_ro
                        p.alt_bg = this.altForm.alt_bg
                        p.alt_tr = this.altForm.alt_tr
                    }
                    window.showToast('success', data.message)
                    this.altModalOpen = false
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            } finally {
                this.submitting = false
            }
        },

        // ── Delete Photo ──
        confirmDeletePhoto(photo) {
            this.deletePhotoTarget = photo
        },

        async deletePhoto() {
            if (!this.deletePhotoTarget) return
            this.submitting = true

            try {
                const res = await fetch(`{{ url('admin/gallery/photos') }}/${this.deletePhotoTarget.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                })

                const data = await res.json()

                if (res.ok) {
                    // If it was featured, decrement count
                    if (this.deletePhotoTarget.is_featured) {
                        this.featuredCount = Math.max(0, this.featuredCount - 1)
                    }
                    this.photos = this.photos.filter(p => p.id !== this.deletePhotoTarget.id)
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            } finally {
                this.deletePhotoTarget = null
                this.submitting = false
            }
        },
    }
}
</script>
@endpush
