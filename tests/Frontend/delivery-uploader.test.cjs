const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

function uploader(count = 0) {
    const script = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/bookings/show.blade.php'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1]
    const warnings = []
    const context = {
        document: { querySelector: () => ({ content: 'test-csrf' }) },
        URL: { createObjectURL: () => 'blob:test', revokeObjectURL() {} },
        window: { showToast: (type, message) => warnings.push(message) },
    }
    vm.createContext(context)
    vm.runInContext(script, context)
    const manager = context.deliveryManager({ photos: Array.from({ length: count }, (_, id) => ({ id })), storage: {}, limits: { maxPerDrop: 10, maxTotal: 50, maxBytes: 10 * 1024 * 1024 } })
    return { manager, warnings }
}

function files(count) { return Array.from({ length: count }, () => ({ name: 'test.jpg', type: 'image/jpeg', size: 10 })) }

test('existing photos and pending files share the 50-photo capacity', () => {
    const { manager } = uploader(49)
    manager.addFiles(files(3))
    assert.equal(manager.queue.length, 1)
    assert.equal(manager.remainingSlots, 0)
})

test('a completed queue item is not counted twice after server success', () => {
    const { manager } = uploader(48)
    manager.addFiles(files(1))
    manager.queue[0].status = 'done'
    manager.photos.push({ id: 49 })
    assert.equal(manager.remainingSlots, 1)
})

test('legacy over-cap deliveries are retained but cannot queue new uploads', () => {
    const { manager } = uploader(51)
    manager.addFiles(files(1))
    assert.equal(manager.photos.length, 51)
    assert.equal(manager.queue.length, 0)
})

test('dropping during an upload cannot silently lose the new queue', () => {
    const { manager, warnings } = uploader()
    manager.uploading = true
    manager.addFiles(files(1))
    assert.equal(manager.queue.length, 0)
    assert.equal(warnings.length, 1)
})
