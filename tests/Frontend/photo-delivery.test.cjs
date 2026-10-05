const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/my-photos.blade.php'), 'utf8')
function page() {
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/photos:\s*\{\{[\s\S]*?\}\},/, 'photos: [{src:"preview-1",download_url:"attachment-1"},{src:"preview-2",download_url:"attachment-2"}],')
        .replace(/@json\((.*)\)/g, (match, expression) => JSON.stringify(expression.includes('localized_url') ? '/ru/my-photos/download-all' : expression))
    const toasts = []
    const context = {
        window: { showToast: (type, message) => toasts.push(message), toastFromResponse: data => toasts.push(data.message), readJsonResponse: response => response.json() },
        document: { querySelector: () => ({ content: 'test-csrf' }), body: { style: {} } },
        fetch: async () => ({ ok: false, json: async () => ({ message: 'Download is unavailable.' }) }),
        setTimeout: () => {},
    }
    vm.createContext(context)
    vm.runInContext(script, context)
    return { state: context.window.myPhotosPage(), context, toasts }
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

test('failed ZIP download restores the loader and shows an error toast', async () => {
    const { state, toasts } = page()
    await state.downloadAll()
    assert.equal(state.downloading, false)
    assert.equal(toasts[0], 'Download is unavailable.')
})
