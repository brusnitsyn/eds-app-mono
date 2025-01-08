let lastCall, lastCallTimer = null
export const debounce = (callee, timeoutMs) => {
    let previousCall = lastCall

    lastCall = Date.now()

    if (previousCall && lastCall - previousCall <= timeoutMs) {
        clearTimeout(lastCallTimer)
    }

    lastCallTimer = setTimeout(() => callee(), timeoutMs)
}
