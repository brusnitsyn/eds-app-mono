<script setup>
import {NCard, NDescriptions, NDescriptionsItem, NTag, NButton, NIcon, NFlex, NAlert, NSwitch, NText, NStatistic, NUpload, NUploadDragger, NSelect, NInput, NList, NListItem, NPopconfirm, NEmpty, NTime} from "naive-ui"
import {ref} from "vue"
import {router, useForm} from "@inertiajs/vue3"
import {useStorage} from "@vueuse/core"
import {IconPlugConnected, IconArrowRight, IconCertificate, IconTrash, IconUpload, IconDownload, IconDeviceDesktopCheck} from "@tabler/icons-vue"
import {useCheckScope} from "@/Composables/useCheckScope.js"

const props = defineProps({
    parser: Object,
    storage: Object,
    mis: Object,
    trustedCas: {type: Array, default: () => []},
    workstationSoftware: {type: Object, default: () => ({})},
})

const SW_KEYS = [
    {key: 'chromium_gost',    label: 'Chromium GOST'},
    {key: 'cryptopro_plugin', label: 'КриптоПро ЭЦП Browser Plug-in'},
    {key: 'cryptopro_csp',    label: 'КриптоПро CSP'},
]

const swForm = useForm({key: '', version: '', file: null})

function uploadSoftware(key) {
    swForm.key = key
    swForm.post(route('workstation-software.store'), {
        forceFormData: true,
        onSuccess: () => {
            swForm.reset()
            window.$message?.success('Файл загружен')
        },
    })
}

function deleteSoftware(id) {
    router.delete(route('workstation-software.destroy', id), {
        onSuccess: () => window.$message?.success('Файл удалён'),
    })
}

function formatSize(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' МБ'
    return (bytes / 1024).toFixed(0) + ' КБ'
}

const {hasScope, scopes} = useCheckScope()

const testing = ref(false)
const testResult = ref(null)

function testParser() {
    testing.value = true
    testResult.value = null
    window.axios.post(route("certificates.settings.test-parser"))
        .then(({data}) => {
            testResult.value = data
        })
        .catch(() => {
            testResult.value = {ok: false, message: "Не удалось выполнить проверку"}
        })
        .finally(() => {
            testing.value = false
        })
}

const caTypeOptions = [
    {label: "Корневой", value: "root"},
    {label: "Промежуточный", value: "intermediate"},
]

const caForm = useForm({
    name: "",
    type: "root",
    file: null,
})

function uploadCa() {
    caForm.post(route("certificates.trusted-ca.store"), {
        forceFormData: true,
        onSuccess: () => {
            caForm.reset()
            window.$message?.success("Сертификат УЦ добавлен")
        },
    })
}

function deleteCa(ca) {
    window.axios.delete(route("certificates.trusted-ca.destroy", ca.id))
        .then(() => {
            router.reload({only: ["trustedCas"]})
            window.$message?.success("Сертификат УЦ удалён")
        })
        .catch(() => window.$message?.error("Не удалось удалить сертификат"))
}

// Локальные настройки уведомлений и безопасности — пока без серверного хранения,
// сохраняются в браузере пользователя.
const notifyEmail = useStorage("cert-settings-notify-email", true)
const notifyExpiring = useStorage("cert-settings-notify-expiring", true)
const requirePassPrompt = useStorage("cert-settings-require-pass", true)
const twoFA = useStorage("cert-settings-2fa", false)

function saveLocalSettings() {
    window.$message?.success("Настройки сохранены в этом браузере")
}
</script>

<template>
    <NFlex vertical :size="16">
        <NCard title="Парсер сертификатов">
            <template #header-extra>
                <NTag :type="parser.script_exists ? 'success' : 'error'" round size="small">
                    {{ parser.script_exists ? "Скрипт найден" : "Скрипт не найден" }}
                </NTag>
            </template>
            <NDescriptions label-placement="top" :column="2" bordered>
                <NDescriptionsItem label="Интерпретатор">{{ parser.python_binary }}</NDescriptionsItem>
                <NDescriptionsItem label="Таймаут">{{ parser.timeout }} сек.</NDescriptionsItem>
                <NDescriptionsItem label="Путь к скрипту" :span="2">{{ parser.script_path }}</NDescriptionsItem>
                <NDescriptionsItem label="Хранилище сертификатов" :span="2">{{ storage.disk_root }}</NDescriptionsItem>
                <NDescriptionsItem label="Допустимые форматы" :span="2">.zip-архив с файлами .cer и закрытого ключа</NDescriptionsItem>
            </NDescriptions>

            <NFlex vertical class="mt-4">
                <NButton secondary :loading="testing" @click="testParser" style="width: fit-content">
                    <template #icon>
                        <NIcon :component="IconPlugConnected" />
                    </template>
                    Проверить интерпретатор
                </NButton>
                <NAlert v-if="testResult" :type="testResult.ok ? 'success' : 'error'" :title="testResult.message" :show-icon="true" />
            </NFlex>
        </NCard>

        <NCard title="Интеграция с МИС">
            <NFlex align="center" justify="space-between">
                <NStatistic label="Сотрудников синхронизировано с МИС" :value="mis.synced_count">
                    <template #suffix>
                        / {{ mis.staff_total }}
                    </template>
                </NStatistic>
                <NButton tag="a" :href="route('mis.index')" secondary>
                    Перейти в раздел ТМ:МИС
                    <template #icon>
                        <NIcon :component="IconArrowRight" />
                    </template>
                </NButton>
            </NFlex>
        </NCard>

        <NCard title="Доверенные сертификаты УЦ">
            <template #header-extra>
                <NTag round size="small">{{ trustedCas.length }}</NTag>
            </template>
            <NAlert type="info" :show-icon="false" class="mb-4">
                Корневой и промежуточные сертификаты удостоверяющего центра, которые сотрудники могут одним кликом установить
                в хранилище Windows со страницы карточки сертификата — для доверия цепочке подписи на их компьютере.
                Личные сертификаты и закрытые ключи сотрудников сюда не относятся.
            </NAlert>

            <NList v-if="trustedCas.length" bordered class="mb-4">
                <NListItem v-for="ca in trustedCas" :key="ca.id">
                    <NFlex align="center" justify="space-between" style="width: 100%">
                        <NFlex align="center" :size="10">
                            <NIcon :component="IconCertificate" :depth="3" />
                            <div>
                                <div class="font-medium">{{ ca.name }}</div>
                                <div class="text-xs text-gray-400 dark:text-white/40">
                                    {{ ca.subject_cn || "—" }}
                                    <template v-if="ca.valid_to"> · до <NTime format="dd.MM.yyyy" :time="Number(ca.valid_to)" /></template>
                                </div>
                            </div>
                        </NFlex>
                        <NFlex align="center" :size="10">
                            <NTag size="small" :type="ca.type === 'root' ? 'success' : 'info'">
                                {{ ca.type === "root" ? "Корневой" : "Промежуточный" }}
                            </NTag>
                            <NPopconfirm v-if="hasScope(scopes.CAN_ADMIN)" @positive-click="deleteCa(ca)">
                                <template #trigger>
                                    <NButton size="small" quaternary circle type="error">
                                        <template #icon>
                                            <NIcon :component="IconTrash" />
                                        </template>
                                    </NButton>
                                </template>
                                Удалить «{{ ca.name }}» из списка?
                            </NPopconfirm>
                        </NFlex>
                    </NFlex>
                </NListItem>
            </NList>
            <NEmpty v-else description="Сертификаты УЦ ещё не загружены" class="mb-4" />

            <NFlex v-if="hasScope(scopes.CAN_ADMIN)" :wrap="false" align="start" :size="12">
                <NInput v-model:value="caForm.name" placeholder="Название УЦ" style="flex: 1" />
                <NSelect v-model:value="caForm.type" :options="caTypeOptions" style="width: 160px" />
                <NUpload :max="1" :default-upload="false" :show-file-list="false"
                         @change="(data) => caForm.file = data.fileList[0]?.file ?? null">
                    <NButton secondary>
                        <template #icon>
                            <NIcon :component="IconUpload" />
                        </template>
                        {{ caForm.file?.name || "Выбрать файл" }}
                    </NButton>
                </NUpload>
                <NButton type="primary" :loading="caForm.processing" :disabled="!caForm.name || !caForm.file" @click="uploadCa">
                    Добавить
                </NButton>
            </NFlex>
        </NCard>

        <NCard title="ПО для рабочего места">
            <template #header-extra>
                <NIcon :component="IconDeviceDesktopCheck" :depth="3" />
            </template>
            <NAlert type="info" :show-icon="false" class="mb-4">
                Дистрибутивы, которые сотрудники смогут скачать со страницы «Проверка рабочего места».
                Загрузите актуальные версии один раз — пользователи не будут уходить на внешние сайты.
            </NAlert>
            <NList bordered class="mb-4">
                <NListItem v-for="item in SW_KEYS" :key="item.key">
                    <NFlex align="center" justify="space-between" style="width:100%">
                        <NFlex align="center" :size="10">
                            <NIcon :component="IconDeviceDesktopCheck" :depth="3" />
                            <div>
                                <div class="font-medium">{{ item.label }}</div>
                                <div v-if="workstationSoftware[item.key]" class="text-xs text-gray-400 dark:text-white/40">
                                    {{ workstationSoftware[item.key].original_name }}
                                    <template v-if="workstationSoftware[item.key].version">· v{{ workstationSoftware[item.key].version }}</template>
                                    · {{ formatSize(workstationSoftware[item.key].file_size) }}
                                </div>
                                <div v-else class="text-xs text-gray-400 dark:text-white/40">Файл не загружен</div>
                            </div>
                        </NFlex>
                        <NFlex align="center" :size="8" v-if="hasScope(scopes.CAN_ADMIN)">
                            <NTag v-if="workstationSoftware[item.key]" size="small" type="success">Загружен</NTag>
                            <NTag v-else size="small" type="default">Отсутствует</NTag>
                            <NButton
                                v-if="workstationSoftware[item.key]"
                                size="small" quaternary
                                tag="a"
                                :href="route('workstation-software.download', workstationSoftware[item.key].id)"
                                target="_blank"
                            >
                                <template #icon><NIcon :component="IconDownload" /></template>
                            </NButton>
                            <NUpload
                                :max="1"
                                :default-upload="false"
                                :show-file-list="false"
                                @change="(data) => { swForm.key = item.key; swForm.file = data.fileList[0]?.file ?? null }"
                            >
                                <NButton size="small" secondary :loading="swForm.processing && swForm.key === item.key">
                                    <template #icon><NIcon :component="IconUpload" /></template>
                                    {{ workstationSoftware[item.key] ? 'Обновить' : 'Загрузить' }}
                                </NButton>
                            </NUpload>
                            <NInput
                                v-model:value="swForm.version"
                                placeholder="Версия"
                                size="small"
                                style="width:90px"
                                v-if="swForm.key === item.key && swForm.file"
                            />
                            <NButton
                                v-if="swForm.key === item.key && swForm.file"
                                size="small" type="primary"
                                :loading="swForm.processing"
                                @click="uploadSoftware(item.key)"
                            >
                                Сохранить
                            </NButton>
                            <NPopconfirm v-if="workstationSoftware[item.key]" @positive-click="deleteSoftware(workstationSoftware[item.key].id)">
                                <template #trigger>
                                    <NButton size="small" quaternary circle type="error">
                                        <template #icon><NIcon :component="IconTrash" /></template>
                                    </NButton>
                                </template>
                                Удалить файл «{{ item.label }}»?
                            </NPopconfirm>
                        </NFlex>
                    </NFlex>
                </NListItem>
            </NList>
        </NCard>

        <NCard title="Уведомления">
            <NFlex justify="space-between" align="center" class="py-3 border-b dark:border-white/10">
                <div>
                    <NText strong>Email-уведомления</NText>
                    <div class="text-xs text-gray-400 dark:text-white/40">Отправлять оповещения на почту администраторов</div>
                </div>
                <NSwitch v-model:value="notifyEmail" />
            </NFlex>
            <NFlex justify="space-between" align="center" class="py-3">
                <div>
                    <NText strong>Предупреждать об истечении</NText>
                    <div class="text-xs text-gray-400 dark:text-white/40">За 30 дней до окончания срока действия сертификата</div>
                </div>
                <NSwitch v-model:value="notifyExpiring" />
            </NFlex>
        </NCard>

        <NCard title="Безопасность">
            <NFlex justify="space-between" align="center" class="py-3 border-b dark:border-white/10">
                <div>
                    <NText strong>Пароль контейнера при загрузке</NText>
                    <div class="text-xs text-gray-400 dark:text-white/40">Запрашивать пароль закрытого ключа для .pfx-контейнеров</div>
                </div>
                <NSwitch v-model:value="requirePassPrompt" />
            </NFlex>
            <NFlex justify="space-between" align="center" class="py-3">
                <div>
                    <NText strong>Двухфакторная аутентификация</NText>
                    <div class="text-xs text-gray-400 dark:text-white/40">Вход администратора по 2ФА</div>
                </div>
                <NSwitch v-model:value="twoFA" />
            </NFlex>
        </NCard>

        <NFlex justify="end">
            <NButton type="primary" @click="saveLocalSettings">
                Сохранить изменения
            </NButton>
        </NFlex>
    </NFlex>
</template>
