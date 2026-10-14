const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const zlib = require('node:zlib')

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/my-photos.blade.php'), 'utf8')
function page({ photos, fetch } = {}) {
    photos = photos || [{ src: 'preview-1', download_url: 'attachment-1', filename: 'a.webp' }, { src: 'preview-2', download_url: 'attachment-2', filename: 'b.webp' }]
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/photos:\s*\{\{[\s\S]*?\}\},/, 'photos: ' + JSON.stringify(photos) + ',')
        .replace(/@json\((.*)\)/g, (match, expression) => JSON.stringify(expression.includes('download_progress') ? 'Downloading :percent%' : expression))
    const toasts = []
    const links = []
    const blobs = []
    const context = {
        window: { showToast: (type, message) => toasts.push(message) },
        document: {
            body: { style: {}, appendChild: () => {}, removeChild: () => {} },
            createElement: () => { const a = { click: () => links.push({ href: a.href, download: a.download }) }; return a },
        },
        URL: { createObjectURL: blob => { blobs.push(blob); return 'blob:' + blobs.length }, revokeObjectURL: () => {} },
        Blob,
        TextEncoder,
        fetch: fetch || (async () => ({ ok: false, status: 403 })),
        setTimeout: () => {},
    }
    vm.createContext(context)
    vm.runInContext(script, context)
    return { state: context.window.myPhotosPage(), context, toasts, links, blobs }
}

function readZip(buffer) {
    const view = new DataView(buffer.buffer, buffer.byteOffset, buffer.byteLength)
    const end = buffer.byteLength - 22
    assert.equal(view.getUint32(end, true), 0x06054b50)
    const count = view.getUint16(end + 10, true)
    let at = view.getUint32(end + 16, true)
    const entries = []
    for (let i = 0; i < count; i++) {
        assert.equal(view.getUint32(at, true), 0x02014b50)
        assert.equal(view.getUint16(at + 10, true), 0)
        const crc = view.getUint32(at + 16, true)
        const size = view.getUint32(at + 24, true)
        const nameLength = view.getUint16(at + 28, true)
        const offset = view.getUint32(at + 42, true)
        const name = buffer.subarray(at + 46, at + 46 + nameLength).toString('utf8')
        assert.equal(view.getUint32(offset, true), 0x04034b50)
        const start = offset + 30 + view.getUint16(offset + 26, true)
        const data = buffer.subarray(start, start + size)
        assert.equal(zlib.crc32(data), crc)
        entries.push({ name, data: data.toString('utf8') })
        at += 46 + nameLength
    }
    return entries
}

test('lightbox next/previous wrap and closing restores scrolling', () => {
    const { state, context } = page()
    state.open(0)
    assert.equal(context.document.body.style.overflow, 'hidden')
    state.prev()
    assert.equal(state.current, 1)
    state.next()
    assert.equal(state.current, 0)
    state.close()
    assert.equal(context.document.body.style.overflow, '')
})

test('mobile navigation buttons are not hidden below desktop widths', () => {
    for (const action of ['prev()', 'next()']) {
        const button = blade.match(new RegExp('<button[\\s\\S]*?class="([^"\\n]*)"\\s+@click="' + action.replace(/[()]/g, '\\$&') + '"'))
        assert.ok(button)
        assert.equal(button[1].split(/\s+/).includes('hidden'), false)
        assert.equal(button[1].includes('h-11'), true)
    }
})

test('failed photo fetch restores the button and shows an error toast without saving a ZIP', async () => {
    const { state, toasts, links } = page()
    await state.downloadAll()
    assert.equal(state.downloading, false)
    assert.match(toasts[0], /zip_failed/)
    assert.equal(links.length, 0)
})

test('download all fetches every photo and saves an ordered, valid ZIP built in the browser', async () => {
    const photos = Array.from({ length: 140 }, (_, i) => ({ src: 'p' + i, download_url: 'https://r2.test/' + i, filename: i === 0 ? 'Été.webp' : 'photo-' + i + '.webp' }))
    const fetched = []
    const labels = []
    const fetch = async url => {
        fetched.push(url)
        await new Promise(resolve => setImmediate(resolve))
        return { ok: true, arrayBuffer: async () => new TextEncoder().encode('data-' + url).buffer }
    }
    const { state, links, blobs } = page({ photos, fetch })
    let label = ''
    Object.defineProperty(state, 'downloadLabel', { get: () => label, set: value => { label = value; labels.push(value) } })
    await state.downloadAll()

    assert.equal(fetched.length, 140)
    assert.equal(links.length, 1)
    assert.equal(links[0].href, 'blob:1')
    assert.match(links[0].download, /-photos\.zip$/)
    assert.equal(labels[0], 'Downloading 0%')
    assert.ok(labels.includes('Downloading 50%'))
    assert.ok(labels.includes('Downloading 100%'))
    assert.equal(blobs[0].type, 'application/zip')

    const entries = readZip(Buffer.from(await blobs[0].arrayBuffer()))
    assert.equal(entries.length, 140)
    assert.deepEqual(entries[0], { name: '001-Été.webp', data: 'data-https://r2.test/0' })
    assert.deepEqual(entries[139], { name: '140-photo-139.webp', data: 'data-https://r2.test/139' })
})
