@extends('admin.layouts.app')

@section('title', 'Packages')
@section('page_title', 'Packages')
@section('breadcrumb', 'Session tiers · EN / RU / IT / RO / BG / TR')

@section('content')
<div
    x-data="packageManager()"
    x-cloak
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-sharm-mist">
            <span class="font-semibold text-sharm-deep" x-text="tiers.length"></span>
            session tier(s)
            <span class="text-sharm-mist/70">· each shown in all locales when active</span>
        </p>
        <button type="button" class="btn-primary gap-2" @click="openCreateModal()">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Package
        </button>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-3">
        <template x-for="tier in tiers" :key="tier.hours">
            <div
                class="admin-card flex flex-col overflow-hidden p-0 transition"
                :class="tier.is_active ? '' : 'opacity-60'"
            >
                <div class="relative aspect-[4/3] w-full bg-sharm-sand/60">
                    <template x-if="tier.image_url">
                        <img :src="tier.image_url" :alt="tier.name_en" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!tier.image_url">
                        <div class="flex h-full w-full items-center justify-center text-xs text-sharm-mist">No photo</div>
                    </template>
                    <span
                        x-show="tier.is_discounted"
                        class="absolute right-3 top-3 rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white shadow"
                        x-text="'-' + tier.discount_percent + '%'"
                    ></span>
                </div>

                <div class="flex flex-1 flex-col p-5">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-display text-2xl text-sharm-deep" x-text="formatHours(tier.hours)"></span>
                    <div class="flex shrink-0 items-center gap-0.5">
                        <button
                            type="button"
                            class="rounded-lg p-2 transition"
                            :class="tier.is_featured
                                ? 'bg-sharm-desert/15 text-sharm-desert hover:bg-sharm-desert/25'
                                : 'text-sharm-mist hover:bg-sharm-sand'"
                            :title="tier.is_featured ? 'Remove Popular badge on all locales' : 'Mark as Popular on all locales'"
                            @click="toggleFeatured(tier)"
                        >
                            <svg class="h-4 w-4" :fill="tier.is_featured ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand"
                            :title="tier.is_active ? 'Hide on all locales' : 'Show on all locales'"
                            @click="toggleTier(tier)"
                        >
                            <svg x-show="tier.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="!tier.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-red-50 hover:text-red-500"
                            title="Delete from all locales"
                            @click="confirmDelete(tier)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-4 space-y-1.5 text-sm">
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">EN</span> <span class="text-sharm-deep" x-text="tier.name_en"></span></p>
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">RU</span> <span class="text-sharm-deep" x-text="tier.name_ru"></span></p>
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">IT</span> <span class="text-sharm-deep" x-text="tier.name_it"></span></p>
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">RO</span> <span class="text-sharm-deep" x-text="tier.name_ro"></span></p>
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">BG</span> <span class="text-sharm-deep" x-text="tier.name_bg"></span></p>
                    <p class="truncate"><span class="text-[10px] font-semibold uppercase tracking-wider text-sharm-mist">TR</span> <span class="text-sharm-deep" x-text="tier.name_tr"></span></p>
                </div>

                <div x-show="tier.is_discounted" class="mt-4 flex flex-wrap gap-x-3 gap-y-1 text-xs text-sharm-mist line-through">
                    <span x-text="tier.original_prices.USD"></span>
                    <span class="text-sharm-mist/40">·</span>
                    <span x-text="tier.original_prices.EUR"></span>
                    <span class="text-sharm-mist/40">·</span>
                    <span x-text="tier.original_prices.RUB"></span>
                </div>
                <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm font-medium text-sharm-desert" :class="tier.is_discounted ? 'mt-1' : 'mt-4'">
                    <span x-text="tier.prices.USD"></span>
                    <span class="text-sharm-mist/40">·</span>
                    <span x-text="tier.prices.EUR"></span>
                    <span class="text-sharm-mist/40">·</span>
                    <span x-text="tier.prices.RUB"></span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1">
                    <template x-for="slot in (tier.available_times || [])" :key="slot">
                        <span class="rounded-md bg-sharm-sand/70 px-2 py-0.5 text-[11px] font-medium text-sharm-deep" x-text="formatSlot(slot)"></span>
                    </template>
                </div>

                <button
                    type="button"
                    class="mt-5 w-full rounded-lg bg-sharm-sand/80 py-2.5 text-sm font-medium text-sharm-deep transition hover:bg-sharm-sea hover:text-white"
                    @click="openEditModal(tier)"
                >
                    Edit
                </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="tiers.length === 0" class="mt-12 text-center">
        <p class="text-sm text-sharm-mist">No packages yet. Add your first session tier to get started.</p>
    </div>

    {{-- Create / Edit Modal --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4"
        @click.self="closeModal()"
        @keydown.escape.window="closeModal()"
    >
        <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl">
            <div class="shrink-0">
                <h2 class="text-lg font-semibold text-sharm-deep" x-text="editingTier ? 'Edit Package' : 'Add Package'"></h2>
                <p class="mt-1 text-xs text-sharm-mist">Names and descriptions per language. Prices stay per currency; discount, Active, and Popular apply to all locales together.</p>
            </div>

            <form @submit.prevent="submitModal()" novalidate class="mt-5 flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1">
                <div class="flex flex-wrap gap-1 rounded-lg bg-sharm-sand/60 p-1">
                    <template x-for="tab in langTabs" :key="tab.id">
                        <button
                            type="button"
                            class="min-w-[5.5rem] flex-1 rounded-md px-2 py-2 text-xs font-semibold transition"
                            :class="langTab === tab.id ? 'bg-white text-sharm-deep shadow-sm' : 'text-sharm-mist hover:text-sharm-deep'"
                            @click="langTab = tab.id"
                            x-text="tab.label"
                        ></button>
                    </template>
                </div>

                <div x-show="langTab === 'en'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — English <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_en" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_en" x-text="errors.name_en?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — English <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_en" class="admin-input min-h-[72px]" rows="2" required></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.description_en" x-text="errors.description_en?.[0]"></p>
                    </div>
                </div>

                <div x-show="langTab === 'ru'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — Русский <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_ru" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_ru" x-text="errors.name_ru?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — Русский <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_ru" class="admin-input min-h-[72px]" rows="2" required></textarea>
                    </div>
                </div>

                <div x-show="langTab === 'it'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — Italiano <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_it" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_it" x-text="errors.name_it?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — Italiano <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_it" class="admin-input min-h-[72px]" rows="2" required></textarea>
                    </div>
                </div>

                <div x-show="langTab === 'ro'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — Română <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_ro" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_ro" x-text="errors.name_ro?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — Română <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_ro" class="admin-input min-h-[72px]" rows="2" required></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.description_ro" x-text="errors.description_ro?.[0]"></p>
                    </div>
                </div>

                <div x-show="langTab === 'bg'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — Български <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_bg" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_bg" x-text="errors.name_bg?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — Български <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_bg" class="admin-input min-h-[72px]" rows="2" required></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.description_bg" x-text="errors.description_bg?.[0]"></p>
                    </div>
                </div>

                <div x-show="langTab === 'tr'" class="space-y-3">
                    <div>
                        <label class="admin-label">Name — Türkçe <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name_tr" class="admin-input" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name_tr" x-text="errors.name_tr?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Description — Türkçe <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description_tr" class="admin-input min-h-[72px]" rows="2" required></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.description_tr" x-text="errors.description_tr?.[0]"></p>
                    </div>
                </div>

                <hr class="border-sharm-deep/8">

                <div class="flex flex-col items-center text-center">
                    <label class="admin-label">Photo</label>
                    <div class="mt-3 flex aspect-[4/3] w-36 items-center justify-center overflow-hidden rounded-xl bg-sharm-sand/60">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!imagePreview">
                            <span class="text-xs text-sharm-mist">No photo</span>
                        </template>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <label class="btn-primary cursor-pointer gap-2 text-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            Upload
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp,image/avif" @change="pickImage($event)">
                        </label>
                        <button
                            type="button"
                            x-show="imagePreview"
                            class="rounded-lg border border-sharm-deep/15 px-3 py-2 text-xs font-medium text-sharm-mist transition hover:bg-sharm-sand"
                            @click="clearImage()"
                        >
                            Remove
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-sharm-mist/70">JPEG, PNG, WebP or AVIF · max 5 MB · stored as WebP on all locales</p>
                    <p class="mt-1 text-xs text-red-500" x-show="errors.image" x-text="errors.image?.[0]"></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="admin-label">USD <span class="text-red-400">*</span></label>
                        <input type="number" x-model="form.price_usd" class="admin-input" min="0" step="1" required>
                        <p
                            x-show="showDiscountPreview(form.price_usd)"
                            class="mt-2 rounded-lg bg-sharm-sea/10 px-3 py-2 text-xs text-sharm-deep"
                        >
                            Sale price: <strong x-text="discountedPricePreview(form.price_usd, '$')"></strong>
                        </p>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.price_usd" x-text="errors.price_usd?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">EUR <span class="text-red-400">*</span></label>
                        <input type="number" x-model="form.price_eur" class="admin-input" min="0" step="1" required>
                        <p
                            x-show="showDiscountPreview(form.price_eur)"
                            class="mt-2 rounded-lg bg-sharm-sea/10 px-3 py-2 text-xs text-sharm-deep"
                        >
                            Sale price: <strong x-text="discountedPricePreview(form.price_eur, '€')"></strong>
                        </p>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.price_eur" x-text="errors.price_eur?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">RUB <span class="text-red-400">*</span></label>
                        <input type="number" x-model="form.price_rub" class="admin-input" min="0" step="1" required>
                        <p
                            x-show="showDiscountPreview(form.price_rub)"
                            class="mt-2 rounded-lg bg-sharm-sea/10 px-3 py-2 text-xs text-sharm-deep"
                        >
                            Sale price: <strong x-text="discountedPricePreview(form.price_rub, '₽')"></strong>
                        </p>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.price_rub" x-text="errors.price_rub?.[0]"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label">Hours <span class="text-red-400">*</span></label>
                        <input type="number" x-model="form.hours" class="admin-input" min="0.5" max="24" step="0.5" required>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.hours" x-text="errors.hours?.[0]"></p>
                    </div>
                    <div>
                        <label class="admin-label">Discount</label>
                        <select x-model.number="form.discount_percent" class="admin-input" required>
                            @foreach (\App\Models\Package::DISCOUNT_PERCENTAGES as $discount)
                                <option value="{{ $discount }}">{{ $discount === 0 ? 'No discount' : $discount.'% off' }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-sharm-mist">Applied to USD, EUR, and RUB.</p>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.discount_percent" x-text="errors.discount_percent?.[0]"></p>
                    </div>
                </div>

                <div>
                    <label class="admin-label">Available time slots <span class="text-red-400">*</span></label>
                    <p class="mt-0.5 text-xs text-sharm-mist">Times customers can pick for this tier (HH:MM, 24h). Past slots are auto-hidden for today on the booking form.</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="(slot, i) in form.available_times" :key="slot">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-sharm-deep/15 bg-sharm-sand/50 px-2.5 py-1 text-xs font-medium text-sharm-deep">
                                <span x-text="formatSlot(slot)"></span>
                                <button type="button" class="text-sharm-mist hover:text-red-500" @click="removeSlot(i)" aria-label="Remove slot">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        </template>
                        <span x-show="form.available_times.length === 0" class="text-xs text-sharm-mist">No slots added yet.</span>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <input type="time" x-model="newSlot" class="admin-input max-w-[140px]">
                        <button type="button" class="btn-primary gap-1.5 text-xs" @click="addSlot()">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add slot
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-red-500" x-show="errors.available_times" x-text="errors.available_times?.[0]"></p>
                </div>

                </div>

                <div class="mt-4 flex shrink-0 items-center justify-end gap-3 border-t border-sharm-deep/8 pt-4">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand" @click="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="submitting">
                        <span x-text="editingTier ? 'Update' : 'Create'"></span>
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
            <h3 class="text-sm font-semibold text-sharm-deep">Delete Package</h3>
            <p class="mt-1 text-sm text-sharm-mist">
                Remove <strong x-text="deleteTarget?.name_en"></strong>
                (<span x-text="formatHours(deleteTarget?.hours)"></span>) from all locales and currencies?
            </p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" class="rounded-lg px-4 py-2.5 text-sm text-sharm-mist hover:bg-sharm-sand" @click="deleteTarget = null">Cancel</button>
                <button type="button" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700" @click="deleteTier()" :disabled="submitting">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function packageManager() {
    return {
        tiers: @json($tiersJson),
        langTab: 'en',
        langTabs: [
            { id: 'en', label: 'English' },
            { id: 'ru', label: 'Русский' },
            { id: 'it', label: 'Italiano' },
            { id: 'ro', label: 'Română' },
            { id: 'bg', label: 'Български' },
            { id: 'tr', label: 'Türkçe' },
        ],
        modalOpen: false,
        editingTier: null,
        deleteTarget: null,
        submitting: false,
        errors: {},
        imageFile: null,
        imagePreview: null,
        removeImage: false,
        form: {
            name_en: '', name_ru: '', name_it: '', name_ro: '', name_bg: '', name_tr: '',
            description_en: '', description_ru: '', description_it: '', description_ro: '', description_bg: '', description_tr: '',
            price_usd: '', price_eur: '', price_rub: '',
            discount_percent: 0,
            hours: '2',
            available_times: [],
        },
        newSlot: '08:00',
        csrf: document.querySelector('meta[name="csrf-token"]').content,

        formatHours(hours) {
            if (hours == null) return ''
            return Number(hours) + 'h'
        },

        showDiscountPreview(price) {
            const amount = Number(price)
            return Number(this.form.discount_percent) > 0
                && String(price ?? '').trim() !== ''
                && Number.isFinite(amount)
                && amount >= 0
        },

        discountedPricePreview(price, symbol) {
            const amount = Number(price)
            const discount = Number(this.form.discount_percent)
            if (!Number.isFinite(amount) || !Number.isFinite(discount)) return ''

            const discounted = Math.round(amount * (100 - discount) / 100)
            return discounted.toLocaleString('en-US') + ' ' + symbol
        },

        formatSlot(slot) {
            const [h, m] = slot.split(':').map(Number)
            const period = h >= 12 ? 'PM' : 'AM'
            const hour12 = h % 12 === 0 ? 12 : h % 12
            return String(hour12).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + period
        },

        emptyForm() {
            return {
                name_en: '', name_ru: '', name_it: '', name_ro: '', name_bg: '', name_tr: '',
                description_en: '', description_ru: '', description_it: '', description_ro: '', description_bg: '', description_tr: '',
                price_usd: '', price_eur: '', price_rub: '',
                discount_percent: 0,
                hours: '2',
                available_times: [],
            }
        },

        openCreateModal() {
            this.editingTier = null
            this.form = this.emptyForm()
            this.resetImageState()
            this.langTab = 'en'
            this.errors = {}
            this.modalOpen = true
        },

        openEditModal(tier) {
            this.editingTier = tier
            this.form = {
                name_en: tier.name_en,
                name_ru: tier.name_ru,
                name_it: tier.name_it,
                name_ro: tier.name_ro,
                name_bg: tier.name_bg,
                name_tr: tier.name_tr,
                description_en: tier.description_en,
                description_ru: tier.description_ru,
                description_it: tier.description_it,
                description_ro: tier.description_ro,
                description_bg: tier.description_bg,
                description_tr: tier.description_tr,
                price_usd: tier.price_usd,
                price_eur: tier.price_eur,
                price_rub: tier.price_rub,
                discount_percent: tier.discount_percent,
                hours: tier.hours,
                available_times: [...(tier.available_times || [])],
            }
            this.resetImageState()
            this.imagePreview = tier.image_url || null
            this.langTab = 'en'
            this.errors = {}
            this.modalOpen = true
        },

        resetImageState() {
            if (this.imagePreview && this.imagePreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.imagePreview)
            }
            this.imageFile = null
            this.imagePreview = null
            this.removeImage = false
        },

        pickImage(event) {
            const file = event.target.files?.[0]
            event.target.value = ''
            if (!file) return

            const allowedExts = ['.jpg', '.jpeg', '.png', '.webp', '.avif']
            const ext = '.' + file.name.split('.').pop().toLowerCase()
            if (!allowedExts.includes(ext)) {
                window.showToast('error', `"${file.name}" — only JPEG, PNG, WebP, or AVIF allowed.`)
                return
            }
            if (file.size > 5 * 1024 * 1024) {
                window.showToast('error', `"${file.name}" — exceeds 5 MB limit.`)
                return
            }

            if (this.imagePreview && this.imagePreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.imagePreview)
            }

            this.imageFile = file
            this.imagePreview = URL.createObjectURL(file)
            this.removeImage = false
        },

        clearImage() {
            if (this.imagePreview && this.imagePreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.imagePreview)
            }
            this.imageFile = null
            this.imagePreview = null
            this.removeImage = !!this.editingTier?.image_url
        },

        closeModal() {
            this.modalOpen = false
            this.editingTier = null
            this.resetImageState()
            this.errors = {}
        },

        tierUrl(hours, action = '') {
            const base = `{{ url('admin/packages/tier') }}/${hours}`
            return action ? `${base}/${action}` : base
        },

        upsertTier(tier) {
            const idx = this.tiers.findIndex(t => t.hours === tier.hours)
            if (idx !== -1) {
                this.tiers[idx] = tier
            } else {
                this.tiers.push(tier)
                this.tiers.sort((a, b) => a.hours - b.hours)
            }
        },

        buildFormData() {
            const formData = new FormData()
            Object.entries(this.form).forEach(([key, value]) => {
                if (key === 'available_times') {
                    const slots = Array.isArray(value) ? value : []
                    if (slots.length === 0) {
                        formData.append('available_times[]', '')
                    } else {
                        slots.forEach(slot => formData.append('available_times[]', slot))
                    }
                } else {
                    formData.append(key, value ?? '')
                }
            })
            if (this.imageFile) {
                formData.append('image', this.imageFile)
            }
            if (this.removeImage) {
                formData.append('remove_image', '1')
            }
            if (this.editingTier) {
                formData.append('_method', 'PUT')
            }
            return formData
        },

        addSlot() {
            const slot = (this.newSlot || '').trim()
            if (!/^\d{2}:\d{2}$/.test(slot)) {
                window.showToast('error', 'Use HH:MM format.')
                return
            }
            if (this.form.available_times.includes(slot)) {
                window.showToast('error', 'That time is already added.')
                return
            }
            this.form.available_times.push(slot)
            this.form.available_times.sort()
        },

        removeSlot(index) {
            this.form.available_times.splice(index, 1)
        },

        validateHours() {
            const hours = Number(this.form.hours)
            if (!String(this.form.hours ?? '').trim() || !Number.isFinite(hours)
                || hours < 0.5 || hours > 24 || !Number.isInteger(hours * 2)) {
                this.errors.hours = ['Duration must be between 0.5 and 24 hours in 0.5-hour increments.']
                return false
            }
            delete this.errors.hours
            return true
        },

        async submitModal() {
            if (this.submitting) return
            this.errors = {}
            if (!this.validateHours()) {
                window.toastFromResponse({ errors: this.errors })
                return
            }
            this.submitting = true

            const url = this.editingTier
                ? this.tierUrl(this.editingTier.hours)
                : `{{ route('admin.packages.store') }}`

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: this.buildFormData(),
                })

                const data = await window.readJsonResponse(res)

                if (!res.ok) {
                    if (data.errors) this.errors = data.errors
                    window.toastFromResponse(data, 'Failed to save package.')
                    return
                }

                if (this.editingTier && this.editingTier.hours !== data.tier.hours) {
                    this.tiers = this.tiers.filter(t => t.hours !== this.editingTier.hours)
                }

                this.upsertTier(data.tier)
                window.showToast('success', data.message)
                this.closeModal()
            } catch (e) {
                window.showToast('error', 'Network error. Please try again.')
            } finally {
                this.submitting = false
            }
        },

        async toggleTier(tier) {
            try {
                const res = await fetch(this.tierUrl(tier.hours, 'toggle'), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await window.readJsonResponse(res)
                if (res.ok) {
                    tier.is_active = data.is_active
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        async toggleFeatured(tier) {
            try {
                const res = await fetch(this.tierUrl(tier.hours, 'featured'), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await window.readJsonResponse(res)
                if (res.ok) {
                    tier.is_featured = data.is_featured
                    window.showToast('success', data.message)
                } else {
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            }
        },

        confirmDelete(tier) {
            this.deleteTarget = tier
        },

        async deleteTier() {
            if (!this.deleteTarget) return
            this.submitting = true
            const hours = this.deleteTarget.hours
            try {
                const res = await fetch(this.tierUrl(hours), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                })
                const data = await window.readJsonResponse(res)
                if (res.ok) {
                    this.tiers = this.tiers.filter(t => t.hours !== hours)
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
