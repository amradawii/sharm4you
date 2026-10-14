@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Overview')
@section('breadcrumb', 'Welcome back, ' . (Auth::guard('admin')->user()->name ?? 'Admin'))

@section('content')

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        @php
            $photoCount = \App\Models\Photo::count();
            $albumCount = \App\Models\Album::count();
            $activeTierCount = \App\Models\Package::where('is_active', true)->distinct()->count('hours');
            $activeTierHours = \App\Models\Package::where('is_active', true)
                ->distinct()
                ->orderBy('hours')
                ->pluck('hours')
                ->map(fn ($h) => rtrim(rtrim((string) $h, '0'), '.').'h')
                ->implode(' · ');
            $newBookings = \App\Models\Booking::where('status', 'new')->count();
            $stats = [
                [
                    'label'  => 'Gallery Photos',
                    'value'  => $photoCount,
                    'note'   => $albumCount . ' album(s)',
                    'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />',
                    'accent' => 'text-sharm-sea',
                ],
                [
                    'label'  => 'Active Packages',
                    'value'  => $activeTierCount,
                    'note'   => $activeTierHours ?: 'None yet',
                    'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
                    'accent' => 'text-sharm-desert',
                ],
                [
                    'label'  => 'New Bookings',
                    'value'  => $newBookings,
                    'note'   => 'Awaiting response',
                    'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />',
                    'accent' => 'text-red-500',
                ],
                [
                    'label'  => 'Supported Locales',
                    'value'  => count(config('locales.supported', [])),
                    'note'   => strtoupper(implode(' · ', array_keys(config('locales.supported', [])))),
                    'icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M3.284 14.253A8.959 8.959 0 0 1 3 12c0-1.007.165-1.977.457-2.918" />',
                    'accent' => 'text-sharm-sunset',
                ],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="admin-card flex items-start gap-4 p-5">
                <svg class="h-5 w-5 shrink-0 {{ $stat['accent'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    {!! $stat['icon'] !!}
                </svg>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-sharm-mist">{{ $stat['label'] }}</p>
                    <p class="mt-0.5 text-2xl font-bold tracking-tight text-sharm-deep">{{ $stat['value'] }}</p>
                    <p class="mt-0.5 text-[10px] text-sharm-mist/70">{{ $stat['note'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        <div class="admin-card p-5">
            <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-sharm-mist">Quick Actions</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">

                @php
                    $actions = [
                        ['label' => 'Manage Gallery',       'note' => $albumCount . ' albums',  'disabled' => false, 'href' => route('admin.gallery.index')],
                        ['label' => 'Edit Package Pricing', 'note' => $activeTierCount . ' active tier(s)', 'disabled' => false, 'href' => route('admin.packages.index')],
                        ['label' => 'View Bookings',        'note' => $newBookings . ' new',    'disabled' => false, 'href' => route('admin.bookings.index')],
                        ['label' => 'View Public Website',  'note' => 'Open site',   'disabled' => false, 'href' => url('/')],
                        ['label' => 'View Gallery Page',    'note' => 'Open gallery','disabled' => false, 'href' => url('/gallery')],
                    ];
                @endphp

                @foreach ($actions as $action)
                    @if ($action['disabled'])
                        <div class="flex items-center justify-between rounded-lg border border-sharm-deep/5 bg-sharm-sand/50 px-4 py-3 opacity-50 cursor-not-allowed">
                            <span class="text-sm text-sharm-deep">{{ $action['label'] }}</span>
                            <span class="text-xs text-sharm-mist">{{ $action['note'] }}</span>
                        </div>
                    @else
                        <a
                            href="{{ $action['href'] }}"
                            @if($action['label'] !== 'Manage Gallery' && $action['label'] !== 'Edit Package Pricing' && $action['label'] !== 'View Bookings') target="_blank" @endif
                            class="flex items-center justify-between rounded-lg border border-sharm-deep/10 bg-sharm-sand/40 px-4 py-3 transition hover:border-sharm-sea/25 hover:bg-white"
                        >
                            <span class="text-sm font-medium text-sharm-deep">{{ $action['label'] }}</span>
                            @if(in_array($action['label'], ['Manage Gallery', 'Edit Package Pricing', 'View Bookings']))
                                <span class="text-xs text-sharm-mist">{{ $action['note'] }}</span>
                            @else
                                <svg class="h-3.5 w-3.5 text-sharm-mist" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            @endif
                        </a>
                    @endif
                @endforeach

            </div>
        </div>
    </div>

@endsection
