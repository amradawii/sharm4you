@extends('admin.layouts.app')

@section('title', 'Reviews')
@section('page_title', 'Reviews')
@section('breadcrumb', 'Customer testimonials · approve, feature, edit')

@section('content')
<div
    x-data="reviewManager()"
    x-cloak
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-sharm-mist">
            <span class="font-semibold text-sharm-deep" x-text="reviews.length"></span>
            review(s)
            <span class="text-sharm-mist/70">· up to {{ \App\Models\Review::FEATURED_LIMIT }} featured on home Testimonials</span>
        </p>
        <div class="flex flex-wrap gap-2">
            <template x-for="f in statusFilters" :key="f.value ?? 'all'">
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="filter === f.value ? 'bg-sharm-sea text-white' : 'bg-sharm-sand text-sharm-mist hover:bg-sharm-sand-dark hover:text-sharm-deep'"
                    @click="filter = f.value"
                    x-text="f.label + (f.value ? ' (' + countByStatus(f.value) + ')' : ' (' + reviews.length + ')')"
                ></button>
            </template>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="button" class="btn-primary gap-2" @click="openCreateModal()">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Review
        </button>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <template x-for="review in filteredReviews" :key="review.id">
            <div class="admin-card flex flex-col p-5" :class="review.status === 'pending' ? 'ring-1 ring-amber-200' : ''">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xl leading-none" x-text="review.flag"></span>
                        <div>
                            <p class="font-semibold text-sharm-deep" x-text="review.name"></p>
                            <p class="text-xs text-sharm-mist" x-text="review.country"></p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider" :class="review.status_color" x-text="review.status_label"></span>
                </div>

                <p class="mt-3 flex-1 text-sm leading-relaxed text-sharm-mist" x-text="'&ldquo;' + review.comment + '&rdquo;'"></p>

                <div class="mt-4 flex items-center justify-between border-t border-sharm-deep/8 pt-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition"
                        :class="review.is_featured ? 'bg-sharm-desert/15 text-sharm-desert hover:bg-sharm-desert/25' : 'text-sharm-mist hover:bg-sharm-sand'"
                        @click="toggleFeatured(review)"
                        :title="review.is_featured ? 'Unfeature' : 'Feature on home'"
                    >
                        <svg class="h-4 w-4" :fill="review.is_featured ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                        <span x-text="review.is_featured ? 'Featured' : 'Feature'"></span>
                    </button>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-emerald-50 hover:text-emerald-600"
                            title="Approve"
                            x-show="review.status !== 'approved'"
                            @click="setStatus(review, 'approved')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-red-50 hover:text-red-500"
                            title="Reject"
                            x-show="review.status !== 'rejected'"
                            @click="setStatus(review, 'rejected')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
                            title="Edit"
                            @click="openEditModal(review)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-red-50 hover:text-red-500"
                            title="Delete"
                            @click="confirmDelete(review)"
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

    <div x-show="filteredReviews.length === 0" class="mt-12 text-center">
        <p class="text-sm text-sharm-mist">No reviews match this filter.</p>
    </div>

    {{-- Create / Edit Modal --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4"
        @click.self="closeModal()"
        @keydown.escape.window="closeModal()"
    >
        <div class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl">
            <div class="shrink-0">
                <h2 class="text-lg font-semibold text-sharm-deep" x-text="editingReview ? 'Edit Review' : 'Add Review'"></h2>
                <p class="mt-1 text-xs text-sharm-mist">Reviews keep their original language and appear verbatim on the home Testimonials.</p>
            </div>

            <form @submit.prevent="submitModal()" class="mt-5 flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1">
                    <div>
                        <label class="admin-label">Name <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name" x-text="errors.name?.[0]"></p>
                    </div>

                    <div>
                        <label class="admin-label">Country <span class="text-red-400">*</span></label>
                        <select x-model="form.country_code" class="admin-input" required>
                            <option value="">Select country</option>
                            @foreach ($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.country_code" x-text="errors.country_code?.[0]"></p>
                    </div>

                    <div>
                        <label class="admin-label">Comment <span class="text-red-400">*</span></label>
                        <textarea x-model="form.comment" class="admin-input min-h-[120px]" rows="5" required></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.comment" x-text="errors.comment?.[0]"></p>
                    </div>

                    <div x-show="!editingReview">
                        <label class="admin-label">Status <span class="text-red-400">*</span></label>
                        <select x-model="form.status" class="admin-input">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-sharm-deep">
                        <input type="checkbox" x-model="form.is_featured" class="rounded border-sharm-deep/20">
                        Feature on home Testimonials
                    </label>
                </div>

                <div class="mt-4 flex shrink-0 items-center justify-end gap-3 border-t border-sharm-deep/8 pt-4">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand" @click="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="submitting">
                        <span x-text="editingReview ? 'Update' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div
        x-show="deleteTarget"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4"
        @click.self="deleteTarget = null"
    >
        <div class="w-full max-w-sm rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-sharm-deep">Delete Review</h3>
            <p class="mt-1 text-sm text-sharm-mist">Permanently delete the review from <strong x-text="deleteTarget?.name"></strong>?</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" class="rounded-lg px-4 py-2.5 text-sm text-sharm-mist hover:bg-sharm-sand" @click="deleteTarget = null">Cancel</button>
                <button type="button" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700" @click="deleteReview()" :disabled="submitting">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function reviewManager() {
    return {
        reviews: @json($reviewsJson),
        countries: @json($countries),
        filter: null,
        statusFilters: [
            { value: null, label: 'All' },
            { value: 'pending', label: 'Pending' },
            { value: 'approved', label: 'Approved' },
            { value: 'rejected', label: 'Rejected' },
        ],
        modalOpen: false,
        editingReview: null,
        deleteTarget: null,
        submitting: false,
        errors: {},
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        form: {
            name: '',
            country_code: '',
            comment: '',
            status: 'approved',
            is_featured: false,
        },

        get filteredReviews() {
            if (!this.filter) return this.reviews
            return this.reviews.filter(r => r.status === this.filter)
        },

        countByStatus(status) {
            return this.reviews.filter(r => r.status === status).length
        },

        emptyForm() {
            return { name: '', country_code: '', comment: '', status: 'approved', is_featured: false }
        },

        openCreateModal() {
            this.editingReview = null
            this.form = this.emptyForm()
            this.errors = {}
            this.modalOpen = true
        },

        openEditModal(review) {
            this.editingReview = review
            this.form = {
                name: review.name,
                country_code: review.country_code,
                comment: review.comment,
                status: review.status,
                is_featured: review.is_featured,
            }
            this.errors = {}
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editingReview = null
            this.errors = {}
        },

        upsertReview(review) {
            const idx = this.reviews.findIndex(r => r.id === review.id)
            if (idx !== -1) {
                this.reviews[idx] = review
            } else {
                this.reviews.unshift(review)
            }
        },

        async submitModal() {
            this.submitting = true
            this.errors = {}

            const url = this.editingReview
                ? `{{ url('admin/reviews') }}/${this.editingReview.id}`
                : `{{ route('admin.reviews.store') }}`
            const method = this.editingReview ? 'PUT' : 'POST'

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
                    if (data.errors) this.errors = data.errors
                    window.toastFromResponse(data, 'Failed to save review.')
                    return
                }

                this.upsertReview(data.review)
                window.showToast('success', data.message)
                this.closeModal()
            } catch (e) {
                window.showToast('error', 'Network error. Please try again.')
            } finally {
                this.submitting = false
            }
        },

        async setStatus(review, status) {
            try {
                const res = await fetch(`{{ url('admin/reviews') }}/${review.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status }),
                })
                const data = await res.json()
                if (res.ok) {
                    this.upsertReview(data.review)
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        async toggleFeatured(review) {
            try {
                const res = await fetch(`{{ url('admin/reviews') }}/${review.id}/featured`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await res.json()
                if (res.ok) {
                    this.upsertReview(data.review)
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        confirmDelete(review) {
            this.deleteTarget = review
        },

        async deleteReview() {
            if (!this.deleteTarget) return
            this.submitting = true
            const id = this.deleteTarget.id
            try {
                const res = await fetch(`{{ url('admin/reviews') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await res.json()
                if (res.ok) {
                    this.reviews = this.reviews.filter(r => r.id !== id)
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
