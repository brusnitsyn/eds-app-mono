<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import CheckCard from "./Partials/CheckCard.vue"
import {NButton, NIcon, NFlex, NAlert} from "naive-ui"
import {IconRefresh, IconBrowser, IconPlugConnected, IconShieldCheck, IconDownload} from "@tabler/icons-vue"
import {useCadesPlugin} from "@/Composables/useCadesPlugin.js"

const {isAvailable, getSystemInfo} = useCadesPlugin()

const props = defineProps({
    software: {type: Object, default: () => ({})},
})

const STATUS = {PENDING: 'pending', OK: 'ok', FAIL: 'fail'}

const browserCheck = ref({status: STATUS.PENDING, version: null})
const pluginCheck  = ref({status: STATUS.PENDING, version: null})
const cspCheck     = ref({status: STATUS.PENDING, version: null})

function checkBrowser() {
    const ua = navigator.userAgent
    const isGost = /Chromium GOST/i.test(ua)
    const ver = ua.match(/Chrome\/([\d.]+)/)?.[1] ?? null
    browserCheck.value = {status: isGost ? STATUS.OK : STATUS.FAIL, version: isGost ? ver : null}
}

async function checkPlugin() {
    pluginCheck.value.status = STATUS.PENDING
    cspCheck.value.status    = STATUS.PENDING
    try {
        const ok = await isAvailable()
        pluginCheck.value.status = ok ? STATUS.OK : STATUS.FAIL
        if (ok) {
            const info = await getSystemInfo()
            pluginCheck.value.version = info?.pluginVersion ?? null
            cspCheck.value = {status: info?.cspVersion ? STATUS.OK : STATUS.FAIL, version: info?.cspVersion ?? null}
        } else {
            cspCheck.value.status = STATUS.FAIL
        }
    } catch {
        pluginCheck.value.status = STATUS.FAIL
        cspCheck.value.status    = STATUS.FAIL
    }
}

async function runAll() {
    checkBrowser()
    await checkPlugin()
}

onMounted(runAll)

const isChecking = computed(() => [browserCheck, pluginCheck, cspCheck].some(c => c.value.status === STATUS.PENDING))
const allOk      = computed(() => [browserCheck, pluginCheck, cspCheck].every(c => c.value.status === STATUS.OK))
</script>

<template>
    <AppLayout title="Проверка рабочего места" subtitle="Компоненты, необходимые для работы с электронной подписью">
        <template #headermore>
            <NButton :loading="isChecking" @click="runAll">
                <template #icon><NIcon :component="IconRefresh" /></template>
                Повторить проверку
            </NButton>
        </template>

        <NFlex vertical :size="16" style="max-width:680px">

            <NAlert v-if="allOk" type="success" title="Рабочее место готово к работе">
                Все необходимые компоненты обнаружены и работают корректно.
            </NAlert>
            <NAlert v-else-if="!isChecking" type="warning" title="Требуется настройка">
                Установите отсутствующие компоненты и нажмите «Повторить проверку».
            </NAlert>

            <!-- Браузер -->
            <CheckCard
                :status="browserCheck.status"
                :icon="IconBrowser"
                title="Браузер Chromium GOST"
                :subtitle="browserCheck.version ? `Обнаружен · версия ${browserCheck.version}` : 'Требуется браузер с поддержкой алгоритмов ГОСТ'"
                description="Необходим для установки TLS-соединения по российским стандартам ГОСТ Р 34.10-2012 и ГОСТ Р 34.11-2012. Поддерживает сайты на ГОСТ-сертификатах без ошибок безопасности."
            >
                <template #action>
                    <NButton
                        v-if="software.chromium_gost"
                        tag="a"
                        :href="software.chromium_gost.download_url"
                        type="primary"
                    >
                        <template #icon><NIcon :component="IconDownload" /></template>
                        Скачать Chromium GOST
                        <template v-if="software.chromium_gost.version"> · {{ software.chromium_gost.version }}</template>
                    </NButton>
                    <NAlert v-else type="default" :show-icon="false" style="padding:6px 12px">
                        Дистрибутив не загружен — обратитесь к администратору
                    </NAlert>
                </template>
            </CheckCard>

            <!-- Плагин -->
            <CheckCard
                :status="pluginCheck.status"
                :icon="IconPlugConnected"
                title="КриптоПро ЭЦП Browser Plug-in"
                :subtitle="pluginCheck.version ? `Обнаружен · версия ${pluginCheck.version}` : 'Плагин не обнаружен или расширение отключено'"
                description="Обеспечивает создание и проверку КЭП прямо в браузере, а также установку сертификатов УЦ в системное хранилище Windows."
            >
                <template #action>
                    <NFlex :size="8">
                        <NButton
                            v-if="software.cryptopro_plugin"
                            tag="a"
                            :href="software.cryptopro_plugin.download_url"
                            type="primary"
                        >
                            <template #icon><NIcon :component="IconDownload" /></template>
                            Скачать плагин
                            <template v-if="software.cryptopro_plugin.version"> · {{ software.cryptopro_plugin.version }}</template>
                        </NButton>
                        <NAlert v-else type="default" :show-icon="false" style="padding:6px 12px">
                            Дистрибутив не загружен — обратитесь к администратору
                        </NAlert>
                    </NFlex>
                </template>
            </CheckCard>

            <!-- КриптоПро CSP -->
            <CheckCard
                :status="cspCheck.status"
                :icon="IconShieldCheck"
                title="КриптоПро CSP"
                :subtitle="cspCheck.version ? `Обнаружен · версия ${cspCheck.version}` : 'Криптографический провайдер не обнаружен'"
                description="Системный криптографический провайдер для хранения закрытых ключей и выполнения криптографических операций. Устанавливается на рабочем месте отдельно."
            >
                <template #action>
                    <NButton
                        v-if="software.cryptopro_csp"
                        tag="a"
                        :href="software.cryptopro_csp.download_url"
                        type="primary"
                    >
                        <template #icon><NIcon :component="IconDownload" /></template>
                        Скачать КриптоПро CSP
                        <template v-if="software.cryptopro_csp.version"> · {{ software.cryptopro_csp.version }}</template>
                    </NButton>
                    <NAlert v-else type="default" :show-icon="false" style="padding:6px 12px">
                        Дистрибутив не загружен — обратитесь к администратору
                    </NAlert>
                </template>
            </CheckCard>

        </NFlex>
    </AppLayout>
</template>
