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
                        minDate: 'today',
                        disableMobile: true,
                        allowInput: false,
                        onChange: (selectedDates, dateStr) => {
                            this.form.session_date = dateStr
                            this.ensureValidSlot()
                        },
                    })
                },

                availableSlots() {
                    const slots = this.packagesSlots[String(this.form.package_id)] || []
                    if (!this.form.session_date) {
                        return slots
                    }

                    const today = new Date()
                    const todayStr = today.getFullYear() + '-' +
                        String(today.getMonth() + 1).padStart(2, '0') + '-' +
                        String(today.getDate()).padStart(2, '0')

                    if (this.form.session_date !== todayStr) {
                        return slots
                    }

                    const nowMinutes = today.getHours() * 60 + today.getMinutes()

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
                    if (packageId) {
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
                    } else if (name.length < 2) {
                        this.errors.name = [this.messages.name_min]
                        valid = false
                    }

                    const phoneInput = this.getActivePhoneInput()
                    const phoneRaw = phoneInput?.value?.replace(/[^\d\s\-]/g, '').trim() || ''
                    const phoneDigits = phoneRaw.replace(/\D/g, '')
                    const activeIti = this.getActiveIti()

                    if (!phoneRaw) {
                        this.errors.phone = [this.messages.phone_required]
                        valid = false
                    } else if (phoneDigits.length < 6) {
                        this.errors.phone = [this.messages.phone_invalid]
                        valid = false
                    } else if (activeIti && typeof window.intlTelInputUtils !== 'undefined' && !activeIti.isValidNumber()) {
                        this.errors.phone = [this.messages.phone_invalid]
                        valid = false
                    }

                    const email = this.form.email.trim()
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        this.errors.email = [this.messages.email_invalid]
                        valid = false
                    }

                    if (!this.form.package_id) {
                        this.errors.package_id = [this.messages.package_required]
                        valid = false
                    }

                    if (!this.form.session_date) {
                        this.errors.session_date = [this.messages.date_required]
                        valid = false
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

                async submit() {
                    if (!this.validate()) {
                        return
                    }

                    this.loading = true
                    this.errors = {}

                    const phoneInput = this.getActivePhoneInput()
                    const activeIti = this.getActiveIti()

                    if (phoneInput) {
                        this.form.phone = phoneInput.value.replace(/[^\d\s\-]/g, '').trim()
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
