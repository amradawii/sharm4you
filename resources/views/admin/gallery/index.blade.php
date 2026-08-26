@extends('admin.layouts.app')

@section('title', 'Gallery')
@section('page_title', 'Gallery')
@section('breadcrumb', 'Manage albums and photos')

@section('content')
<div
    x-data="albumManager()"
    x-cloak
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-sharm-mist">
                <span x-text="albums.length"></span> album(s)
            </p>
        </div>
        <button
            type="button"
            class="btn-primary gap-2"
            @click="openCreateModal()"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Create Album
        </button>
    </div>

    {{-- Albums Grid --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="album in albums" :key="album.id">
            <div class="admin-card group relative overflow-hidden">
                {{-- Cover image --}}
                <a :href="album.edit_url" class="block aspect-[16/10] overflow-hidden bg-sharm-sand-dark">
                    <template x-if="album.cover_url">
                        <img
                            :src="album.cover_url"
                            :alt="album.name_en"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        >
                    </template>
                    <template x-if="!album.cover_url">
                        <div class="flex h-full items-center justify-center">
                            <svg class="h-12 w-12 text-sharm-mist/30" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                    </template>
                </a>

                {{-- Info --}}
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-sharm-deep" x-text="album.name_en"></h3>
                            <p class="mt-0.5 truncate text-[11px] text-sharm-mist" x-text="album.name_ru + ' · ' + album.name_it"></p>
                        </div>
                        <span
                            class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                            :class="album.is_active
                                ? 'bg-emerald-50 text-emerald-600'
                                : 'bg-red-50 text-red-500'"
                            x-text="album.is_active ? 'Active' : 'Hidden'"
                        ></span>
                    </div>

                    <p class="mt-2 text-xs text-sharm-mist">
                        <span x-text="album.photos_count"></span> photo(s)
                    </p>

                    {{-- Actions --}}
                    <div class="mt-3 flex items-center gap-2 border-t border-sharm-deep/5 pt-3">
                        <a
                            :href="album.edit_url"
                            class="flex-1 rounded-lg bg-sharm-sand/80 px-3 py-2 text-center text-xs font-medium text-sharm-deep transition hover:bg-sharm-sea hover:text-white"
                        >
                            Manage Photos
                        </a>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
                            title="Edit Album"
                            @click="openEditModal(album)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand"
                            :class="album.is_active ? 'hover:text-amber-600' : 'hover:text-emerald-600'"
                            :title="album.is_active ? 'Hide album' : 'Show album'"
                            @click="toggleActive(album)"
                        >
                            <svg x-show="album.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg x-show="!album.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-red-50 hover:text-red-500"
                            title="Delete Album"
                            @click="confirmDelete(album)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <div x-show="albums.length === 0" class="mt-12 text-center">
        <svg class="mx-auto h-16 w-16 text-sharm-mist/30" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
        </svg>
        <p class="mt-4 text-sm text-sharm-mist">No albums yet. Create your first album to start building your gallery.</p>
    </div>

    {{-- Create / Edit Modal --}}
    <div
        x-show="modalOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="closeModal()"
        @keydown.escape.window="closeModal()"
    >
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-md rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl"
        >
            <h2 class="text-lg font-semibold text-sharm-deep" x-text="editingAlbum ? 'Edit Album' : 'Create Album'"></h2>

            <form @submit.prevent="submitModal()" class="mt-5 space-y-4">
                <div>
                    <label class="admin-label">Name — English <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        x-model="form.name_en"
                        class="admin-input"
                        placeholder="e.g. Moses Mountain"
                        required
                    >
                    <p class="mt-1 text-xs text-red-500" x-show="errors.name_en" x-text="errors.name_en?.[0]"></p>
                </div>
                <div>
                    <label class="admin-label">Name — Русский <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        x-model="form.name_ru"
                        class="admin-input"
                        placeholder="e.g. Гора Моисея"
                        required
                    >
                    <p class="mt-1 text-xs text-red-500" x-show="errors.name_ru" x-text="errors.name_ru?.[0]"></p>
                </div>
                <div>
                    <label class="admin-label">Name — Italiano <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        x-model="form.name_it"
                        class="admin-input"
                        placeholder="e.g. Monte Mosè"
                        required
                    >
                    <p class="mt-1 text-xs text-red-500" x-show="errors.name_it" x-text="errors.name_it?.[0]"></p>
                </div>
                <div>
                    <label class="admin-label">Name — Deutsch <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        x-model="form.name_de"
                        class="admin-input"
                        placeholder="e.g. Moses Berg"
                        required
                    >
                    <p class="mt-1 text-xs text-red-500" x-show="errors.name_de" x-text="errors.name_de?.[0]"></p>
                </div>
                <div>
                    <label class="admin-label">Name — Українська <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        x-model="form.name_uk"
                        class="admin-input"
                        placeholder="e.g. Гора Мойсея"
                        required
                    >
                    <p class="mt-1 text-xs text-red-500" x-show="errors.name_uk" x-text="errors.name_uk?.[0]"></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
                        @click="closeModal()"
                    >Cancel</button>
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="submitting"
                    >
                        <svg x-show="submitting" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="editingAlbum ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div
        x-show="deleteTarget"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="deleteTarget = null"
        @keydown.escape.window="deleteTarget = null"
    >
        <div
            x-show="deleteTarget"
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
            <h3 class="mt-3 text-sm font-semibold text-sharm-deep">Delete Album</h3>
            <p class="mt-1 text-sm text-sharm-mist">
                This will permanently delete <strong x-text="deleteTarget?.name_en"></strong> and all its photos. This cannot be undone.
            </p>
            <div class="mt-5 flex items-center justify-end gap-3">
                <button
                    type="button"
                    class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand"
                    @click="deleteTarget = null"
                >Cancel</button>
                <button
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700"
                    @click="deleteAlbum()"
                    :disabled="submitting"
                >Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function albumManager() {
    return {
        albums: @json($albumsJson),

        modalOpen: false,
        editingAlbum: null,
        deleteTarget: null,
        submitting: false,
        form: { name_en: '', name_ru: '', name_it: '', name_de: '', name_uk: '' },
        errors: {},

        csrf: document.querySelector('meta[name="csrf-token"]').content,

        openCreateModal() {
            this.editingAlbum = null
            this.form = { name_en: '', name_ru: '', name_it: '', name_de: '', name_uk: '' }
            this.errors = {}
            this.modalOpen = true
        },

        openEditModal(album) {
            this.editingAlbum = album
            this.form = {
                name_en: album.name_en,
                name_ru: album.name_ru,
                name_it: album.name_it,
                name_de: album.name_de,
                name_uk: album.name_uk,
            }
            this.errors = {}
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editingAlbum = null
            this.errors = {}
        },

        async submitModal() {
            this.submitting = true
            this.errors = {}

            const url = this.editingAlbum
                ? `{{ url('admin/gallery') }}/${this.editingAlbum.id}`
                : `{{ route('admin.gallery.store') }}`

            const method = this.editingAlbum ? 'PUT' : 'POST'

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                })

                const data = await res.json()

                if (!res.ok) {
                    if (data.errors) {
                        this.errors = data.errors
                    }
                    window.toastFromResponse(data, 'Failed to save album.')
                    return
                }

                if (this.editingAlbum) {
                    // Update in list
                    const idx = this.albums.findIndex(a => a.id === this.editingAlbum.id)
                    if (idx !== -1) {
                        this.albums[idx].name_en = data.album.name_en
                        this.albums[idx].name_ru = data.album.name_ru
                        this.albums[idx].name_it = data.album.name_it
                        this.albums[idx].name_de = data.album.name_de
                        this.albums[idx].name_uk = data.album.name_uk
                        this.albums[idx].slug = data.album.slug
                    }
                } else {
                    // Prepend to list
                    this.albums.unshift(data.album)
                }

                window.showToast('success', data.message)
                this.closeModal()
            } catch (e) {
                window.showToast('error', 'Network error. Please try again.')
            } finally {
                this.submitting = false
            }
        },

        async toggleActive(album) {
            try {
                const res = await fetch(`{{ url('admin/gallery') }}/${album.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                })

                const data = await res.json()

                if (res.ok) {
                    album.is_active = data.is_active
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        confirmDelete(album) {
            this.deleteTarget = album
        },

        async deleteAlbum() {
            if (!this.deleteTarget) return
            this.submitting = true

            try {
                const res = await fetch(`{{ url('admin/gallery') }}/${this.deleteTarget.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                })

                const data = await res.json()

                if (res.ok) {
                    this.albums = this.albums.filter(a => a.id !== this.deleteTarget.id)
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            } finally {
                this.deleteTarget = null
                this.submitting = false
            }
        },
    }
}
</script>
@endpush
