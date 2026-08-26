<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }} Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @include('partials.alpine-toast-store')
</head>
<body
    class="min-h-screen bg-sharm-sand font-admin text-sharm-deep antialiased"
    x-data="{
        sidebarOpen: false,
        profileOpen: false,
        sidebarCollapsed: JSON.parse(localStorage.getItem('admin_sidebar_collapsed') || 'false'),
        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed
            localStorage.setItem('admin_sidebar_collapsed', JSON.stringify(this.sidebarCollapsed))
        },
        logout() {
            document.getElementById('logout-form').submit()
        }
    }"
    @keydown.escape.window="sidebarOpen = false; profileOpen = false"
>
    @php
        $adminUser = Auth::guard('admin')->user();
        $adminInitial = strtoupper(substr($adminUser->name ?? 'A', 0, 1));
        $adminPhoto = $adminUser->photo_url;
    @endphp

    {{-- Mobile backdrop --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-sharm-deep/30 backdrop-blur-sm lg:hidden"
    ></div>

    {{-- Sidebar wrapper (toggle sits outside) --}}
    <div
        class="fixed inset-y-0 left-0 z-40 hidden lg:block"
        :class="sidebarCollapsed ? 'w-16' : 'w-60'"
    >
        <aside
            class="flex h-full w-full flex-col border-r border-sharm-deep/10 bg-white transition-all duration-300"
        >
            {{-- Logo --}}
            <div class="flex h-16 shrink-0 items-center border-b border-sharm-deep/10 px-3">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex w-full items-center gap-3 overflow-hidden"
                    :class="sidebarCollapsed ? 'justify-center' : ''"
                >
                    <span
                        class="flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-sharm-sand/80 p-1.5 transition-all duration-300"
                        :class="sidebarCollapsed ? 'h-11 w-11' : 'h-14 w-14'"
                    >
                        @include('partials.site-logo', ['size' => 'admin'])
                    </span>
                    <span
                        class="truncate text-sm font-semibold tracking-tight text-sharm-deep transition-all duration-300"
                        :class="sidebarCollapsed ? 'w-0 opacity-0' : 'opacity-100'"
                    >
                        {{ config('app.name') }}
                    </span>
                </a>
            </div>

            {{-- Nav --}}
            <nav class="flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden p-2 pt-3">
                @php
        $newBookingsCount = \App\Models\Booking::where('status', 'new')->count();
        $pendingReviewsCount = \App\Models\Review::where('status', 'pending')->count();
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'disabled' => false, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'],
            ['label' => 'Profile', 'route' => 'admin.profile', 'active' => request()->routeIs('admin.profile'), 'disabled' => false, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />'],
            ['label' => 'Gallery', 'route' => 'admin.gallery.index', 'active' => request()->routeIs('admin.gallery.*'), 'disabled' => false, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />'],
            ['label' => 'Packages', 'route' => 'admin.packages.index', 'active' => request()->routeIs('admin.packages.*'), 'disabled' => false, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />'],
            ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => request()->routeIs('admin.bookings.*'), 'disabled' => false, 'badge' => $newBookingsCount, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />'],
            ['label' => 'Reviews', 'route' => 'admin.reviews.index', 'active' => request()->routeIs('admin.reviews.*'), 'disabled' => false, 'badge' => $pendingReviewsCount, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />'],
            ['label' => 'Settings', 'route' => null, 'active' => false, 'disabled' => true, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />'],
        ];
    @endphp

                @foreach ($navItems as $item)
                    @if ($item['disabled'])
                        <div
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sharm-mist/40 cursor-not-allowed select-none"
                            :class="sidebarCollapsed ? 'justify-center' : ''"
                            :title="sidebarCollapsed ? '{{ $item['label'] }} (soon)' : ''"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">{!! $item['icon'] !!}</svg>
                            <span class="truncate transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'opacity-100'">{{ $item['label'] }}</span>
                            <span class="transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'opacity-100'">
                                <span class="rounded bg-sharm-sand px-1.5 py-0.5 text-[9px] font-medium uppercase tracking-wider text-sharm-mist/60">Soon</span>
                            </span>
                        </div>
                    @else
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition duration-150"
                            :class="sidebarCollapsed ? 'justify-center' : ''"
                            :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                            @class([
                                'admin-nav-active' => $item['active'],
                                'admin-nav-item' => ! $item['active'],
                            ])
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">{!! $item['icon'] !!}</svg>
                            <span class="truncate transition-all duration-300" :class="sidebarCollapsed ? 'w-0 opacity-0' : 'opacity-100'">{{ $item['label'] }}</span>
                            @if (! empty($item['badge']) && $item['badge'] > 0)
                                <span
                                    class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white transition-all duration-300"
                                    :class="sidebarCollapsed ? 'hidden' : ''"
                                >{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Sidebar footer: avatar + logout stacked when collapsed --}}
            <div class="shrink-0 border-t border-sharm-deep/10 p-3">
                <div
                    class="flex rounded-lg px-1 py-1"
                    :class="sidebarCollapsed ? 'flex-col items-center gap-2' : 'flex-row items-center gap-3'"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-sharm-desert/15 text-xs font-bold text-sharm-desert">
                        @if ($adminPhoto)
                            <img src="{{ $adminPhoto }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ $adminInitial }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 transition-all duration-300" :class="sidebarCollapsed ? 'hidden' : 'block'">
                        <p class="truncate text-xs font-semibold text-sharm-deep">{{ $adminUser->name ?? 'Admin' }}</p>
                        <p class="truncate text-[10px] text-sharm-mist">Administrator</p>
                    </div>
                    <button
                        type="button"
                        @click="logout()"
                        :title="'Logout'"
                        class="shrink-0 rounded-lg p-2 text-sharm-mist transition hover:bg-red-50 hover:text-red-500"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        {{-- Collapse toggle — outside sidebar edge --}}
        <button
            type="button"
            @click="toggleCollapse()"
            class="absolute -right-3 top-[4.25rem] z-50 flex h-6 w-6 items-center justify-center rounded-full border border-sharm-deep/10 bg-white text-sharm-mist shadow-soft transition hover:border-sharm-sea/30 hover:text-sharm-sea"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="sidebarCollapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
    </div>

    {{-- Mobile sidebar --}}
    <aside
        x-show="sidebarOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-sharm-deep/10 bg-white lg:hidden"
    >
        <div class="flex h-16 items-center border-b border-sharm-deep/10 px-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-sharm-sand/80 p-1.5">
                    @include('partials.site-logo', ['size' => 'admin'])
                </span>
                <span class="text-sm font-semibold text-sharm-deep">{{ config('app.name') }}</span>
            </a>
        </div>
        <nav class="flex flex-1 flex-col gap-1 p-2 pt-3">
            @foreach ($navItems as $item)
                @if ($item['disabled'])
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sharm-mist/40 cursor-not-allowed">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </div>
                @else
                    <a href="{{ route($item['route']) }}" @click="sidebarOpen = false" @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium', 'admin-nav-active' => $item['active'], 'admin-nav-item' => ! $item['active']])>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </aside>

    {{-- Main --}}
    <div
        class="flex min-h-screen flex-col transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-60'"
    >
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-sharm-deep/10 bg-white/95 px-4 backdrop-blur-xl sm:px-6">

            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep lg:hidden"
                aria-label="Toggle sidebar"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="flex-1 min-w-0">
                <h1 class="truncate text-sm font-semibold text-sharm-deep">@yield('page_title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                    <p class="truncate text-xs text-sharm-mist">@yield('breadcrumb')</p>
                @endif
            </div>

            {{-- Profile dropdown --}}
            <div class="relative" @click.outside="profileOpen = false">
                <button
                    type="button"
                    @click="profileOpen = !profileOpen"
                    class="flex items-center gap-2.5 rounded-lg border border-sharm-deep/10 bg-white px-2 py-1.5 shadow-sm transition hover:border-sharm-sea/20"
                    :aria-expanded="profileOpen"
                >
                    <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-sharm-desert/15 text-xs font-bold text-sharm-desert">
                        @if ($adminPhoto)
                            <img src="{{ $adminPhoto }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ $adminInitial }}
                        @endif
                    </div>
                    <div class="hidden text-left sm:block">
                        <p class="max-w-[120px] truncate text-xs font-semibold text-sharm-deep">{{ $adminUser->name ?? 'Admin' }}</p>
                        <p class="text-[10px] text-sharm-mist">Administrator</p>
                    </div>
                    <svg class="h-3.5 w-3.5 text-sharm-mist transition" :class="profileOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div
                    x-show="profileOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 top-full z-50 mt-2 w-52 overflow-hidden rounded-xl border border-sharm-deep/10 bg-white py-1 shadow-lg shadow-sharm-deep/10"
                >
                    <div class="border-b border-sharm-deep/8 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-sharm-deep">{{ $adminUser->name ?? 'Admin' }}</p>
                        <p class="truncate text-xs text-sharm-mist">{{ $adminUser->email ?? '' }}</p>
                    </div>
                    <a
                        href="{{ route('admin.profile') }}"
                        @click="profileOpen = false"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Profile
                    </a>
                    <button
                        type="button"
                        @click="profileOpen = false; logout()"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-500 transition hover:bg-red-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>
    </div>

    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    @stack('scripts')

    @include('partials.whatsapp-float')

    <x-toast />
</body>
</html>
