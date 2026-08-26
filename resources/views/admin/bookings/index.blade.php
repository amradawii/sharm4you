@extends('admin.layouts.app')

@section('title', 'Bookings')
@section('page_title', 'Bookings')
@section('breadcrumb', 'Tourist session requests & admin ad-hoc bookings')

@section('content')
<div
    x-data="bookingManager()"
    x-cloak
>
    {{-- Status filter tabs + new booking CTA --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            @php
                $filters = [
                    ['value' => null, 'label' => 'All'],
                    ['value' => 'new', 'label' => 'New'],
                    ['value' => 'contacted', 'label' => 'Contacted'],
                    ['value' => 'confirmed', 'label' => 'Confirmed'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ];
            @endphp
            @foreach ($filters as $filter)
                <a
                    href="{{ $filter['value'] ? route('admin.bookings.index', ['status' => $filter['value']]) : route('admin.bookings.index') }}"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                        'bg-sharm-sea text-white' => ($statusFilter ?? null) === $filter['value'],
                        'bg-sharm-sand text-sharm-mist hover:bg-sharm-sand-dark hover:text-sharm-deep' => ($statusFilter ?? null) !== $filter['value'],
                    ])
                >
                    {{ $filter['label'] }}
                    @if ($filter['value'] === 'new' && $newCount > 0)
                        <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $newCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <button type="button" class="btn-primary shrink-0 gap-2" @click="openCreateModal()">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New Booking
        </button>
    </div>

    {{-- Table --}}
    <div class="admin-card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="border-b border-sharm-deep/10 bg-sharm-sand/50 text-xs uppercase tracking-wider text-sharm-mist">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Booking #</th>
                        <th class="px-4 py-3 font-semibold">Source</th>
                        <th class="px-4 py-3 font-semibold">Customer</th>
                        <th class="px-4 py-3 font-semibold">Phone</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Package</th>
                        <th class="px-4 py-3 font-semibold">Session</th>
                        <th class="px-4 py-3 font-semibold">Amount</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Received</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sharm-deep/5">
                    @forelse ($bookings as $booking)
                        <tr class="hover:bg-sharm-sand/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="font-mono text-xs font-semibold text-sharm-sea hover:underline">{{ $booking->booking_number }}</a>
                                @if ($booking->photos_count > 0)
                                    <span class="mt-0.5 block text-[10px] text-sharm-mist">{{ $booking->photos_count }} photo{{ $booking->photos_count === 1 ? '' : 's' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $booking->source_color }}">{{ $booking->source_label }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-sharm-deep">{{ $booking->name }}</td>
                            <td class="px-4 py-3">
                                <a href="tel:{{ $booking->country_code }}{{ preg_replace('/\s+/', '', $booking->phone) }}" class="text-sharm-sea hover:underline">{{ $booking->full_phone }}</a>
                            </td>
                            <td class="px-4 py-3 text-sharm-mist">
                                @if ($booking->email)
                                    <a href="mailto:{{ $booking->email }}" class="text-sharm-sea hover:underline">{{ $booking->email }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sharm-mist">{{ $booking->package?->name_en ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-sharm-mist">{{ $booking->session_datetime_label }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-sharm-deep">{{ $booking->amount_label }}</td>
                            <td class="px-4 py-3">
                                <form
                                    method="POST"
                                    action="{{ route('admin.bookings.status', $booking) }}"
                                    class="inline-block"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <select
                                        name="status"
                                        class="admin-status-select {{ $booking->status_color }}"
                                        onchange="this.form.submit()"
                                    >
                                        @foreach ($booking->allowedStatuses() as $status)
                                            <option value="{{ $status }}" @selected($booking->status === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-xs text-sharm-mist">{{ $booking->created_at->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <a
                                        href="{{ route('admin.bookings.show', $booking) }}"
                                        class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sea/10 hover:text-sharm-sea"
                                        title="Deliver photos"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                        </svg>
                                    </a>
                                    @if ($booking->isAdminBooking())
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sea/10 hover:text-sharm-sea"
                                            title="Edit booking"
                                            @click="openEditModal(@js([
                                                'id' => $booking->id,
                                                'name' => $booking->name,
                                                'session_date' => $booking->session_date?->format('Y-m-d'),
                                                'session_time' => $booking->session_time,
                                                'country_code' => $booking->country_code,
                                                'phone' => $booking->phone,
                                                'currency' => $booking->currency,
                                                'amount' => $booking->amount,
                                                'booking_number' => $booking->booking_number,
                                            ]))"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6.75 17.25 9" />
                                            </svg>
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-sharm-mist transition hover:bg-sharm-sand hover:text-sharm-deep"
                                        title="Notes"
                                        @click="openNotesModal({{ $booking->id }}, @js($booking->name))"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 10.5h7.5m-7.5 3.75h4.5m-8.25 6 2.25-2.25h11.25a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H5.25A1.5 1.5 0 0 0 3.75 6v14.25Z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-16 text-center text-sm text-sharm-mist">
                                No bookings yet — tourist requests appear here automatically, or record one with “New Booking”.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit admin booking modal --}}
    <div
        x-show="formModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="closeFormModal()"
        @keydown.escape.window="closeFormModal()"
    >
        <div class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl">
            <div class="shrink-0">
                <h2 class="text-lg font-semibold text-sharm-deep" x-text="editingBooking ? 'Edit Booking ' + editingBooking.booking_number : 'New Booking'"></h2>
                <p class="mt-1 text-xs text-sharm-mist">
                    For ad-hoc sessions you handled outside the agency schedule. The booking number is generated automatically and the booking is saved as <strong>Confirmed</strong>.
                </p>
            </div>

            <form @submit.prevent="submitForm()" class="mt-5 flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1">
                    <div>
                        <label class="admin-label">Customer name <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.name" class="admin-input" placeholder="e.g. Anna Petrova">
                        <p class="mt-1 text-xs text-red-500" x-show="errors.name" x-text="errors.name?.[0]"></p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="admin-label">Session date <span class="text-red-400">*</span></label>
                            <input type="date" x-model="form.session_date" max="{{ now()->format('Y-m-d') }}" class="admin-input">
                            <p class="mt-1 text-[10px] text-sharm-mist">Finished sessions only — today or earlier.</p>
                            <p class="mt-1 text-xs text-red-500" x-show="errors.session_date" x-text="errors.session_date?.[0]"></p>
                        </div>
                        <div>
                            <label class="admin-label">Session time <span class="text-red-400">*</span></label>
                            <input type="time" x-model="form.session_time" class="admin-input">
                            <p class="mt-1 text-[10px] text-sharm-mist">Any time — AM/PM shown as <span x-text="form.session_time ? formatTime(form.session_time) : '—'"></span></p>
                            <p class="mt-1 text-xs text-red-500" x-show="errors.session_time" x-text="errors.session_time?.[0]"></p>
                        </div>
                    </div>

                    <div>
                        <label class="admin-label">Phone <span class="text-red-400">*</span></label>
                        <div class="flex gap-2">
                            <select x-model="form.country_code" class="admin-input w-2/5 shrink-0">
                                @foreach ($dialCodes as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="tel" x-model="form.phone" class="admin-input" placeholder="1234567890">
                        </div>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.country_code" x-text="errors.country_code?.[0]"></p>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.phone" x-text="errors.phone?.[0]"></p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="admin-label">Currency <span class="text-red-400">*</span></label>
                            <select x-model="form.currency" class="admin-input">
                                @foreach ($currencies as $code => $meta)
                                    <option value="{{ $code }}">{{ $meta['label'] }} ({{ $meta['symbol'] }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-red-500" x-show="errors.currency" x-text="errors.currency?.[0]"></p>
                        </div>
                        <div>
                            <label class="admin-label">Amount collected <span class="text-red-400">*</span></label>
                            <input type="number" step="0.01" min="0.01" x-model="form.amount" class="admin-input" placeholder="0.00">
                            <p class="mt-1 text-xs text-red-500" x-show="errors.amount" x-text="errors.amount?.[0]"></p>
                        </div>
                    </div>

                    <div>
                        <label class="admin-label">Internal notes <span class="text-sharm-mist/70">(optional)</span></label>
                        <textarea x-model="form.notes" class="admin-input min-h-[80px]" rows="3" placeholder="Anything worth remembering about this session..."></textarea>
                        <p class="mt-1 text-xs text-red-500" x-show="errors.notes" x-text="errors.notes?.[0]"></p>
                    </div>
                </div>

                <div class="mt-4 flex shrink-0 items-center justify-end gap-3 border-t border-sharm-deep/8 pt-4">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-sharm-mist transition hover:bg-sharm-sand" @click="closeFormModal()">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="submitting">
                        <span x-text="editingBooking ? 'Save Changes' : 'Record Booking'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Notes Modal --}}
    <div
        x-show="notesTarget"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-sharm-deep/40 p-4 backdrop-blur-sm"
        @click.self="notesTarget = null"
    >
        <div class="w-full max-w-md rounded-2xl border border-sharm-deep/10 bg-white p-6 shadow-xl">
            <h3 class="text-sm font-semibold text-sharm-deep">Internal Notes</h3>
            <p class="mt-1 text-xs text-sharm-mist" x-text="notesTarget ? 'Booking #' + notesTarget.id + ' — ' + notesTarget.name : ''"></p>
            <textarea
                x-model="notesText"
                class="admin-input mt-4 min-h-[120px]"
                placeholder="Add private notes about this booking..."
            ></textarea>
            <p class="mt-1 text-xs text-red-500" x-show="notesError" x-text="notesError"></p>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" class="rounded-lg px-4 py-2.5 text-sm text-sharm-mist hover:bg-sharm-sand" @click="notesTarget = null">Cancel</button>
                <button type="button" class="btn-primary" @click="saveNotes()" :disabled="submitting">Save Notes</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bookingManager() {
    return {
        // Kept in sync after every save so reopening a modal shows what is stored.
        notesById: @json($bookings->pluck('notes', 'id')),
        notesTarget: null,
        notesText: '',
        notesError: '',
        formModalOpen: false,
        editingBooking: null,
        submitting: false,
        errors: {},
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        currencies: @json(array_keys($currencies)),
        dialCodes: @json(array_keys($dialCodes)),
        today: @json(now()->format('Y-m-d')),
        form: {},

        // Mirrors App\Http\Requests\Admin\StoreAdminBookingRequest::messages()
        messages: {
            name_required: 'Customer name is required.',
            name_min: 'Customer name must be at least 2 characters.',
            date_required: 'Session date is required.',
            date_future: 'Session date cannot be in the future.',
            time_required: 'Session time is required.',
            time_invalid: 'Please enter a valid time.',
            country_code_required: 'Please select a country code.',
            country_code_invalid: 'Please select a valid country code.',
            phone_required: 'Phone number is required.',
            phone_invalid: 'Please enter a valid phone number.',
            currency_required: 'Please select a currency.',
            currency_invalid: 'Please select a valid currency.',
            amount_required: 'Amount is required.',
            amount_numeric: 'Amount must be a number.',
            amount_min: 'Amount must be greater than zero.',
            amount_decimals: 'Amount can have at most 2 decimal places.',
            notes_max: 'Notes cannot exceed 2000 characters.',
        },

        init() {
            this.form = this.emptyForm()
        },

        emptyForm() {
            return {
                name: '',
                session_date: this.today,
                session_time: '',
                country_code: this.dialCodes[0] || '+20',
                phone: '',
                currency: this.currencies[0] || 'USD',
                amount: '',
                notes: '',
            }
        },

        formatTime(value) {
            const [h, m] = String(value).split(':').map(Number)
            if (Number.isNaN(h) || Number.isNaN(m)) return '—'
            const period = h >= 12 ? 'PM' : 'AM'
            const hour12 = h % 12 === 0 ? 12 : h % 12
            return String(hour12).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + period
        },

        openCreateModal() {
            this.editingBooking = null
            this.form = this.emptyForm()
            this.errors = {}
            this.formModalOpen = true
        },

        openEditModal(booking) {
            this.editingBooking = booking
            this.form = {
                name: booking.name || '',
                session_date: booking.session_date || '',
                session_time: booking.session_time || '',
                country_code: booking.country_code || this.dialCodes[0],
                phone: booking.phone || '',
                currency: booking.currency || this.currencies[0],
                amount: booking.amount ?? '',
                notes: this.notesById[booking.id] || '',
            }
            this.errors = {}
            this.formModalOpen = true
        },

        closeFormModal() {
            this.formModalOpen = false
            this.editingBooking = null
            this.errors = {}
        },

        validateForm() {
            this.errors = {}
            const m = this.messages

            const name = (this.form.name || '').trim()
            if (!name) {
                this.errors.name = [m.name_required]
            } else if (name.length < 2) {
                this.errors.name = [m.name_min]
            }

            if (!this.form.session_date) {
                this.errors.session_date = [m.date_required]
            } else if (this.form.session_date > this.today) {
                this.errors.session_date = [m.date_future]
            }

            if (!this.form.session_time) {
                this.errors.session_time = [m.time_required]
            } else if (!/^([01]\d|2[0-3]):[0-5]\d$/.test(this.form.session_time)) {
                this.errors.session_time = [m.time_invalid]
            }

            if (!this.form.country_code) {
                this.errors.country_code = [m.country_code_required]
            } else if (!this.dialCodes.includes(this.form.country_code)) {
                this.errors.country_code = [m.country_code_invalid]
            }

            const phone = (this.form.phone || '').replace(/[\s-]+/g, '')
            if (!phone) {
                this.errors.phone = [m.phone_required]
            } else if (!/^[0-9]+$/.test(phone) || phone.length < 6 || phone.length > 20) {
                this.errors.phone = [m.phone_invalid]
            }

            if (!this.form.currency) {
                this.errors.currency = [m.currency_required]
            } else if (!this.currencies.includes(this.form.currency)) {
                this.errors.currency = [m.currency_invalid]
            }

            const amountRaw = String(this.form.amount ?? '').trim()
            if (!amountRaw) {
                this.errors.amount = [m.amount_required]
            } else if (!/^\d+(\.\d{1,2})?$/.test(amountRaw)) {
                this.errors.amount = [/^\d*\.?\d+$/.test(amountRaw) ? m.amount_decimals : m.amount_numeric]
            } else if (parseFloat(amountRaw) < 0.01) {
                this.errors.amount = [m.amount_min]
            } else if (parseFloat(amountRaw) > 99999999.99) {
                this.errors.amount = [m.amount_numeric]
            }

            if ((this.form.notes || '').length > 2000) {
                this.errors.notes = [m.notes_max]
            }

            return Object.keys(this.errors).length === 0
        },

        async submitForm() {
            if (!this.validateForm()) return

            this.submitting = true
            const url = this.editingBooking
                ? `{{ url('admin/bookings') }}/${this.editingBooking.id}`
                : `{{ route('admin.bookings.store') }}`

            try {
                const res = await fetch(url, {
                    method: this.editingBooking ? 'PUT' : 'POST',
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
                    window.toastFromResponse(data, 'Failed to save the booking.')
                    return
                }

                sessionStorage.setItem('bookingFlash', data.message)
                window.location.reload()
            } catch (e) {
                window.showToast('error', 'Network error. Please try again.')
            } finally {
                this.submitting = false
            }
        },

        openNotesModal(id, name) {
            this.notesTarget = { id, name }
            this.notesText = this.notesById[id] || ''
            this.notesError = ''
        },

        async saveNotes() {
            if (!this.notesTarget) return
            this.submitting = true
            this.notesError = ''
            const id = this.notesTarget.id
            try {
                const res = await fetch(`{{ url('admin/bookings') }}/${id}/notes`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ notes: this.notesText }),
                })
                const data = await res.json()
                if (res.ok) {
                    this.notesById[id] = data.booking.notes
                    window.showToast('success', data.message)
                    this.notesTarget = null
                } else {
                    this.notesError = data.errors?.notes?.[0] || ''
                    window.toastFromResponse(data)
                }
            } catch (e) {
                window.showToast('error', 'Network error.')
            } finally {
                this.submitting = false
            }
        },
    }
}

document.addEventListener('DOMContentLoaded', () => {
    @if (session('success'))
        window.showToast('success', @json(session('success')));
    @endif

    const flash = sessionStorage.getItem('bookingFlash')
    if (flash) {
        sessionStorage.removeItem('bookingFlash')
        window.showToast('success', flash)
    }
});
</script>
@endpush
