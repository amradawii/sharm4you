const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const messages = {
    name_required: 'Please enter your name.', name_min: 'Name must be at least 2 characters.',
    name_max: 'Name must not exceed 255 characters.', phone_required: 'Please enter your phone number.',
    phone_invalid: 'Please enter a valid phone number.', email_invalid: 'Please enter a valid email address.',
    email_max: 'Email must not exceed 255 characters.', country_code_invalid: 'Please select a valid country code.',
    package_required: 'Please select a package.', date_required: 'Please select a date.',
    date_invalid: 'Please select today or a future date.', time_required: 'Please select an available time.',
    timezone_label: 'Sharm El Sheikh local time', network_error: 'Network error. Please try again.',
}

function component() {
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/booking-form-scripts.blade.php'), 'utf8')
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1].replace(/@(?:json|js)\((.*)\)/g, (match, expression) => {
        if (expression.includes('site.booking.validation')) return JSON.stringify(messages)
        if (expression.includes('booking.validation')) return JSON.stringify({ name_min: 2, name_max: 255, phone_min: 6, phone_max: 15, email_max: 255 })
        if (expression.includes('public_dial_codes')) return JSON.stringify(['+20', '+1', '+7'])
        if (expression.includes('timezone')) return JSON.stringify('Africa/Cairo')
        if (expression.includes('bookSessionUrl')) return JSON.stringify('/book-session')
        throw new Error('Unhandled Blade expression: ' + expression)
    })
    const toasts = []
    const requests = []
    const context = {
        console, Intl, Date, setTimeout, clearTimeout,
        document: { querySelector: () => ({ content: 'test-csrf' }), body: { classList: { add() {}, remove() {} } } },
        window: {
            location: { hash: '' },
            readJsonResponse: response => response.json(),
            showToast: (type, message) => toasts.push({ type, message }),
            toastFromResponse: data => toasts.push({ type: 'error', message: Object.values(data.errors || {}).flat()[0] || data.message }),
        },
        fetch: async (url, options) => {
            requests.push({ url, options })
            return { ok: true, status: 200, json: async () => ({ success: true, message: 'Saved' }) }
        },
    }
    vm.createContext(context)
    vm.runInContext(script, context)
    const form = context.bookingForm({ 1: ['10:00', '17:30'] })
    form.$refs = { phoneInput: { value: '', focus() {}, scrollIntoView() {} }, bookingForm: { querySelector: () => null } }
    form.$nextTick = callback => callback()
    return { form, toasts, requests, context }
}

function freeze(context, iso) {
    context.Date = class extends Date {
        constructor(...args) { super(...(args.length ? args : [iso])) }
    }
}

test('Cairo slots ignore the device timezone and respect summer time', () => {
    const { form, context } = component()
    freeze(context, '2026-10-03T12:00:00Z')
    assert.equal(form.businessNow().date, '2026-10-03')
    assert.equal(form.businessNow().minutes, 15 * 60)
    form.form.package_id = '1'
    form.form.session_date = '2026-10-03'
    assert.deepEqual(Array.from(form.availableSlots()), ['17:30'])
    freeze(context, '2026-10-03T21:30:00Z')
    assert.equal(form.businessNow().date, '2026-10-04')
    freeze(context, '2026-01-03T12:00:00Z')
    assert.equal(form.businessNow().minutes, 14 * 60)
})

test('a header event without a package does not select an object as the id', () => {
    const { form } = component()
    form.openModal({})
    assert.equal(form.form.package_id, '')
    form.openModal(1)
    assert.equal(form.form.package_id, '1')
})

test('in-flight submissions cannot create a second request', async () => {
    const { form, requests } = component()
    form.loading = true
    await form.submit()
    assert.equal(requests.length, 0)
})

test('empty submit reports inline errors and a toast without a request', async () => {
    const { form, toasts, requests } = component()
    await form.submit()
    assert.deepEqual(Object.keys(form.errors).sort(), ['name', 'package_id', 'phone', 'session_date', 'session_time'])
    assert.equal(toasts.length, 1)
    assert.equal(toasts[0].message, messages.name_required)
    assert.equal(requests.length, 0)
    assert.equal(form.loading, false)
})

test('validation rejects meaningless and overlong contact fields', () => {
    const { form } = component()
    form.form.name = 'A'.repeat(256)
    form.form.email = 'a'.repeat(256) + '@example.com'
    form.$refs.phoneInput.value = '---'
    assert.equal(form.validate(), false)
    assert.ok(form.errors.name)
    assert.ok(form.errors.email)
    assert.ok(form.errors.phone)
})

test('a network failure restores the button and shows feedback', async () => {
    const { form, context, toasts } = component()
    form.form = { name: 'Test Tourist', phone: '', country_code: '+20', email: '', package_id: '1', session_date: '2099-01-01', session_time: '17:30' }
    form.$refs.phoneInput.value = '1002003000'
    context.fetch = async () => { throw new Error('Connection failed') }
    await form.submit()
    assert.equal(form.loading, false)
    assert.equal(toasts[0].message, messages.network_error)
})

test('valid submission sends one normalized booking request', async () => {
    const { form, requests } = component()
    form.form = { name: 'Test Tourist', phone: '', country_code: '+20', email: '', package_id: '1', session_date: '2099-01-01', session_time: '17:30' }
    form.$refs.phoneInput.value = '100 200-3000'
    form.showSuccessMessage = () => {}
    await form.submit()
    assert.equal(requests.length, 1)
    assert.equal(JSON.parse(requests[0].options.body).phone, '1002003000')
    assert.equal(form.loading, false)
})
