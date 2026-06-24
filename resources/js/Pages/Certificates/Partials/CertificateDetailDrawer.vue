<script setup>
import {ref} from "vue"
import {NDrawer, NDrawerContent, NFlex, NAvatar, NProgress, NList, NListItem, NTime, NButton, NIcon, NPopconfirm, NTooltip} from "naive-ui"
import {IconDownload, IconRefresh, IconBan, IconShieldCheck} from "@tabler/icons-vue"
import {useCheckScope} from "@/Composables/useCheckScope.js"
import {useCadesPlugin} from "@/Composables/useCadesPlugin.js"
import {staffInitials, avatarColor, certificateStatusDef, certificateDaysLabel, certificateDaysLeft} from "@/Utils/certificateStatus.js"
import StatusTag from "./StatusTag.vue"

const show = defineModel("show")

const props = defineProps({
    certificate: Object,
    revoking: Boolean,
    trustedCas: {type: Array, default: () => []},
})

const emit = defineEmits(["revoke", "renew"])

const {hasScope, scopes} = useCheckScope()
const {isAvailable, installToStore} = useCadesPlugin()

const installing = ref(false)

async function installTrustedCas() {
    if (props.trustedCas.length === 0) {
        window.$message?.warning("Список сертификатов УЦ пуст — загрузите их в разделе «Настройки»")
        return
    }

    installing.value = true
    try {
        if (!(await isAvailable())) {
            window.$message?.error("Не обнаружен плагин «КриптоПро ЭЦП Browser plug-in». Установите его и расширение CryptoPro Extension для браузера")
            return
        }

        let installed = 0
        for (const ca of props.trustedCas) {
            try {
                const {data} = await window.axios.get(route("certificates.trusted-ca.content", ca.id))
                await installToStore(data.content, ca.type === "root" ? "Root" : "CA")
                installed++
            } catch (e) {
                window.$message?.error(`Не удалось установить «${ca.name}»: ${e?.message ?? e}`)
            }
        }

        if (installed > 0) {
            window.$message?.success(`Установлено сертификатов УЦ: ${installed} из ${props.trustedCas.length}`)
        }
    } finally {
        installing.value = false
    }
}

function downloadUrl(cert) {
    return route("certification.download", {staff_ids: [cert.staff_id]}, true)
}

function daysColor(status) {
    const type = certificateStatusDef(status).type
    return type === "success" ? "#18a058" : type === "warning" ? "#f0a020" : "#d03050"
}

function progressPercentage(cert) {
    const days = certificateDaysLeft(cert.valid_to)
    if (days === null || cert.status === "expired" || cert.status === "revoked") return 100
    return Math.max(0, Math.min(100, Math.round(100 - (days / 365) * 100)))
}
</script>

<template>
    <NDrawer v-model:show="show" width="560" block-scroll>
        <NDrawerContent title="Сведения о сертификате" closable v-if="certificate">
            <NFlex align="center" :size="16" class="mb-5">
                <NAvatar round :size="60" :color="avatarColor(certificate.staff_id)" style="color:#fff;font-weight:600;font-size:20px">
                    {{ staffInitials(certificate.fio) }}
                </NAvatar>
                <div class="flex-1 min-w-0">
                    <div class="text-lg font-semibold leading-tight">{{ certificate.fio }}</div>
                    <div class="text-sm mb-2">{{ certificate.position }}</div>
                    <StatusTag :status="certificate.status" />
                </div>
            </NFlex>

            <NEl class="rounded-lg p-4 mb-5 bg-[color-mix(in_srgb,var(--placeholder-color)_30%,transparent)]">
                <NFlex justify="space-between" class="mb-2 text-sm">
                    <span class="">Срок действия</span>
                    <span class="font-semibold" :style="{color: daysColor(certificate.status)}">{{ certificateDaysLabel(certificate) }}</span>
                </NFlex>
                <NProgress type="line" :percentage="progressPercentage(certificate)" :color="daysColor(certificate.status)" :show-indicator="false" :height="7" />
                <NFlex justify="space-between" class="mt-2 text-xs">
                    <NTime format="dd.MM.yyyy" :time="Number(certificate.valid_from)" />
                    <NTime format="dd.MM.yyyy" :time="Number(certificate.valid_to)" />
                </NFlex>
            </NEl>

            <div class="text-xs font-semibold uppercase tracking-wide mb-3">Реквизиты</div>
            <NList>
                <NListItem>
                    <NFlex align="center" justify="space-between">
                        <NEl class="text-[var(--text-color-3)]">СНИЛС</NEl>
                        <span class="font-mono">
                            {{ certificate.snils }}
                        </span>
                    </NFlex>
                </NListItem>
                <NListItem>
                    <NFlex align="center" justify="space-between" :wrap="false">
                        <NEl class="text-[var(--text-color-3)]">Серийный номер</NEl>
                        <span class="font-mono">
                            {{ certificate.serial_number }}
                        </span>
                    </NFlex>
                </NListItem>
                <NListItem>
                    <NFlex align="center" justify="space-between">
                        <NEl class="text-[var(--text-color-3)]">Алгоритм</NEl>
                        <span>
                            ГОСТ Р 34.10-2012
                        </span>
                    </NFlex>
                </NListItem>
                <NListItem>
                    <NFlex align="center" justify="space-between">
                        <NEl class="text-[var(--text-color-3)]">Файл</NEl>
                        <span>
                            {{ certificate.file_certification }}
                        </span>
                    </NFlex>
                </NListItem>
            </NList>

            <template #footer>
                <NFlex vertical :size="10" style="width: 100%">
                    <NFlex :wrap="false" style="width: 100%">
                        <NButton tag="a" :href="downloadUrl(certificate)" target="_blank" type="primary" style="flex: 1" v-if="hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)">
                            <template #icon>
                                <NIcon :component="IconDownload" />
                            </template>
                            Скачать
                        </NButton>
                        <NTooltip trigger="hover" v-if="hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)">
                            <template #trigger>
                                <NButton secondary :loading="installing" @click="installTrustedCas">
                                    <template #icon>
                                        <NIcon :component="IconShieldCheck" />
                                    </template>
                                    Установить сертификаты УЦ
                                </NButton>
                            </template>
                            Через плагин КриптоПро добавит корневой и промежуточные сертификаты УЦ в хранилище Windows текущего пользователя — для доверия цепочке подписи на этом компьютере.
                        </NTooltip>
                        <!-- <NButton secondary v-if="hasScope(scopes.CAN_CREATE_STAFF)" @click="emit('renew')">
                            <template #icon>
                                <NIcon :component="IconRefresh" />
                            </template>
                            Продлить
                        </NButton>
                        <NPopconfirm v-if="certificate.status !== 'revoked' && hasScope(scopes.CAN_DELETE_STAFF)" @positive-click="emit('revoke', certificate)">
                            <template #trigger>
                                <NButton type="error" secondary :loading="revoking">
                                    <template #icon>
                                        <NIcon :component="IconBan" />
                                    </template>
                                    Отозвать
                                </NButton>
                            </template>
                            Отозвать сертификат «{{ certificate.serial_number }}»? Это действие необратимо.
                        </NPopconfirm> -->
                    </NFlex>
                </NFlex>
            </template>
        </NDrawerContent>
    </NDrawer>
</template>
