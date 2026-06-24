<script setup>
import {NCard, NFlex, NIcon, NTag, NButton, NSelect, NDataTable, NText, NAvatar} from "naive-ui"
import {computed, h, ref} from "vue"
import {IconDatabaseEdit, IconArrowRight} from "@tabler/icons-vue"
import {staffInitials, avatarColor} from "@/Utils/certificateStatus.js"

const props = defineProps({
    staff: Array,
    divisions: Array,
    mis: Object,
})

const divisionFilter = ref(null)

const divisionOptions = computed(() => props.divisions.map(d => ({label: d.label, value: d.id})))

const filtered = computed(() => divisionFilter.value
    ? props.staff.filter(p => p.division_id === divisionFilter.value)
    : props.staff)

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
                h(NAvatar, {round: true, size: "small", color: avatarColor(row.id), style: "color:#fff;font-weight:600;flex:none"}, () => staffInitials(row.fio)),
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
    {
        title: "Источник",
        width: 120,
        key: "source",
        render(row) {
            return h(NTag, {type: row.source === "mis" ? "info" : "default", size: "small"}, () => row.source === "mis" ? "МИС" : "Вручную")
        }
    },
]
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

        <NDataTable size="small" min-height="calc(100vh - 295px)" max-height="calc(100vh - 295px)" :columns="columns"
                    :data="filtered" :row-key="row => row.id"
                    table-layout="fixed"
                    :pagination="{pageSize: 15, showSizePicker: true, pageSizes: [15, 30, 60]}" />
    </NFlex>
</template>
