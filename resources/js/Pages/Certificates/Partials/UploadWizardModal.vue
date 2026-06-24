<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue"
import {router} from "@inertiajs/vue3"
import {NModal, NSteps, NStep, NAlert, NFlex, NSpin, NResult, NButton, NIcon} from "naive-ui"
import {IconCircleCheck, IconAlertTriangle, IconX, IconUpload} from "@tabler/icons-vue"
import CreateStaffForm from "@/Pages/Staff/Partials/CreateStaffForm.vue"

const show = defineModel("show")

const step = ref(1)
const processing = ref({status: null, type: "info", message: ""})
const failure = ref(null)
const parsedOwner = ref(null)

// Реальные проверки, которые сервер выполняет на этапе "Проверка":
// целостность контейнера → цепочка доверия (издатель из списка аккредитованных
// УЦ) → проверка по списку отзыва (CRL). См. ProcessCertificateUpload::handle().
const STAGES = [
    {key: "container", title: "Целостность контейнера", placeholder: "Архив, сертификат и контейнер закрытого ключа КриптоПро прочитаны"},
    {key: "chain", title: "Цепочка доверия", placeholder: "Сверка издателя сертификата со списком аккредитованных УЦ"},
    {key: "crl", title: "Проверка по списку отзыва (CRL)", placeholder: "Запрос к точке распространения CRL удостоверяющего центра"},
]

function emptyStageState() {
    return {container: "pending", chain: "pending", crl: "pending"}
}

const stageStatus = ref(emptyStageState())
const stageMessage = ref({container: "", chain: "", crl: ""})

let channel = null
let handler = null

onMounted(() => {
    channel = window.Echo.channel("certificate.processing")
    handler = (data) => {
        processing.value = data

        // Событие этапа (container/chain/crl/save) — нет stage только у событий уровня пакета.
        if (data.stage && stageStatus.value[data.stage] !== undefined) {
            const statusMap = {running: "running", done: "success", warning: "warning", failed: "error"}
            stageStatus.value[data.stage] = statusMap[data.status] ?? "pending"
            stageMessage.value[data.stage] = data.message
        }

        if (data.stage === "save" && data.status === "done") {
            parsedOwner.value = data.data ?? null
            step.value = 3
            window.$message?.success("Сертификат отправлен в реестр")
            return
        }

        const isStageFailure = data.stage && data.status === "failed"
        const isBatchFailure = !data.stage && data.status === "failed"
        if (isStageFailure || isBatchFailure) {
            failure.value = data
            step.value = 4
            window.$message?.error("Не удалось обработать сертификат")
        }
    }
    channel.listen("CertificateProcessingEvent", handler)
})

onUnmounted(() => {
    channel?.stopListening("CertificateProcessingEvent", handler)
})

function onUploadSuccess() {
    step.value = 2
}

function confirmData() {
    step.value = 4
}

const STEP_SUBTITLES = {
    1: "Шаг 1 из 4 · выбор файлов",
    2: "Шаг 2 из 4 · проверка на сервере",
    3: "Шаг 3 из 4 · данные сотрудника",
    4: "Шаг 4 из 4 · подтверждение",
}
const subtitle = computed(() => STEP_SUBTITLES[step.value])

function formatDate(ms) {
    if (!ms) return "—"
    return new Date(ms).toLocaleDateString("ru-RU")
}

function rowClass(stageKey) {
    const status = stageStatus.value[stageKey]
    if (status === "running") return "border-emerald-400 dark:border-emerald-500/50 bg-emerald-50 dark:bg-emerald-500/10"
    if (status === "error") return "border-red-300 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10"
    if (status === "warning") return "border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10"
    return "border-gray-200 dark:border-white/10"
}

function iconWrapClass(stageKey) {
    const status = stageStatus.value[stageKey]
    if (status === "success" || status === "running") return "bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400"
    if (status === "warning") return "bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400"
    if (status === "error") return "bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400"
    return "bg-gray-100 dark:bg-white/10 text-gray-400 dark:text-white/40"
}

function reset() {
    step.value = 1
    processing.value = {status: null, type: "info", message: ""}
    failure.value = null
    parsedOwner.value = null
    stageStatus.value = emptyStageState()
    stageMessage.value = {container: "", chain: "", crl: ""}
}

function close() {
    show.value = false
}

function goToList() {
    close()
    router.visit(route("certificates.index"))
}
</script>

<template>
    <NModal v-model:show="show" preset="card" style="width: 660px" :bordered="false" @after-leave="reset">
        <template #header>
            <NFlex align="center" :size="12">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-none text-white" style="background: var(--primary-color, #18a058)">
                    <NIcon :component="IconUpload" size="18" />
                </div>
                <div class="leading-tight">
                    <div class="font-semibold">Загрузка УКЭП</div>
                    <div class="text-xs text-gray-400 dark:text-white/40">{{ subtitle }}</div>
                </div>
            </NFlex>
        </template>

        <NSteps :current="step" class="mb-7">
            <NStep title="Файлы" />
            <NStep title="Проверка" />
            <NStep title="Данные" />
            <NStep title="Готово" />
        </NSteps>

        <div v-if="step === 1">
            <NAlert type="info" class="mb-4" :show-icon="false">
                Загрузите .zip-архив с файлами сертификата (.cer) и контейнера закрытого ключа КриптоПро. Если в архиве несколько сертификатов — отметьте «Архив содержит несколько сертификатов».
            </NAlert>
            <CreateStaffForm @success="onUploadSuccess" />
        </div>

        <NFlex v-else-if="step === 2" vertical :size="12" class="py-2">
            <div v-for="(stageDef, index) in STAGES" :key="stageDef.key"
                 class="flex items-center gap-3 px-4 py-3 rounded-lg border transition-colors"
                 :class="rowClass(stageDef.key)"
            >
                <span class="flex items-center justify-center w-7 h-7 rounded-full flex-none" :class="iconWrapClass(stageDef.key)">
                    <NIcon v-if="stageStatus[stageDef.key] === 'success'" :component="IconCircleCheck" :size="16" />
                    <NIcon v-else-if="stageStatus[stageDef.key] === 'warning'" :component="IconAlertTriangle" :size="16" />
                    <NIcon v-else-if="stageStatus[stageDef.key] === 'error'" :component="IconX" :size="16" />
                    <NSpin v-else-if="stageStatus[stageDef.key] === 'running'" :size="14" />
                    <span v-else class="text-xs font-semibold">{{ index + 1 }}</span>
                </span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold">{{ stageDef.title }}</div>
                    <div class="text-xs text-gray-400 dark:text-white/40">{{ stageMessage[stageDef.key] || stageDef.placeholder }}</div>
                </div>
            </div>
            <div class="text-gray-400 dark:text-white/40 text-xs text-center mt-1">
                Статус обработки также отображается в подвале приложения.
            </div>
        </NFlex>

        <NFlex v-else-if="step === 3" vertical :size="16" class="py-2">
            <NAlert type="success" :show-icon="false">
                Сертификат прошёл все проверки и сохранён в реестре. Проверьте распознанные данные сотрудника.
            </NAlert>
            <div class="rounded-lg border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="text-gray-400 dark:text-white/40">ФИО</span>
                    <span class="font-medium">{{ parsedOwner?.full_name || "—" }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="text-gray-400 dark:text-white/40">Должность</span>
                    <span class="font-medium">{{ parsedOwner?.job_title || "—" }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="text-gray-400 dark:text-white/40">СНИЛС</span>
                    <span class="font-medium">{{ parsedOwner?.snils || "—" }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="text-gray-400 dark:text-white/40">Серийный номер</span>
                    <span class="font-medium font-mono text-xs">{{ parsedOwner?.serial_number || "—" }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="text-gray-400 dark:text-white/40">Действует</span>
                    <span class="font-medium">{{ formatDate(parsedOwner?.valid_from) }} — {{ formatDate(parsedOwner?.valid_to) }}</span>
                </div>
            </div>
            <NFlex justify="end">
                <NButton type="primary" @click="confirmData">
                    Далее
                </NButton>
            </NFlex>
        </NFlex>

        <NResult v-else
                 :status="failure ? 'error' : 'success'"
                 :title="failure ? 'Не удалось обработать сертификат' : 'Сертификат отправлен в реестр'"
                 :description="failure ? failure.message : processing.message"
        >
            <template #footer>
                <NFlex justify="center">
                    <NButton @click="close">
                        Закрыть
                    </NButton>
                    <NButton type="primary" @click="goToList">
                        Перейти к списку
                    </NButton>
                </NFlex>
            </template>
        </NResult>
    </NModal>
</template>
