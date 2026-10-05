@php
    $bookSessionUrl = current_locale() === 'en'
        ? route('book-session')
        : localized_url(current_locale(), 'book-session');
@endphp

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script>
        function bookingForm(packagesSlots = {}) {
            return {
                form: { name: '', phone: '', country_code: '+20', email: '', package_id: '', session_date: '', session_time: '' },
                packagesSlots,
                iti: null,
                itiModal: null,
                datePicker: null,
                datePickerModal: null,
                modalOpen: false,
                loading: false,
                errors: {},
                success: false,
                successTimer: null,
                csrf: document.querySelector('meta[name="csrf-token"]').content,
                submitUrl: @json($bookSessionUrl),
                messages: @json(__('site.booking.validation')),
                limits: @json(config('booking.validation')),
                timezone: @json(config('booking.timezone')),
                dialCodes: @json(config('countries.public_dial_codes')),

                init() {
                    this.$nextTick(() => {
                        const input = this.$refs.phoneInput
                        if (input) {
                            this.iti = this.createIti(input)
                        }
                        if (this.$refs.dateInput) {
                            this.datePicker = this.createDatePicker(this.$refs.dateInput)
                        }
                    })

                    const hash = window.location.hash
                    if (hash.startsWith('#book-session-')) {
                        this.form.package_id = hash.replace('#book-session-', '')
                    }

                    this.$watch('form.package_id', () => this.ensureValidSlot())
                },

                createIti(input) {
                    if (!input || typeof window.intlTelInput === 'undefined') return null

                    const instance = window.intlTelInput(input, {
                        initialCountry: 'eg',
                        preferredCountries: ['eg', 'sa', 'ae', 'kw', 'qa', 'bh', 'om', 'jo', 'lb'],
                        separateDialCode: true,
                        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js',
                    })

                    const data = instance.getSelectedCountryData()
                    this.form.country_code = '+' + data.dialCode

                    input.addEventListener('countrychange', () => {
                        const country = instance.getSelectedCountryData()
                        this.form.country_code = '+' + country.dialCode
                    })

                    return instance
                },

                createDatePicker(input) {
                    if (!input || typeof window.flatpickr === 'undefined') return null

                    return window.flatpickr(input, {
                        dateFormat: 'Y-m-d',
                        minDate: this.businessNow().date,
                        disableMobile: true,
                        allowInput: false,
                        onChange: (selectedDates, dateStr) => {
                            this.form.session_date = dateStr
                            this.ensureValidSlot()
                        },
                    })
                },

                businessNow() {
                    const parts = new Intl.DateTimeFormat('en-GB', {
                        timeZone: this.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
                        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
                    }).formatToParts(new Date())
                    const values = Object.fromEntries(parts.map(part => [part.type, part.value]))
                    return {
                        date: `${values.year}-${values.month}-${values.day}`,
                        minutes: Number(values.hour) * 60 + Number(values.minute),
                    }
                },

                availableSlots() {
                    const slots = this.packagesSlots[String(this.form.package_id)] || []
                    if (!this.form.session_date) {
                        return slots
                    }

                    const now = this.businessNow()
                    if (this.form.session_date !== now.date) {
                        return slots
                    }

                    const nowMinutes = now.minutes

                    return slots.filter(slot => {
                        const [h, m] = slot.split(':').map(Number)
                        return h * 60 + m > nowMinutes
                    })
                },

                ensureValidSlot() {
                    if (this.form.session_time && !this.availableSlots().includes(this.form.session_time)) {
                        this.form.session_time = ''
                    }
                },

                formatSlot(slot) {
                    const [h, m] = slot.split(':').map(Number)
                    const period = h >= 12 ? 'PM' : 'AM'
                    const hour12 = h % 12 === 0 ? 12 : h % 12
                    return String(hour12).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + period
                },

                openModal(packageId) {
                    if ((typeof packageId === 'string' || typeof packageId === 'number')
                        && Object.hasOwn(this.packagesSlots, String(packageId))) {
                        this.form.package_id = String(packageId)
                    }
                    this.errors = {}
                    this.success = false
                    this.modalOpen = true
                    document.body.classList.add('overflow-hidden')

                    this.$nextTick(() => {
                        if (!this.itiModal && this.$refs.modalPhoneInput) {
                            this.itiModal = this.createIti(this.$refs.modalPhoneInput)
                        }
                        if (!this.datePickerModal && this.$refs.modalDateInput) {
                            this.datePickerModal = this.createDatePicker(this.$refs.modalDateInput)
                        }
                    })
                },

                closeModal() {
                    this.modalOpen = false
                    document.body.classList.remove('overflow-hidden')
                },

                getActivePhoneInput() {
                    return this.modalOpen ? this.$refs.modalPhoneInput : this.$refs.phoneInput
                },

                getActiveIti() {
                    return this.modalOpen ? this.itiModal : this.iti
                },

                validate() {
                    this.errors = {}
                    let valid = true
                    const name = this.form.name.trim()

                    if (!name) {
                        this.errors.name = [this.messages.name_required]
                        valid = false
                    } else if (Array.from(name).length < this.limits.name_min) {
                        this.errors.name = [this.messages.name_min]
                        valid = false
                    } else if (Array.from(name).length > this.limits.name_max) {
                        this.errors.name = [this.messages.name_max]
                        valid = false
                    }

                    const phoneInput = this.getActivePhoneInput()
                    const phoneRaw = phoneInput?.value?.trim() || ''
                    const phoneDigits = phoneRaw.replace(/[\s\-]+/g, '')
                    const activeIti = this.getActiveIti()
                    if (activeIti) this.form.country_code = '+' + activeIti.getSelectedCountryData().dialCode

                    if (!phoneDigits) {
                        this.errors.phone = [this.messages.phone_required]
                        valid = false
                    } else if (!/^\d+$/.test(phoneDigits) || phoneDigits.length < this.limits.phone_min
                        || phoneDigits.length > this.limits.phone_max
                        || this.form.country_code.replace('+', '').length + phoneDigits.length > 15) {
                        this.errors.phone = [this.messages.phone_invalid]
                        valid = false
                    }
                    if (!this.dialCodes.includes(this.form.country_code)) {
                        this.errors.country_code = [this.messages.country_code_invalid]
                        valid = false
                    }

                    const email = this.form.email.trim()
                    if (email.length > this.limits.email_max || (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))) {
                        this.errors.email = [email.length > this.limits.email_max ? this.messages.email_max : this.messages.email_invalid]
                        valid = false
                    }

                    if (!this.form.package_id || !Object.hasOwn(this.packagesSlots, String(this.form.package_id))) {
                        this.errors.package_id = [this.messages.package_required]
                        valid = false
                    }

                    if (!this.form.session_date) {
                        this.errors.session_date = [this.messages.date_required]
                        valid = false
                    } else {
                        const date = new Date(this.form.session_date + 'T00:00:00Z')
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(this.form.session_date) || Number.isNaN(date.getTime())
                            || date.toISOString().slice(0, 10) !== this.form.session_date || this.form.session_date < this.businessNow().date) {
                            this.errors.session_date = [this.messages.date_invalid]
                            valid = false
                        }
                    }

                    if (!this.form.session_time) {
                        this.errors.session_time = [this.messages.time_required]
                        valid = false
                    } else if (this.form.package_id && this.form.session_date) {
                        const slots = this.availableSlots()
                        if (!slots.includes(this.form.session_time)) {
                            this.errors.session_time = [this.messages.time_required]
                            valid = false
                        }
                    }

                    return valid
                },

                resetForm() {
                    this.form = { name: '', phone: '', country_code: '+20', email: '', package_id: '', session_date: '', session_time: '' }
                    this.errors = {}

                    if (this.$refs.phoneInput) {
                        this.$refs.phoneInput.value = ''
                    }
                    if (this.$refs.modalPhoneInput) {
                        this.$refs.modalPhoneInput.value = ''
                    }
                    if (this.iti) {
                        this.iti.setCountry('eg')
                    }
                    if (this.itiModal) {
                        this.itiModal.setCountry('eg')
                    }
                    const data = this.iti?.getSelectedCountryData() || this.itiModal?.getSelectedCountryData()
                    if (data) {
                        this.form.country_code = '+' + data.dialCode
                    }
                    if (this.datePicker) {
                        this.datePicker.clear()
                    }
                    if (this.datePickerModal) {
                        this.datePickerModal.clear()
                    }
                },

                showSuccessMessage(message) {
                    this.success = true
                    window.showToast('success', message)

                    if (this.successTimer) {
                        clearTimeout(this.successTimer)
                    }

                    const fromModal = this.modalOpen

                    this.successTimer = setTimeout(() => {
                        this.success = false
                        this.resetForm()
                        if (fromModal) {
                            this.closeModal()
                        }
                        this.successTimer = null
                    }, 5000)
                },

                showValidationErrors() {
                    window.toastFromResponse({ errors: this.errors })
                    this.$nextTick(() => {
                        const field = Object.keys(this.errors)[0]
                        const form = this.modalOpen ? this.$refs.modalForm : this.$refs.bookingForm
                        const input = ['phone', 'country_code'].includes(field) ? this.getActivePhoneInput()
                            : form?.querySelector(`[x-model="form.${field}"], [data-booking-field="${field}"]`)
                        input?.focus()
                        input?.scrollIntoView({ block: 'center', behavior: 'smooth' })
                    })
                },

                async submit() {
                    if (this.loading) return
                    if (!this.validate()) {
                        this.showValidationErrors()
                        return
                    }

                    this.loading = true
                    this.errors = {}

                    const phoneInput = this.getActivePhoneInput()
                    const activeIti = this.getActiveIti()

                    if (phoneInput) {
                        this.form.phone = phoneInput.value.replace(/[\s\-]+/g, '')
                    }
                    if (activeIti) {
                        const data = activeIti.getSelectedCountryData()
                        this.form.country_code = '+' + data.dialCode
                    }

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

                        const data = await window.readJsonResponse(res, this.messages.network_error)

                        if (res.status === 429) {
                            window.showToast('error', this.messages.throttled)
                            return
                        }

                        if (!res.ok) {
                            if (data.errors) {
                                this.errors = data.errors
                                this.showValidationErrors()
                            } else {
                                window.toastFromResponse(data, this.messages.network_error)
                            }
                            return
                        }

                        this.showSuccessMessage(data.message)
                    } catch (e) {
                        window.showToast('error', this.messages.network_error)
                    } finally {
                        this.loading = false
                    }
                },
            }
        }
    </script>
@endpush
