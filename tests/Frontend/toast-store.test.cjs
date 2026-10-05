const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

function store() {
    const events = {}
    const stores = {}
    const context = {
        window: {}, document: { addEventListener: (name, callback) => { events[name] = callback } },
        Alpine: { store: (name, value) => value ? (stores[name] = value) : stores[name] },
        setTimeout: () => {}, Date, Math,
    }
    vm.createContext(context)
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/alpine-toast-store.blade.php'), 'utf8')
    vm.runInContext(blade.match(/<script>([\s\S]*?)<\/script>/)[1], context)
    events['alpine:init']()
    return { context, toast: stores.toast }
}

test('validation errors are displayed as plain toast messages', () => {
    const { context, toast } = store()
    context.window.toastFromResponse({ errors: { name: ['Username is required.'] } })
    assert.equal(toast.items[0].message, 'Username is required.')
})

test('non-JSON errors have English fallbacks instead of silent failure', async () => {
    const { context } = store()
    for (const status of [401, 419, 429, 500]) {
        const data = await context.window.readJsonResponse({ status, ok: false, json: async () => { throw new Error('HTML response') } })
        assert.ok(data.message)
        assert.equal(data.message.includes('undefined'), false)
    }
})

test('internal exception messages are not exposed in server-error toasts', async () => {
    const { context } = store()
    const data = await context.window.readJsonResponse({ status: 500, ok: false, json: async () => ({ message: 'Sensitive internal diagnostic' }) })
    assert.equal(data.message, 'Something went wrong. Please try again.')
})
