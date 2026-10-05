const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

function profile() {
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/profile.blade.php'), 'utf8')
    const expression = blade.match(/x-data="([\s\S]*?)"\s*>/)[1].replace(/@js\((.*)\)/g, (match, value) => value.includes('hashing.driver') ? '72' : JSON.stringify('test-value'))
    const toasts = []
    const requests = []
    const context = {
        window: { location: { search: '' }, showToast: (type, message) => toasts.push(message) },
        URLSearchParams, TextEncoder,
        document: { querySelector: () => ({ content: 'test-csrf' }) },
        FormData: class { append() {} },
        fetch: async (...args) => { requests.push(args); throw new Error('Simulated network failure') },
    }
    vm.createContext(context)
    const state = vm.runInContext(`(${expression})`, context)
    state.$refs = { photoInput: { files: [] } }
    return { state, toasts, requests }
}

test('profile password byte limits reject oversized Unicode before a request', async () => {
    const { state, toasts, requests } = profile()
    state.passForm.password = 'é'.repeat(37) + 'A1!'
    await state.submitPassword()
    assert.equal(requests.length, 0)
    assert.ok(state.passErrors.password[0].includes('72'))
    assert.equal(toasts.length, 1)
})

test('profile forms do not submit a second in-flight request', async () => {
    const { state, requests } = profile()
    state.infoLoading = true
    state.passLoading = true
    await state.submitInfo()
    await state.submitPassword()
    assert.equal(requests.length, 0)
})

test('profile network failures restore loading and show English feedback', async () => {
    const { state, toasts } = profile()
    await state.submitInfo()
    await state.submitPassword()
    assert.equal(state.infoLoading, false)
    assert.equal(state.passLoading, false)
    assert.equal(toasts.length, 2)
    assert.ok(toasts.every(message => message.includes('Network error')))
})
