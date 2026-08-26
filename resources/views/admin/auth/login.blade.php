<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @include('partials.alpine-toast-store')
</head>
<body class="min-h-screen bg-sharm-sand font-admin text-sharm-deep">

    <div class="relative flex min-h-screen items-center justify-center px-4 py-16">

        {{-- Decorative background --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-40 -top-40 h-[500px] w-[500px] rounded-full bg-sharm-sea/8 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 h-[500px] w-[500px] rounded-full bg-sharm-desert/10 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-sm">

            {{-- Logo / Brand --}}
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-sharm-sea text-lg font-bold text-white">S</div>
                <span class="text-2xl font-bold tracking-tight text-sharm-deep">
                    {{ config('app.name') }}
                </span>
                <p class="mt-1.5 text-xs font-medium tracking-widest text-sharm-mist uppercase">Admin Panel</p>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-sharm-deep/10 bg-white p-8 shadow-soft"
                x-data="{
                    loading: false,
                    form: { username: '', password: '' },
                    errors: {},
                    showPassword: false,
                    async submit() {
                        this.loading = true
                        this.errors = {}

                        try {
                            const res = await fetch('{{ route('admin.login.post') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify(this.form),
                            })

                            const data = await res.json()

                            if (res.ok && data.success) {
                                window.showToast('success', data.message)
                                setTimeout(() => { window.location.href = data.redirect }, 600)
                                return
                            }

                            if (res.status === 429) {
                                window.showToast('warning', data.message)
                                this.loading = false
                                return
                            }

                            if (data.errors) {
                                this.errors = data.errors
                            }

                            window.toastFromResponse(data, 'Login failed. Please check your credentials.')

                        } catch (e) {
                            window.showToast('error', 'Something went wrong. Please try again.')
                        }

                        this.loading = false
                    }
                }"
            >
                <h1 class="mb-6 text-center text-lg font-semibold text-sharm-deep">Sign in to continue</h1>

                <form @submit.prevent="submit" novalidate class="space-y-5">

                    {{-- Username --}}
                    <div>
                        <label for="username" class="mb-1.5 block text-xs font-semibold text-sharm-mist uppercase tracking-wider">
                            Username
                        </label>
                        <input
                            id="username"
                            type="text"
                            x-model="form.username"
                            autocomplete="username"
                            placeholder="your_username"
                            :class="errors.username ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-sharm-deep/10 focus:border-sharm-sea focus:ring-sharm-sea/15'"
                            class="w-full rounded-xl border bg-sharm-sand/50 px-4 py-3 text-sm text-sharm-deep placeholder-sharm-mist/50 outline-none transition focus:ring-2"
                        >
                        <p
                            x-show="errors.username"
                            x-text="errors.username?.[0]"
                            class="mt-1.5 text-xs text-red-500"
                            x-cloak
                        ></p>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-semibold text-sharm-mist uppercase tracking-wider">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                x-model="form.password"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                :class="errors.password ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-sharm-deep/10 focus:border-sharm-sea focus:ring-sharm-sea/15'"
                                class="w-full rounded-xl border bg-sharm-sand/50 px-4 py-3 pr-11 text-sm text-sharm-deep placeholder-sharm-mist/50 outline-none transition focus:ring-2"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-sharm-mist transition hover:text-sharm-deep"
                                tabindex="-1"
                            >
                                {{-- eye open --}}
                                <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                {{-- eye closed --}}
                                <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p
                            x-show="errors.password"
                            x-text="errors.password?.[0]"
                            class="mt-1.5 text-xs text-red-500"
                            x-cloak
                        ></p>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        :disabled="loading"
                        class="mt-2 flex w-full items-center justify-center gap-2.5 rounded-xl bg-sharm-sea px-6 py-3.5 text-sm font-semibold text-white transition duration-200 hover:bg-sharm-sea-dark disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{-- Spinner --}}
                        <svg
                            x-show="loading"
                            x-cloak
                            class="h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
                    </button>

                </form>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-sharm-mist/60">
                {{ config('app.name') }} &mdash; Secure Admin Access
            </p>

        </div>
    </div>

    @include('partials.whatsapp-float')

    <x-toast />
</body>
</html>
