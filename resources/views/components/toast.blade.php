<div
    x-data
    class="pointer-events-none fixed right-4 top-4 z-[9999] flex flex-col gap-2.5"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
            class="pointer-events-auto flex min-w-[18rem] max-w-sm items-center gap-3 rounded-xl px-4 py-3.5 text-white shadow-lg"
            :class="{
                'bg-emerald-600 shadow-emerald-600/25': toast.type === 'success',
                'bg-red-600 shadow-red-600/25': toast.type === 'error',
                'bg-amber-500 shadow-amber-500/25': toast.type === 'warning',
                'bg-blue-600 shadow-blue-600/25': toast.type === 'info',
            }"
        >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15">
                <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                <svg x-show="toast.type === 'warning'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            </div>
            <p class="flex-1 text-sm font-medium leading-snug" x-text="toast.message"></p>
            <button
                type="button"
                @click="$store.toast.remove(toast.id)"
                class="shrink-0 rounded-lg p-1 text-white/60 transition hover:bg-white/10 hover:text-white"
                aria-label="Dismiss"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
