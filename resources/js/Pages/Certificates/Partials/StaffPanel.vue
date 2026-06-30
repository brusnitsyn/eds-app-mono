<script setup>
import {NCard, NFlex, NIcon, NTag, NButton, NDataTable, NText, NAvatar, NSpace} from "naive-ui"
import {computed, h, ref} from "vue"
import {router, useForm} from "@inertiajs/vue3"
import {IconDatabaseEdit, IconArrowRight} from "@tabler/icons-vue"
import {staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue"

const props = defineProps({
    directory: Object,
    mis: Object,
})

const emit = defineEmits(["open-detail"])

function certDef(row) {
    if (!row.has_certificate) return {label: "Нет УКЭП", type: "default"}
    if (row.cert_status === "expiring") return {label: "Истекает", type: "warning"}
    if (row.cert_status === "valid") return {label: "УКЭП активна", type: "success"}
    return {label: "УКЭП недействительна", type: "error"}
}

const columns = [
    {
        title: "Сотрудник",
        key: "fio",
        width: 328,
        render(row) {
            return h(NFlex, {align: "center", size: 12, wrap: false}, () => [
                h(NAvatar, {round: true, size: "small", color: avatarColor(row.mis_user_id ?? row.staff_id ?? 0), style: "color:#fff;font-weight:600;flex:none"}, () => staffInitials(row.fio)),
                h("span", {class: "font-medium"}, row.fio),
            ])
        }
    },
    {
        title: "Должность",
        key: "position",
        minWidth: 330,
        maxWidth: 430,
        ellipsis: {tooltip: true},
        render: (row) => {
            return h('span', {class: 'text-nowrap'}, row.position)
        }
    },
    {title: "СНИЛС", key: "snils", width: 130},
    {
        title: "Сертификат",
        width: 190,
        key: "cert_status",
        render(row) {
            const def = certDef(row)
            return h(NTag, {type: def.type, round: true, size: "small"}, () => def.label)
        }
    },
]

const form = useForm({search_value: router.page.props.ziggy.query.search_value})

const paginationReactive = ref({
    page: props.directory.current_page,
    pageSize: props.directory.per_page,
    pageCount: props.directory.last_page,
    showSizePicker: true,
    pageSizes: [25, 50, 100],
    onChange: (page) => {
        fetchDirectory({...router.page.props.ziggy.query, page})
    },
    onUpdatePageSize: (pageSize) => {
        fetchDirectory({...router.page.props.ziggy.query, page: 1, page_size: pageSize})
    }
})

function fetchDirectory(query) {
    router.get(route('staff'), {...query}, {
        preserveState: true,
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

function searchDirectory() {
    form.get(route('staff'), {
        preserveState: true,
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

const searchValue = computed({
    get() {
        return form.search_value
    },
    set(value) {
        form.search_value = value
        searchDirectory()
    }
})
</script>

<template>
    <NFlex vertical :size="16">
        <NCard>
            <NFlex align="center" :size="16">
                <NIcon :component="IconDatabaseEdit" size="28" color="#2080f0" />
                <div class="flex-1">
                    <NText strong>Синхронизация с МИС</NText>
                    <div class="text-xs text-[var(--n-close-icon-color)]">Синхронизировано {{ mis.synced_count }} из {{ mis.staff_total }} сотрудников</div>
                </div>
                <NButton tag="a" :href="route('mis.index')" secondary>
                    Перейти в раздел ТМ:МИС
                    <template #icon>
                        <NIcon :component="IconArrowRight" />
                    </template>
                </NButton>
            </NFlex>
        </NCard>

        <NSpace vertical>
            <EdsSearchInput v-model:search="searchValue" :debounce="500" @searched="searchDirectory" :loading="form.processing" size="medium" placeholder="ФИО или СНИЛС" />
        </NSpace>

        <NDataTable remote size="small" min-height="calc(100vh - 360px)" max-height="calc(100vh - 360px)" :columns="columns"
                    :data="directory.data" :row-key="row => row.id"
                    :loading="form.processing"
                    table-layout="fixed"
                    :row-props="row => ({style: 'cursor:pointer', onClick: () => emit('open-detail', row)})"
                    :pagination="paginationReactive" />
    </NFlex>
</template>
