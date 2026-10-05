<script>
    (function () {
        const pending = []

        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                items: [],

                add(type, message) {
                    message = String(message ?? '').trim()
                    if (!message) return

                    const id = Date.now() + Math.random()
                    this.items.push({
                        id,
                        type: type || 'info',
                        message,
                        visible: true,
                    })

                    setTimeout(() => this.remove(id), 4500)
                },

                remove(id) {
                    const item = this.items.find(t => t.id === id)
                    if (!item) return

                    item.visible = false

                    setTimeout(() => {
                        this.items = this.items.filter(t => t.id !== id)
                    }, 300)
                },
            })

            pending.splice(0).forEach(({ type, message }) => {
                Alpine.store('toast').add(type, message)
            })
        })

        window.showToast = function (type, message) {
            if (typeof Alpine !== 'undefined' && Alpine.store('toast')) {
                Alpine.store('toast').add(type, message)
                return
            }

            pending.push({ type, message })
        }

        window.readJsonResponse = async function (response, fallback) {
            const parsed = await response.json().catch(() => ({}))
            const data = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {}
            if (response.status >= 500) return { success: false, message: fallback || 'Something went wrong. Please try again.' }
            if (response.status === 419) return { success: false, message: fallback || 'Your session expired. Refresh the page and try again.' }
            if (response.status === 401 && !data.errors) return { success: false, message: fallback || 'Please sign in again to continue.' }
            if (data.warning) window.showToast('warning', data.warning)
            if (!response.ok && !data.message && !data.errors) {
                data.message = fallback || (response.status === 429 ? 'Too many requests. Please wait and try again.' : 'Unable to complete the request. Please try again.')
            }
            return data
        }

        window.toastFromResponse = function (data, fallback) {
            if (data?.errors) {
                const first = Object.values(data.errors).flat().find(Boolean)
                if (first) {
                    window.showToast('error', first)
                    return
                }
            }

            window.showToast('error', data?.message || fallback || 'Something went wrong.')
        }
    })()
</script>
