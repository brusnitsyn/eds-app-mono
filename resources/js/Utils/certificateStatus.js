const STATUS_DEFS = {
    valid: { label: 'Действителен', type: 'success' },
    expiring: { label: 'Истекает', type: 'warning' },
    expired: { label: 'Истёк', type: 'error' },
    revoked: { label: 'Отозван', type: 'default' },
}

export function certificateStatusDef(status) {
    return STATUS_DEFS[status] ?? STATUS_DEFS.expired
}

export function certificateDaysLeft(validToMs) {
    if (!validToMs) return null
    return Math.ceil((Number(validToMs) - Date.now()) / 86400000)
}

function pluralizeDays(n) {
    const mod100 = Math.abs(n) % 100
    const mod10 = mod100 % 10
    if (mod100 > 10 && mod100 < 20) return 'дней'
    if (mod10 === 1) return 'день'
    if (mod10 >= 2 && mod10 <= 4) return 'дня'
    return 'дней'
}

export function certificateDaysLabel(certificate) {
    if (certificate.status === 'revoked') return 'Отозван'

    const days = certificateDaysLeft(certificate.valid_to)
    if (days === null) return ''
    if (days < 0) {
        const abs = Math.abs(days)
        return `Истёк ${abs} ${pluralizeDays(abs)} назад`
    }
    return `Осталось ${days} ${pluralizeDays(days)}`
}

export function staffInitials(fullName) {
    const parts = (fullName ?? '').trim().split(/\s+/)
    return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase()
}

const AVATAR_COLORS = ['#2080f0', '#18a058', '#f0a020', '#8a2be2', '#d03050', '#0891b2', '#db2777', '#ca8a04']

export function avatarColor(seed) {
    const n = Number(seed) || 0
    return AVATAR_COLORS[n % AVATAR_COLORS.length]
}
