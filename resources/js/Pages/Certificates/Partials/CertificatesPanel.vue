<script setup>
import {
    NGrid, NGi, NCard, NStatistic, NFlex, NButton, NIcon, NRadioGroup, NRadioButton,
    NDataTable, NAvatar, NEmpty, NTime, NDescriptions, NDescriptionsItem,
    NProgress, NPopconfirm, NText, NSkeleton, NResult, NPagination, NSpace, NEl
} from "naive-ui"
import {computed, h, ref, watch} from "vue"
import {router} from "@inertiajs/vue3"
import {
    IconLayoutList, IconLayoutGrid, IconLayoutSidebarRightExpand,
    IconDownload, IconBan, IconEye, IconCertificate, IconCheck, IconClock, IconAlertTriangle,
    IconRefresh, IconShield, IconFileZip, IconFileSpreadsheet
} from "@tabler/icons-vue"
import {useCheckScope} from "@/Composables/useCheckScope.js"
import {certificateStatusDef, certificateDaysLabel, certificateDaysLeft, staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import StatusTag from "./StatusTag.vue"
import EdsWidget from "@/Components/Eds/EdsWidget.vue";

const {hasScope, scopes} = useCheckScope()

const props = defineProps({
    directory: Object,
    stats: Object,
    revoking: Boolean,
})

const emit = defineEmits(["open-detail", "open-wizard", "revoke"])

function renderIcon(icon) {
    return () => h(NIcon, null, {default: () => h(icon)})
}

const layout = ref("table")
const viewState = ref("data")
const loading = ref(false)
const checkedRowKeys = ref([])

// Selected rows can come from a page that's no longer loaded once the user
// pages on — remember every row we've ever seen so bulk actions still know
// each checked id's staff_id.
const knownRows = ref(new Map())
watch(() => props.directory.data, (rows) => {
    rows.forEach(row => knownRows.value.set(row.id, row))
}, {immediate: true})

function fetchDirectory(query) {
    router.get(route("certificates.index"), query, {
        preserveState: true,
        onStart: () => { loading.value = true },
        onFinish: () => { loading.value = false },
        onSuccess: () => {
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.directory.current_page,
                pageSize: props.directory.per_page,
                pageCount: props.directory.last_page,
            }
        }
    })
}

const statusFilterValue = ref(router.page.props.ziggy.query.status ?? "all")
const statusFilter = computed({
    get() { return statusFilterValue.value },
    set(value) {
        statusFilterValue.value = value
        checkedRowKeys.value = []
        fetchDirectory({...router.page.props.ziggy.query, status: value, page: 1})
    }
})

const paginationReactive = ref({
    page: props.directory.current_page,
    pageSize: props.directory.per_page,
    pageCount: props.directory.last_page,
    showSizePicker: true,
    pageSizes: [12, 24, 48],
    onChange: (page) => fetchDirectory({...router.page.props.ziggy.query, page}),
    onUpdatePageSize: (pageSize) => fetchDirectory({...router.page.props.ziggy.query, page: 1, page_size: pageSize}),
})

function handleCheckedRowKeysChange(rowKeys) {
    checkedRowKeys.value = rowKeys
}

const checkedStaffIds = computed(() => checkedRowKeys.value
    .map(id => knownRows.value.get(id)?.staff_id)
    .filter(Boolean))

const checkedDownloadUrl = computed(() => route("certification.download", {staff_ids: checkedStaffIds.value}, true))

function onExportSelected() {
    window.location.href = route("staff.export", {
        _query: {staff_ids: checkedStaffIds.value}
    })
}

const selectedId = ref(props.directory.data[0]?.id ?? null)
watch(() => props.directory.data, (rows) => {
    if (!rows.find(r => r.id === selectedId.value)) {
        selectedId.value = rows[0]?.id ?? null
    }
})
const selected = computed(() => props.directory.data.find(c => c.id === selectedId.value) ?? null)

function downloadUrl(cert) {
    return route("certification.download", {staff_ids: [cert.staff_id]}, true)
}

function progressColor(status) {
    const type = certificateStatusDef(status).type
    return type === "success" ? "#18a058" : type === "warning" ? "#f0a020" : "#d03050"
}

function progressPercentage(cert) {
    const days = certificateDaysLeft(cert.valid_to)
    if (days === null || cert.status === "expired" || cert.status === "revoked") return 100
    return Math.max(0, Math.min(100, Math.round(100 - (days / 365) * 100)))
}

const columnsRef = ref([
    {
        type: "selection",
    },
    {
        title: "Владелец",
        key: "fio",
        maxWidth: 400,
        minWidth: 320,
        ellipsis: {tooltip: true},
        render(row) {
            return h(NFlex, {align: "center", size: 12, wrap: false}, () => [
                h(NAvatar, {round: true, color: avatarColor(row.staff_id), style: "color:#fff;font-weight:600;flex:none"}, () => staffInitials(row.fio)),
                h("div", {class: "min-w-0"}, [
                    h("div", {class: "font-medium truncate"}, row.fio),
                    h("div", {class: "text-xs text-[var(--n-close-icon-color)] truncate"}, row.position),
                ])
            ])
        }
    },
    {
        title: "СНИЛС",
        key: "snils",
        width: 130,
        render (row) {
            return h(
                'span',
                {
                    class: 'text-nowrap font-mono'
                },
                row.snils
            )
        }
    },
    {
        title: "Серийный номер",
        key: "serial_number",
        width: 320,
        ellipsis: {tooltip: true},
        render (row) {
            return h(
                'span',
                {
                    class: 'text-nowrap font-mono'
                },
                row.serial_number
            )
        }
    },
    {
        title: "Срок действия",
        key: "valid_to",
        width: 190,
        sortOrder: false,
        sorter: true,
        render(row) {
            return h("div", null, [
                h("div", {class: "text-xs"}, [
                    h(NTime, {format: "dd.MM.yyyy", time: Number(row.valid_from)}),
                    " – ",
                    h(NTime, {format: "dd.MM.yyyy", time: Number(row.valid_to)}),
                ]),
                h("div", {class: "text-xs mt-0.5", style: {color: progressColor(row.status)}}, certificateDaysLabel(row)),
            ])
        }
    },
    {
        title: "Статус",
        key: "status",
        width: 140,
        render(row) {
            return h(StatusTag, {status: row.status})
        }
    },
    {
        title: "Действия",
        key: "actions",
        width: 120,
        render(row) {
            return h(NFlex, {size: 6, wrap: false}, () => [
                h(NButton, {size: "small", quaternary: true, title: "Подробнее", onClick: () => emit("open-detail", row)}, {icon: renderIcon(IconEye)}),
                hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)
                    ? h(NButton, {size: "small", quaternary: true, tag: "a", href: downloadUrl(row), target: "_blank", title: "Скачать", onClick: (e) => e.stopPropagation()}, {icon: renderIcon(IconDownload)})
                    : null,
            ])
        }
    }
])

function handleSorterChange(sorter) {
    const order = sorter.order === false ? "default" : sorter.order.replace("end", "")
    router.visit(route("certificates.index", {...router.page.props.ziggy.query, sort_key: sorter.columnKey, sort_order: order, page: 1}), {
        preserveState: true,
        onStart: () => { loading.value = true },
        onFinish: () => { loading.value = false },
        onSuccess: () => {
            const query = router.page.props.ziggy.query
            const col = columnsRef.value.find(itm => itm.key === query.sort_key)
            if (col) col.sortOrder = query.sort_order === 'default' ? false : `${query.sort_order}end`
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.directory.current_page,
                pageSize: props.directory.per_page,
                pageCount: props.directory.last_page,
            }
        }
    })
}
</script>

<template>
    <NFlex vertical :size="16">
        <NGrid :cols="4" :x-gap="16">
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <NEl class="p-1.5 bg-[color-mix(in_srgb,var(--success-color)_30%,transparent)] flex items-center justify-center rounded-lg">
                                <NIcon :component="IconShield" class="text-[var(--success-color-hover)]" />
                            </NEl>
                            <span>Всего сертификатов</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.total" />
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <NEl class="p-1.5 bg-[color-mix(in_srgb,var(--success-color)_30%,transparent)] flex items-center justify-center rounded-lg">
                                <NIcon :component="IconCheck" class="text-[var(--success-color-hover)]" />
                            </NEl>
                            <span>Действительны</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.valid" />
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <NEl class="p-1.5 bg-[color-mix(in_srgb,var(--warning-color)_30%,transparent)] flex items-center justify-center rounded-lg">
                                <NIcon :component="IconClock" class="text-[var(--warning-color-hover)]" />
                            </NEl>
                            <span>Истекают</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.expiring" />
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <NEl class="p-1.5 bg-[color-mix(in_srgb,var(--error-color)_30%,transparent)] flex items-center justify-center rounded-lg">
                                <NIcon :component="IconAlertTriangle" class="text-[var(--error-color-hover)]" />
                            </NEl>
                            <span>Истекли / отозваны</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.expired" />
                </EdsWidget>
            </NGi>
        </NGrid>

        <NFlex justify="space-between" align="center" :wrap="true">
            <NRadioGroup v-model:value="statusFilter" :disabled="loading">
                <NRadioButton label="Все" value="all" />
                <NRadioButton label="Действительны" value="valid" />
                <NRadioButton label="Истекают" value="expiring" />
                <NRadioButton label="Истекли / отозваны" value="expired" />
            </NRadioGroup>

            <NFlex align="center" :size="20">
                <NFlex v-if="layout === 'table' && checkedRowKeys.length" align="center" :size="12">
                    <NButton v-if="hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)" tag="a" target="_blank" secondary :href="checkedDownloadUrl">
                        <template #icon>
                            <NIcon :component="IconFileZip" />
                        </template>
                        Скачать ({{ checkedRowKeys.length }})
                    </NButton>
                    <NButton secondary @click="onExportSelected">
                        <template #icon>
                            <NIcon :component="IconFileSpreadsheet" />
                        </template>
                        Экспортировать в Excel
                    </NButton>
                </NFlex>
                <NRadioGroup v-model:value="layout">
                    <NRadioButton value="table">
                        <NIcon :component="IconLayoutList" />
                    </NRadioButton>
                    <NRadioButton value="cards">
                        <NIcon :component="IconLayoutGrid" />
                    </NRadioButton>
                    <NRadioButton value="split">
                        <NIcon :component="IconLayoutSidebarRightExpand" />
                    </NRadioButton>
                </NRadioGroup>
            </NFlex>
        </NFlex>

        <NCard v-if="viewState === 'loading'">
            <NFlex v-for="n in 6" :key="n" align="center" :size="16" class="mb-4">
                <NSkeleton circle :width="40" />
                <NFlex vertical :size="8" style="flex:1">
                    <NSkeleton text style="width: 42%" />
                    <NSkeleton text style="width: 26%" />
                </NFlex>
                <NSkeleton text style="width: 90px; height: 24px" />
            </NFlex>
        </NCard>

        <NCard v-else-if="viewState === 'error'">
            <NResult status="error" title="Не удалось получить список сертификатов" description="Сервис временно недоступен. Попробуйте повторить запрос.">
                <template #footer>
                    <NFlex justify="center">
                        <NButton type="primary" @click="viewState = 'data'">
                            Повторить попытку
                        </NButton>
                    </NFlex>
                </template>
            </NResult>
        </NCard>

        <NEmpty v-else-if="viewState === 'empty' || !directory.data.length" description="Сертификаты не найдены" class="py-20">
            <template #icon>
                <NIcon :component="IconCertificate" />
            </template>
            <template #extra>
                <NButton v-if="hasScope(scopes.CAN_CREATE_STAFF)" type="primary" @click="emit('open-wizard')">
                    Загрузить первый сертификат
                </NButton>
            </template>
        </NEmpty>

        <template v-else-if="layout === 'table'">
            <NDataTable remote size="small"
                        :columns="columnsRef"
                        :data="directory.data"
                        min-height="calc(100vh - 388px)"
                        max-height="calc(100vh - 388px)"
                        :row-key="row => row.id"
                        :checked-row-keys="checkedRowKeys"
                        :loading="loading"
                        @update:checked-row-keys="handleCheckedRowKeysChange"
                        @update:sorter="handleSorterChange"
                        :pagination="paginationReactive" />
        </template>

        <template v-else-if="layout === 'cards'">
            <NGrid :cols="4" :x-gap="16" :y-gap="16" class="min-h-[calc(100vh-350px)]">
                <NGi v-for="cert in directory.data" :key="cert.id">
                    <NCard hoverable class="cursor-pointer" @click="emit('open-detail', cert)">
                        <NFlex align="center" :wrap="false" class="mb-3">
                            <NAvatar round :color="avatarColor(cert.staff_id)" style="color:#fff;font-weight:600;flex:none">
                                {{ staffInitials(cert.fio) }}
                            </NAvatar>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium truncate">{{ cert.fio }}</div>
                                <div class="text-xs text-[var(--n-close-icon-color)] truncate">{{ cert.position }}</div>
                            </div>
                            <StatusTag :status="cert.status" />
                        </NFlex>
                        <NFlex justify="space-between" class="text-sm mb-1">
                            <NText depth="3">СНИЛС</NText>
                            <NText class="font-mono">{{ cert.snils }}</NText>
                        </NFlex>
                        <NFlex justify="space-between" class="text-sm mb-3">
                            <NText depth="3">Действует до</NText>
                            <NTime format="dd.MM.yyyy" :time="Number(cert.valid_to)" />
                        </NFlex>
                        <NProgress type="line" :percentage="progressPercentage(cert)" :color="progressColor(cert.status)" :show-indicator="false" />
                        <div class="text-xs mt-1" :style="{color: progressColor(cert.status)}">
                            {{ certificateDaysLabel(cert) }}
                        </div>
                    </NCard>
                </NGi>
            </NGrid>
            <NFlex justify="end">
                <NPagination :page="paginationReactive.page" :page-size="paginationReactive.pageSize" :page-count="paginationReactive.pageCount"
                             show-size-picker :page-sizes="paginationReactive.pageSizes"
                             @update:page="paginationReactive.onChange" @update:page-size="paginationReactive.onUpdatePageSize" />
            </NFlex>
        </template>

        <template v-else>
            <NGrid :cols="3" :x-gap="16">
                <NGi :span="1">
                    <NCard content-style="padding:0" content-class="h-[calc(100vh-350px)]" content-scrollable>
                        <div v-for="cert in directory.data" :key="cert.id"
                             class="flex items-center gap-3 px-4 py-3 border-b border-[var(--n-border-color)] transition-colors cursor-pointer hover:bg-[var(--n-close-color-hover)]"
                             :class="{'bg-[var(--n-close-color-pressed)]': cert.id === selected?.id}"
                             @click="selectedId = cert.id"
                        >
                            <NAvatar round :color="avatarColor(cert.staff_id)" style="color:#fff;font-weight:600;flex:none" size="small">
                                {{ staffInitials(cert.fio) }}
                            </NAvatar>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate">{{ cert.fio }}</div>
                                <div class="text-xs text-[var(--n-close-icon-color)] truncate">{{ cert.position }}</div>
                            </div>
                        </div>
                    </NCard>
                    <NFlex justify="center" class="mt-3">
                        <NPagination :page="paginationReactive.page" :page-size="paginationReactive.pageSize" :page-count="paginationReactive.pageCount"
                                     :page-sizes="paginationReactive.pageSizes" simple
                                     @update:page="paginationReactive.onChange" @update:page-size="paginationReactive.onUpdatePageSize" />
                    </NFlex>
                </NGi>
                <NGi :span="2">
                    <NCard v-if="selected">
                        <NFlex align="center" class="mb-4">
                            <NAvatar round :size="54" :color="avatarColor(selected.staff_id)" style="color:#fff;font-weight:600">
                                {{ staffInitials(selected.fio) }}
                            </NAvatar>
                            <div class="flex-1">
                                <div class="text-lg font-semibold">{{ selected.fio }}</div>
                                <div class="text-sm text-[var(--n-close-icon-color)]">{{ selected.position }}</div>
                            </div>
                            <StatusTag :status="selected.status" :size="'medium'" />
                        </NFlex>
                        <NDescriptions label-placement="top" :column="2" bordered>
                            <NDescriptionsItem label="СНИЛС"><NText class="font-mono">{{ selected.snils }}</NText></NDescriptionsItem>
                            <NDescriptionsItem label="Серийный номер"><NText class="font-mono">{{ selected.serial_number }}</NText></NDescriptionsItem>
                            <NDescriptionsItem label="Действителен с">
                                <NTime format="dd.MM.yyyy" :time="Number(selected.valid_from)" />
                            </NDescriptionsItem>
                            <NDescriptionsItem label="Действителен по">
                                <NTime format="dd.MM.yyyy" :time="Number(selected.valid_to)" />
                            </NDescriptionsItem>
                            <NDescriptionsItem label="Алгоритм">ГОСТ Р 34.10-2012</NDescriptionsItem>
                            <NDescriptionsItem label="Файл">{{ selected.file_certification }}</NDescriptionsItem>
                        </NDescriptions>
                        <NProgress class="mt-4" type="line" :percentage="progressPercentage(selected)" :color="progressColor(selected.status)" :show-indicator="false" />
                        <div class="text-xs mt-1" :style="{color: progressColor(selected.status)}">
                            {{ certificateDaysLabel(selected) }}
                        </div>
                        <NFlex class="mt-4">
                            <NButton tag="a" :href="downloadUrl(selected)" target="_blank" secondary v-if="hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)">
                                <template #icon>
                                    <NIcon :component="IconDownload" />
                                </template>
                                Скачать
                            </NButton>
                            <NButton secondary v-if="hasScope(scopes.CAN_CREATE_STAFF)" @click="emit('open-wizard')">
                                <template #icon>
                                    <NIcon :component="IconRefresh" />
                                </template>
                                Продлить
                            </NButton>
                            <NPopconfirm v-if="selected.status !== 'revoked' && hasScope(scopes.CAN_DELETE_STAFF)" @positive-click="emit('revoke', selected)">
                                <template #trigger>
                                    <NButton type="error" secondary :loading="revoking">
                                        <template #icon>
                                            <NIcon :component="IconBan" />
                                        </template>
                                        Отозвать
                                    </NButton>
                                </template>
                                Отозвать сертификат «{{ selected.serial_number }}»? Это действие необратимо.
                            </NPopconfirm>
                        </NFlex>
                    </NCard>
                </NGi>
            </NGrid>
        </template>
    </NFlex>
</template>
