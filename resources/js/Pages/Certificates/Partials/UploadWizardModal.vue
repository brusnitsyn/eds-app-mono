<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue"
import {router} from "@inertiajs/vue3"
import {NModal, NAlert, NFlex, NSpin, NResult, NButton, NIcon} from "naive-ui"
import {IconCircleCheck, IconAlertTriangle, IconX, IconUpload} from "@tabler/icons-vue"
import CreateStaffForm from "@/Pages/Staff/Partials/CreateStaffForm.vue"

const show = defineModel("show")

const step = ref(1)
const processing = ref({status: null, type: "info", message: ""})

// Сообщение о провале, для которого нет своего пакета (например, в
// загруженных файлах вообще не нашли сертификат .cer) — единственный случай,
// когда ошибку больше не к чему привязать построчно.
const batchFailureMessage = ref(null)

// Реальные проверки, которые сервер выполняет для каждого сертификата пакета:
// целостность контейнера → срок действия → цепочка доверия (издатель из
// списка аккредитованных УЦ) → проверка по списку отзыва (CRL).
// См. ProcessCertificateUpload::handle().
const STAGES = [
    {key: "container", title: "Целостность контейнера", placeholder: "Архив, сертификат и контейнер закрытого ключа КриптоПро прочитаны"},
    {key: "expiry", title: "Срок действия сертификата", placeholder: "Проверка дат «действует с» / «действует до» относительно текущей даты"},
    {key: "chain", title: "Цепочка доверия", placeholder: "Сверка издателя сертификата со списком аккредитованных УЦ"},
    {key: "crl", title: "Проверка по списку отзыва (CRL)", placeholder: "Запрос к точке распространения CRL удостоверяющего центра"},
]
const STAGE_KEYS = STAGES.map((s) => s.key)
const TERMINAL_STAGE_STATUSES = ["success", "warning", "error"]

function emptyStageState() {
    return {container: "pending", expiry: "pending", chain: "pending", crl: "pending"}
}

function emptyStageMessage() {
    return {container: "", expiry: "", chain: "", crl: ""}
}

// Сертификаты пакета. Состав (id/label) приходит от сервера в событии
// "started" — до этого момента он неизвестен, поэтому шаг 2 показывает
// нейтральный спиннер вместо конкретных строк (см. шаблон ниже).
const packages = ref([])

function packageHasError(pkg) {
    return STAGE_KEYS.some((key) => pkg.stageStatus[key] === "error")
}

// Провалившийся сертификат больше не получит событий по оставшимся этапам
// (job уже остановился) — они останутся "pending" навсегда, и это нормально:
// ошибка на любом этапе сама по себе означает, что для этого пакета всё кончено.
function packageSettled(pkg) {
    return packageHasError(pkg) || STAGE_KEYS.every((key) => TERMINAL_STAGE_STATUSES.includes(pkg.stageStatus[key]))
}

function packageErrorMessage(pkg) {
    const key = STAGE_KEYS.find((k) => pkg.stageStatus[k] === "error")
    return key ? pkg.stageMessage[key] : "Не пройдена проверка"
}

const allPackagesSettled = computed(() => packages.value.length > 0 && packages.value.every(packageSettled))
const step2ShowFooter = computed(() => allPackagesSettled.value || !!batchFailureMessage.value)
// Идти дальше можно и если часть сертификатов пакета провалилась — на шаге 3
// провалившиеся показаны отдельным списком с причиной, это не экран "только успех".
const step2CanAdvance = computed(() => allPackagesSettled.value && !batchFailureMessage.value)

const savedEntries = computed(() => packages.value.map((p) => p.saved).filter(Boolean))
const failedEntries = computed(() =>
    packages.value.filter(packageHasError).map((p) => ({label: p.label, message: packageErrorMessage(p)}))
)

let channel = null
let handler = null
let didReload = false

onMounted(() => {
    channel = window.Echo.channel("certificate.processing")
    handler = (data) => {
        processing.value = data

        // Состав батча — приходит один раз, сразу после постановки в очередь,
        // до того как отработает хоть один этап хоть одного сертификата.
        if (data.status === "started" && Array.isArray(data.data?.packages)) {
            packages.value = data.data.packages.map((p) => ({
                id: p.id,
                label: p.label,
                stageStatus: emptyStageState(),
                stageMessage: emptyStageMessage(),
                saved: null,
            }))
            // Не ждём первое событие по конкретному сертификату — между
            // постановкой в очередь и его обработкой есть задержка, и строки
            // иначе на секунду выглядят как "ничего не происходит".
            packages.value.forEach((pkg) => {
                pkg.stageStatus.container = "running"
            })
            return
        }

        if (!data.packageId) {
            // Провал без привязки к пакету — нет своего блока на шаге 2,
            // показываем отдельно (например, в загруженных файлах вообще не
            // нашли сертификат — батч даже не создавался).
            if (!data.stage && data.status === "failed") {
                batchFailureMessage.value = data.message
            }
            return
        }

        const pkg = packages.value.find((p) => p.id === data.packageId)
        if (!pkg) return

        if (data.stage && pkg.stageStatus[data.stage] !== undefined) {
            const statusMap = {running: "running", done: "success", warning: "warning", failed: "error"}
            pkg.stageStatus[data.stage] = statusMap[data.status] ?? "pending"
            pkg.stageMessage[data.stage] = data.message
        }

        if (data.stage === "save" && data.status === "done") {
            pkg.saved = data.data ?? null
            window.$message?.success(`Сертификат «${pkg.saved?.full_name || pkg.label || ""}» отправлен в реестр`)
        }

        if (!didReload && allPackagesSettled.value && savedEntries.value.length > 0) {
            didReload = true
            router.reload()
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

// Назад с любого шага, где это уместно (1 и работающий шаг 2 — без кнопок).
function goBack() {
    if (step.value === 2) {
        reset()
        return
    }
    if (step.value === 3) {
        step.value = 2
        return
    }
    if (step.value === 4) {
        step.value = 3
    }
}

const TOTAL_STEPS = 4
const STEP_TITLES = {
    1: "Выбор файлов",
    2: "Проверка на сервере",
    3: "Данные сотрудников",
    4: "Подтверждение",
}
const stepTitle = computed(() => STEP_TITLES[step.value])
const progressPercent = computed(() => (step.value / TOTAL_STEPS) * 100)

function formatDate(ms) {
    if (!ms) return "—"
    return new Date(ms).toLocaleDateString("ru-RU")
}

function rowClass(status) {
    if (status === "running") return "border-emerald-400 dark:border-emerald-500/50 bg-emerald-50 dark:bg-emerald-500/10"
    if (status === "error") return "border-red-300 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10"
    if (status === "warning") return "border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10"
    return "border-gray-200 dark:border-white/10"
}

function iconWrapClass(status) {
    if (status === "success" || status === "running") return "bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400"
    if (status === "warning") return "bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400"
    if (status === "error") return "bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400"
    return "bg-gray-100 dark:bg-white/10 text-gray-400 dark:text-white/40"
}

const resultStatus = computed(() => (savedEntries.value.length > 0 ? "success" : "warning"))
const resultTitle = computed(() => {
    if (packages.value.length <= 1) {
        return savedEntries.value.length > 0 ? "Сертификат отправлен в реестр" : "Сертификат не сохранён"
    }
    return savedEntries.value.length > 0 ? "Загрузка завершена" : "Ни один сертификат не сохранён"
})
const resultDescription = computed(() => {
    if (packages.value.length <= 1) return processing.value.message
    return `Сохранено сертификатов: ${savedEntries.value.length} из ${packages.value.length}`
})

function reset() {
    step.value = 1
    processing.value = {status: null, type: "info", message: ""}
    batchFailureMessage.value = null
    packages.value = []
    didReload = false
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
                </div>
            </NFlex>
        </template>

        <div class="mb-6">
            <div class="flex items-baseline justify-between gap-3 mb-2">
                <div class="text-sm font-semibold flex items-baseline gap-1.5">
                    <span class="text-gray-400 dark:text-white/40">{{ step }} ·</span>
                    <span>{{ stepTitle }}</span>
                </div>
                <span class="text-xs text-gray-400 dark:text-white/40 flex-none">Шаг {{ step }} из 4</span>
            </div>
            <div class="h-[2px] w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300 ease-out" :style="{width: progressPercent + '%'}" />
            </div>
        </div>

        <!-- Высота ограничена: при нескольких сертификатах в пакете блоков
             этапов может быть много, и без этого модалка растягивает страницу
             за пределы экрана. Кнопки "Назад"/"Далее" — снаружи, чтобы не уезжали со скроллом. -->
        <div v-if="step === 1 || step === 2 || step === 3" class="max-h-[55vh] overflow-y-auto pr-1 -mr-1">
            <div v-if="step === 1">
                <NAlert type="info" class="mb-4" :show-icon="false">
                    Чтобы загрузить несколько сертификатов сразу, выберите папку, внутри которой лежат их подпапки: пакеты определятся автоматически.
                </NAlert>
                <CreateStaffForm @success="onUploadSuccess" />
            </div>

            <NFlex v-else-if="step === 2" vertical :size="16" class="py-2">
                <div v-if="packages.length === 0" class="flex flex-col items-center gap-2 py-6 text-gray-400 dark:text-white/40 text-xs">
                    <NSpin :size="20" />
                    Постановка в очередь…
                </div>

                <div v-for="pkg in packages" :key="pkg.id" class="flex flex-col gap-2">
                    <div v-if="packages.length > 1" class="text-xs font-semibold text-gray-500 dark:text-white/50 px-1">
                        {{ pkg.label || "Сертификат" }}
                    </div>
                    <NFlex vertical :size="8">
                        <div v-for="(stageDef, index) in STAGES" :key="stageDef.key"
                             class="flex items-center gap-3 px-4 py-3 rounded-lg border transition-colors"
                             :class="rowClass(pkg.stageStatus[stageDef.key])"
                        >
                            <span class="flex items-center justify-center w-7 h-7 rounded-full flex-none" :class="iconWrapClass(pkg.stageStatus[stageDef.key])">
                                <NIcon v-if="pkg.stageStatus[stageDef.key] === 'success'" :component="IconCircleCheck" :size="16" />
                                <NIcon v-else-if="pkg.stageStatus[stageDef.key] === 'warning'" :component="IconAlertTriangle" :size="16" />
                                <NIcon v-else-if="pkg.stageStatus[stageDef.key] === 'error'" :component="IconX" :size="16" />
                                <NSpin v-else-if="pkg.stageStatus[stageDef.key] === 'running'" :size="14" />
                                <span v-else class="text-xs font-semibold">{{ index + 1 }}</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold">{{ stageDef.title }}</div>
                                <div class="text-xs text-gray-400 dark:text-white/40">{{ pkg.stageMessage[stageDef.key] || stageDef.placeholder }}</div>
                            </div>
                        </div>
                    </NFlex>
                </div>

                <div v-if="packages.length && !step2ShowFooter" class="text-gray-400 dark:text-white/40 text-xs text-center mt-1">
                    Идет проверка сертификатов. Пожалуйста подождите.
                </div>
                <NAlert v-if="batchFailureMessage" type="error" :show-icon="false">
                    {{ batchFailureMessage }}
                </NAlert>
            </NFlex>

            <NFlex v-else-if="step === 3" vertical :size="16" class="py-2">
                <NAlert v-if="savedEntries.length" type="success" :show-icon="false">
                    {{ savedEntries.length > 1
                        ? `Сохранено сертификатов: ${savedEntries.length} из ${packages.length}. Проверьте распознанные данные сотрудников.`
                        : "Сертификат прошёл все проверки и сохранён в реестре. Проверьте распознанные данные сотрудника." }}
                </NAlert>
                <NAlert v-if="failedEntries.length" type="error" :show-icon="false">
                    <div v-for="entry in failedEntries" :key="entry.label" class="mb-1 last:mb-0">
                        <span class="font-medium">{{ entry.label || "Сертификат" }}:</span> {{ entry.message }}
                    </div>
                </NAlert>

                <div v-for="(owner, idx) in savedEntries" :key="idx" class="rounded-lg border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/10">
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-gray-400 dark:text-white/40">ФИО</span>
                        <span class="font-medium">{{ owner?.full_name || "—" }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-gray-400 dark:text-white/40">Должность</span>
                        <span class="font-medium">{{ owner?.job_title || "—" }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-gray-400 dark:text-white/40">СНИЛС</span>
                        <span class="font-medium">{{ owner?.snils || "—" }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-gray-400 dark:text-white/40">Серийный номер</span>
                        <span class="font-medium font-mono text-xs">{{ owner?.serial_number || "—" }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-gray-400 dark:text-white/40">Действует</span>
                        <span class="font-medium">{{ formatDate(owner?.valid_from) }} — {{ formatDate(owner?.valid_to) }}</span>
                    </div>
                </div>
            </NFlex>
        </div>

        <NFlex v-if="step === 2 && step2ShowFooter" justify="space-between" class="mt-4">
            <NButton @click="goBack">
                Назад
            </NButton>
            <NButton v-if="step2CanAdvance" type="primary" @click="step = 3">
                Далее
            </NButton>
        </NFlex>

        <NFlex v-else-if="step === 3" justify="space-between" class="mt-4">
            <NButton @click="goBack">
                Назад
            </NButton>
            <NButton type="primary" @click="confirmData">
                Далее
            </NButton>
        </NFlex>

        <NResult v-else-if="step === 4"
                 :status="resultStatus"
                 :title="resultTitle"
                 :description="resultDescription"
        >
            <template #footer>
                <NFlex justify="center">
                    <NButton @click="goBack">
                        Назад
                    </NButton>
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
