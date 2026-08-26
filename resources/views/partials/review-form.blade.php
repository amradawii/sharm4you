@php
    $countries = config('countries.list', []);
    $reviewUrl = current_locale() === 'en'
        ? route('review.store')
        : localized_url(current_locale(), 'review');
@endphp

<div
    x-data="reviewForm()"
    x-cloak
    class="review-form mx-auto mt-12 max-w-xl rounded-2xl border border-sharm-deep/8 bg-white/50 p-6 shadow-soft backdrop-blur-sm sm:p-8 lg:max-w-3xl"
>
    <div class="text-center">
        <div class="flex items-center justify-center gap-2.5">
            <svg class="h-5 w-5 shrink-0 text-sharm-desert" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2l2.9 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 7.1-1.01L12 2z"/>
            </svg>
            <h3 class="text-lg font-medium text-sharm-deep sm:text-xl">{{ __('site.testimonials.leave_review_title') }}</h3>
        </div>
        <p class="mx-auto mt-2 max-w-md text-sm text-sharm-mist">{{ __('site.testimonials.leave_review_subtitle') }}</p>
    </div>

    <div x-show="success" x-cloak class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center">
        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <h4 class="mt-3 font-display text-lg text-sharm-deep">{{ __('site.testimonials.success_title') }}</h4>
        <p class="mt-1 text-sm text-sharm-mist">{{ __('site.testimonials.success') }}</p>
    </div>

    <form x-show="!success" @submit.prevent="submit()" class="mt-6 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="review-name" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.testimonials.name') }}</label>
                <input
                    id="review-name"
                    type="text"
                    x-model="form.name"
                    class="w-full rounded-xl border bg-white/75 px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
                    :class="errors.name ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
                    placeholder="{{ __('site.testimonials.name_placeholder') }}"
                >
                <p class="mt-1 text-xs text-red-500" x-show="errors.name" x-text="errors.name?.[0]"></p>
            </div>

            <div>
                <label for="review-country" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.testimonials.country') }}</label>
                <select
                    id="review-country"
                    x-model="form.country_code"
                    class="w-full rounded-xl border bg-white/75 px-4 py-3 text-sm text-sharm-deep focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
                    :class="errors.country_code ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
                >
                    <option value="">{{ __('site.testimonials.country_placeholder') }}</option>
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-red-500" x-show="errors.country_code" x-text="errors.country_code?.[0]"></p>
            </div>
        </div>

        <div>
            <label for="review-comment" class="mb-1.5 block text-sm font-medium text-sharm-deep">{{ __('site.testimonials.comment') }}</label>
            <textarea
                id="review-comment"
                x-model="form.comment"
                rows="4"
                class="w-full rounded-xl border bg-white/75 px-4 py-3 text-sm text-sharm-deep placeholder:text-sharm-mist/60 focus:outline-none focus:ring-2 focus:ring-sharm-sea/20"
                :class="errors.comment ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-sharm-deep/10 focus:border-sharm-sea'"
                placeholder="{{ __('site.testimonials.comment_placeholder') }}"
            ></textarea>
            <p class="mt-1 text-xs text-red-500" x-show="errors.comment" x-text="errors.comment?.[0]"></p>
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
            <span x-show="!loading">{{ __('site.testimonials.submit') }}</span>
            <span x-show="loading" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('site.testimonials.submitting') }}
            </span>
        </button>
    </form>
</div>

@push('scripts')
<script>
    function reviewForm() {
        return {
            form: { name: '', country_code: '', comment: '' },
            loading: false,
            errors: {},
            success: false,
            successTimer: null,
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            submitUrl: @json($reviewUrl),
            messages: @json(__('site.testimonials.validation')),

            validate() {
                this.errors = {}
                let valid = true
                const name = this.form.name.trim()
                if (!name) {
                    this.errors.name = [this.messages.name_required]
                    valid = false
                }
                if (!this.form.country_code) {
                    this.errors.country_code = [this.messages.country_required]
                    valid = false
                }
                const comment = this.form.comment.trim()
                if (!comment) {
                    this.errors.comment = [this.messages.comment_required]
                    valid = false
                } else if (comment.length < 10) {
                    this.errors.comment = [this.messages.comment_min]
                    valid = false
                }
                return valid
            },

            resetForm() {
                this.form = { name: '', country_code: '', comment: '' }
                this.errors = {}
            },

            showSuccessMessage(message) {
                this.success = true
                window.showToast('success', message)

                if (this.successTimer) {
                    clearTimeout(this.successTimer)
                }

                this.successTimer = setTimeout(() => {
                    this.success = false
                    this.resetForm()
                    this.successTimer = null
                }, 5000)
            },

            async submit() {
                if (!this.validate()) return
                this.loading = true
                this.errors = {}
                try {
                    const res = await fetch(this.submitUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    })
                    const data = await res.json()
                    if (res.status === 429) {
                        window.showToast('error', 'Too many requests. Please wait a moment and try again.')
                        return
                    }
                    if (!res.ok) {
                        if (data.errors) this.errors = data.errors
                        window.toastFromResponse(data, 'Please check the form and try again.')
                        return
                    }
                    this.showSuccessMessage(data.message)
                } catch (e) {
                    window.showToast('error', 'Network error. Please try again.')
                } finally {
                    this.loading = false
                }
            },
        }
    }
</script>
@endpush
