@extends('admin.layouts.app')

@section('title', 'Profile')
@section('page_title', 'Profile')
@section('breadcrumb', 'Manage your account information and security')

@section('content')
@php
    $photoUrl = $admin->photo_url;
    $initial = strtoupper(substr($admin->name, 0, 1));
@endphp

<div
    class="mx-auto max-w-3xl"
    x-data="{
        tab: new URLSearchParams(window.location.search).get('tab') === 'security' ? 'security' : 'info',

        // Info form
        infoLoading: false,
        infoErrors: {},
        photoPreview: @js($photoUrl),
        initial: @js($initial),
        form: {
            name: @js($admin->name),
            username: @js($admin->username),
            email: @js($admin->email),
        },

        onPhotoChange(event) {
            const file = event.target.files[0]
            if (!file) return
            if (this.photoPreview && this.photoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.photoPreview)
            }
            this.photoPreview = URL.createObjectURL(file)
        },

        async submitInfo() {
            this.infoLoading = true
            this.infoErrors = {}

            const fd = new FormData()
            fd.append('_method', 'PUT')
            fd.append('name', this.form.name)
            fd.append('username', this.form.username)
            fd.append('email', this.form.email)

            const fileInput = this.$refs.photoInput
            if (fileInput?.files[0]) {
                fd.append('photo', fileInput.files[0])
            }

            try {
                const res = await fetch(@js(route('admin.profile.update')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: fd,
                })

                const data = await res.json()

                if (res.ok && data.success) {
                    if (data.photo_url) this.photoPreview = data.photo_url
                    if (data.initial) this.initial = data.initial
                    if (fileInput) fileInput.value = ''
                    window.showToast('success', data.message)
                    this.infoLoading = false
                    return
                }

                if (data.errors) this.infoErrors = data.errors
            } catch {}

            this.infoLoading = false
        },

        // Security form
        passLoading: false,
        passErrors: {},
        passForm: { current_password: '', password: '', password_confirmation: '' },
        showCurrent: false,
        showNew: false,
        showConfirm: false,
        checks: { length: false, lower: false, upper: false, digit: false, special: false },

        updateChecks(password) {
            this.checks.length = password.length >= 8
            this.checks.lower = /[a-z]/.test(password)
            this.checks.upper = /[A-Z]/.test(password)
            this.checks.digit = /\d/.test(password)
            this.checks.special = /[^A-Za-z0-9]/.test(password)
        },

        get strengthScore() {
            return Object.values(this.checks).filter(Boolean).length
        },

        get strengthPercent() {
            return this.strengthScore * 20
        },

        get strengthColor() {
            if (this.strengthScore <= 2) return 'bg-red-400'
            if (this.strengthScore <= 4) return 'bg-amber-400'
            return 'bg-emerald-500'
        },

        get strengthLabel() {
            if (!this.passForm.password) return ''
            if (this.strengthScore <= 2) return 'Weak'
            if (this.strengthScore <= 4) return 'Fair'
            return 'Strong'
        },

        async submitPassword() {
            this.passLoading = true
            this.passErrors = {}

            try {
                const res = await fetch(@js(route('admin.profile.password')), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(this.passForm),
                })

                const data = await res.json()

                if (res.ok && data.success) {
                    this.passForm = { current_password: '', password: '', password_confirmation: '' }
                    this.updateChecks('')
                    window.showToast('success', data.message)
                    this.passLoading = false
                    return
                }

                if (data.errors) this.passErrors = data.errors
            } catch {}

            this.passLoading = false
        },
    }"
>
    {{-- Tabs --}}
    <div class="mb-6 flex gap-1 rounded-xl border border-sharm-deep/10 bg-white p-1 shadow-sm">
        <button
            type="button"
            @click="tab = 'info'"
            class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium transition"
            :class="tab === 'info' ? 'bg-sharm-sea text-white shadow-sm' : 'text-sharm-mist hover:bg-sharm-sand hover:text-sharm-deep'"
        >
            Account Info
        </button>
        <button
            type="button"
            @click="tab = 'security'"
            class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium transition"
            :class="tab === 'security' ? 'bg-sharm-sea text-white shadow-sm' : 'text-sharm-mist hover:bg-sharm-sand hover:text-sharm-deep'"
        >
            Security
        </button>
    </div>

    {{-- Info tab --}}
    <div x-show="tab === 'info'" x-cloak class="admin-card p-6 sm:p-8">
        <form @submit.prevent="submitInfo" class="space-y-6">

            {{-- Photo --}}
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <div class="relative shrink-0">
                    <template x-if="photoPreview">
                        <img :src="photoPreview" alt="Profile photo" class="h-24 w-24 rounded-full border-2 border-sharm-deep/10 object-cover shadow-sm">
                    </template>
                    <template x-if="!photoPreview">
                        <div class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-sharm-deep/10 bg-sharm-desert/15 text-2xl font-bold text-sharm-desert" x-text="initial"></div>
                    </template>
                </div>
                <div class="text-center sm:text-left">
                    <p class="text-sm font-semibold text-sharm-deep">Profile photo</p>
                    <p class="mt-0.5 text-xs text-sharm-mist">JPG, PNG or WebP. Max 2 MB. Preview updates before you save.</p>
                    <label class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-sharm-deep/10 bg-sharm-sand/50 px-4 py-2 text-xs font-medium text-sharm-deep transition hover:border-sharm-sea/25 hover:bg-white">
                        <svg class="h-4 w-4 text-sharm-mist" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        Choose photo
                        <input x-ref="photoInput" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" @change="onPhotoChange($event)">
                    </label>
                    <p x-show="infoErrors.photo" x-text="infoErrors.photo?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
                </div>
            </div>

            <hr class="border-sharm-deep/8">

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">Full name</label>
                    <input id="name" type="text" x-model="form.name" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="infoErrors.name && 'border-red-400'">
                    <p x-show="infoErrors.name" x-text="infoErrors.name?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
                </div>
                <div>
                    <label for="username" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">Username</label>
                    <input id="username" type="text" x-model="form.username" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="infoErrors.username && 'border-red-400'">
                    <p x-show="infoErrors.username" x-text="infoErrors.username?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">Email</label>
                    <input id="email" type="email" x-model="form.email" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="infoErrors.email && 'border-red-400'">
                    <p x-show="infoErrors.email" x-text="infoErrors.email?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" :disabled="infoLoading" class="inline-flex items-center gap-2 rounded-xl bg-sharm-sea px-6 py-3 text-sm font-semibold text-white transition hover:bg-sharm-sea-dark disabled:opacity-70">
                    <svg x-show="infoLoading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="infoLoading ? 'Saving…' : 'Save changes'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- Security tab --}}
    <div x-show="tab === 'security'" x-cloak class="admin-card p-6 sm:p-8">
        <form @submit.prevent="submitPassword" class="space-y-5">

            <div>
                <label for="current_password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">Current password</label>
                <div class="relative">
                    <input id="current_password" :type="showCurrent ? 'text' : 'password'" x-model="passForm.current_password" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 pr-11 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="passErrors.current_password && 'border-red-400'">
                    <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-sharm-mist hover:text-sharm-deep" tabindex="-1">
                        <svg x-show="!showCurrent" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg x-show="showCurrent" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
                <p x-show="passErrors.current_password" x-text="passErrors.current_password?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">New password</label>
                <div class="relative">
                    <input id="password" :type="showNew ? 'text' : 'password'" x-model="passForm.password" @input="updateChecks(passForm.password)" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 pr-11 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="passErrors.password && 'border-red-400'">
                    <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-sharm-mist hover:text-sharm-deep" tabindex="-1">
                        <svg x-show="!showNew" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg x-show="showNew" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
                <p x-show="passErrors.password" x-text="passErrors.password?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>

                {{-- Password meter --}}
                <div x-show="passForm.password" x-cloak class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-sharm-mist">Password strength</span>
                        <span class="font-medium" :class="strengthScore <= 2 ? 'text-red-500' : strengthScore <= 4 ? 'text-amber-500' : 'text-emerald-600'" x-text="strengthLabel"></span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-sharm-sand-dark">
                        <div class="h-full rounded-full transition-all duration-300" :class="strengthColor" :style="`width: ${strengthPercent}%`"></div>
                    </div>
                    <ul class="mt-2 space-y-1.5">
                        <template x-for="rule in [
                            { key: 'length', label: 'At least 8 characters' },
                            { key: 'lower', label: 'One lowercase letter' },
                            { key: 'upper', label: 'One uppercase letter' },
                            { key: 'digit', label: 'One digit (0–9)' },
                            { key: 'special', label: 'One special character' },
                        ]" :key="rule.key">
                            <li class="flex items-center gap-2 text-xs" :class="checks[rule.key] ? 'text-emerald-600' : 'text-sharm-mist'">
                                <span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full border" :class="checks[rule.key] ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-sharm-mist/40'">
                                    <svg x-show="checks[rule.key]" class="h-2 w-2" fill="none" viewBox="0 0 24 24" stroke-width="4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </span>
                                <span x-text="rule.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-sharm-mist">Confirm new password</label>
                <div class="relative">
                    <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" x-model="passForm.password_confirmation" class="w-full rounded-xl border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 pr-11 text-sm text-sharm-deep outline-none transition focus:border-sharm-sea focus:ring-2 focus:ring-sharm-sea/15" :class="passErrors.password_confirmation && 'border-red-400'">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-sharm-mist hover:text-sharm-deep" tabindex="-1">
                        <svg x-show="!showConfirm" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg x-show="showConfirm" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
                <p x-show="passErrors.password_confirmation" x-text="passErrors.password_confirmation?.[0]" class="mt-1.5 text-xs text-red-500" x-cloak></p>
                <p x-show="passForm.password_confirmation && passForm.password !== passForm.password_confirmation" class="mt-1.5 text-xs text-red-500" x-cloak>Passwords do not match.</p>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" :disabled="passLoading || strengthScore < 5 || passForm.password !== passForm.password_confirmation" class="inline-flex items-center gap-2 rounded-xl bg-sharm-sea px-6 py-3 text-sm font-semibold text-white transition hover:bg-sharm-sea-dark disabled:cursor-not-allowed disabled:opacity-70">
                    <svg x-show="passLoading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="passLoading ? 'Updating…' : 'Update password'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
