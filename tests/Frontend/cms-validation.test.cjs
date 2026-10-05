const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

function manager(view, name) {
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/admin', view, 'index.blade.php'), 'utf8')
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/@json\((.*)\)/g, '[]').replace(/\{\{.*?\}\}/g, '/admin/test')
    const toasts = []
    const requests = []
    const context = {
        document: { querySelector: () => ({ content: 'test-csrf' }) }, FormData, URL,
        window: {
            toastFromResponse: data => toasts.push(Object.values(data.errors || {}).flat()[0]),
            showToast: (type, message) => toasts.push(message),
            readJsonResponse: response => response.json(),
        },
        fetch: async (url, options) => { requests.push({ url, options }); throw new Error('Unexpected request') },
    }
    vm.createContext(context)
    vm.runInContext(script, context)
    return { manager: context[name](), toasts, requests, context }
}

test('symbol-only album names give field errors and a toast before a request', async () => {
    const { manager: album, toasts, requests } = manager('gallery', 'albumManager')
    Object.keys(album.form).forEach(key => { album.form[key] = '!!!' })
    await album.submitModal()
    assert.ok(album.errors.name_en)
    assert.equal(requests.length, 0)
    assert.equal(toasts.length, 1)
    assert.equal(album.submitting, false)
})

test('album name validation accepts normal punctuation and Unicode', () => {
    const { manager: album } = manager('gallery', 'albumManager')
    Object.keys(album.form).forEach(key => { album.form[key] = 'Été & Family’s Trip – 2026' })
    assert.equal(album.validateForm(), true)
    album.form.name_en = 'Beach/Trip'
    assert.equal(album.validateForm(), false)
})

test('an album rename updates the cover URL without a reload', async () => {
    const { manager: album, context } = manager('gallery', 'albumManager')
    const previous = { id: 1, slug: 'old-name', cover_url: '/storage/albums/old-name/cover.webp' }
    Object.keys(album.form).forEach(key => { previous[key] = 'Old Name' })
    album.albums = [previous]
    album.openEditModal(previous)
    album.form.name_en = 'New Name'
    context.fetch = async () => ({ ok: true, json: async () => ({
        success: true, message: 'Album updated.',
        album: { ...previous, name_en: 'New Name', slug: 'new-name', cover_url: '/storage/albums/new-name/cover.webp' },
    }) })
    await album.submitModal()
    assert.equal(album.albums[0].cover_url, '/storage/albums/new-name/cover.webp')
    assert.equal(album.modalOpen, false)
})

test('invalid package duration cannot reach the API', async () => {
    const { manager: packages, toasts, requests } = manager('packages', 'packageManager')
    packages.form.hours = '2.05'
    await packages.submitModal()
    assert.ok(packages.errors.hours)
    assert.equal(requests.length, 0)
    assert.equal(toasts.length, 1)
})

test('half-hour duration validation accepts canonical equivalent values', () => {
    const { manager: packages } = manager('packages', 'packageManager')
    for (const hours of ['0.5', '2', '2.50', '24']) {
        packages.form.hours = hours
        assert.equal(packages.validateHours(), true)
    }
    for (const hours of ['', '2.05', '-1', '25', 'abc']) {
        packages.form.hours = hours
        assert.equal(packages.validateHours(), false)
    }
})
