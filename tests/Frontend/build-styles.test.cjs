const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

test('compiled CSS includes the status badge classes supplied by PHP models', () => {
    const css = fs.readFileSync(path.join(__dirname, '../../public/css/app.css'), 'utf8')
    for (const model of ['Booking', 'Review']) {
        const php = fs.readFileSync(path.join(__dirname, `../../app/Models/${model}.php`), 'utf8')
        const classes = php.match(/(?:bg|text)-(?:blue|amber|emerald|red)-\d+/g)
        assert.ok(classes?.length)
        for (const value of new Set(classes)) assert.ok(css.includes(`.${value}{`), `${model} is missing ${value}`)
    }
})
