<script setup>
import {computed, nextTick, onUnmounted, ref, watch} from "vue"
import {watchDebounced} from "@vueuse/core"
import {NModal, NInput, NIcon, NEmpty, NScrollbar, NTag, NAvatar, NButton, NSpin} from "naive-ui"
import {IconSearch, IconCornerDownLeft} from "@tabler/icons-vue"
import {staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import StatusTag from "./StatusTag.vue"

const show = defineModel("show")

const emit = defineEmits(["select-certificate", "select-staff"])

const query = ref("")
const inputRef = ref(null)
const activeIndex = ref(0)
const certResults = ref([])
const staffResults = ref([])
const searching = ref(false)

let requestToken = 0

async function runSearch(q) {
    const token = ++requestToken
    if (!q) {
        certResults.value = []
        staffResults.value = []
        searching.value = false
        return
    }
    searching.value = true
    try {
        const {data} = await window.axios.get(route("certificates.search"), {params: {q}})
        if (token !== requestToken) return
        certResults.value = data.certificates
        staffResults.value = data.staff
    } finally {
        if (token === requestToken) searching.value = false
    }
}

watchDebounced(query, (q) => runSearch(q.trim()), {debounce: 300})

const flatResults = computed(() => [
    ...certResults.value.map(item => ({type: "cert", item})),
    ...staffResults.value.map(item => ({type: "staff", item})),
])

watch(flatResults, () => {
    activeIndex.value = 0
})

function close() {
    show.value = false
}

watch(show, (visible) => {
    if (visible) {
        query.value = ""
        activeIndex.value = 0
        nextTick(() => inputRef.value?.focus())
        window.addEventListener("keydown", onKeydown)
    } else {
        window.removeEventListener("keydown", onKeydown)
    }
})

onUnmounted(() => window.removeEventListener("keydown", onKeydown))

function isActive(item) {
    return flatResults.value[activeIndex.value]?.item === item
}

function hover(item) {
    const idx = flatResults.value.findIndex(r => r.item === item)
    if (idx !== -1) activeIndex.value = idx
}

function choose(result) {
    if (!result) return
    if (result.type === "cert") emit("select-certificate", result.item)
    else emit("select-staff", result.item)
    close()
}

function onKeydown(e) {
    if (e.key === "ArrowDown") {
        e.preventDefault()
        activeIndex.value = Math.min(activeIndex.value + 1, flatResults.value.length - 1)
    } else if (e.key === "ArrowUp") {
        e.preventDefault()
        activeIndex.value = Math.max(activeIndex.value - 1, 0)
    } else if (e.key === "Enter") {
        e.preventDefault()
        choose(flatResults.value[activeIndex.value])
    } else if (e.key === "Escape") {
        e.preventDefault()
        close()
    }
}
</script>

<template>
    <NModal v-model:show="show" preset="card" style="width: 600px" content-style="padding:0" :bordered="false" :mask-closable="true">
        <template #header>
            Поиск
        </template>

        <div class="flex items-center gap-3 px-4 border-b border-[var(--n-border-color)]" style="height: 54px">
            <NIcon :component="IconSearch" :size="18" class="text-[var(--n-close-icon-color)] flex-none" />
            <NInput ref="inputRef" v-model:value="query" placeholder="Поиск по сотрудникам и сертификатам…" :bordered="false" size="large" />
            <NButton size="tiny" quaternary class="flex-none" @click="close">
                Esc
            </NButton>
        </div>

        <NScrollbar style="max-height: 360px">
            <div v-if="!query" class="py-12 text-center text-[var(--n-close-icon-color)] text-sm">
                Начните вводить ФИО, СНИЛС или номер сертификата…
            </div>
            <div v-else-if="searching && !flatResults.length" class="py-12 flex justify-center">
                <NSpin size="small" />
            </div>
            <NEmpty v-else-if="!flatResults.length" description="Ничего не найдено" class="py-12" />
            <div v-else class="py-2">
                <template v-if="certResults.length">
                    <div class="px-4 pt-2 pb-1 text-xs text-[var(--n-close-icon-color)] uppercase tracking-wide">Сертификаты</div>
                    <div v-for="c in certResults" :key="'cert-' + c.id"
                         class="flex items-center gap-3 px-4 py-2 cursor-pointer"
                         :class="isActive(c) ? 'bg-emerald-50 dark:bg-emerald-500/10' : ''"
                         @mouseenter="hover(c)" @click="choose({type: 'cert', item: c})">
                        <NAvatar round size="small" :color="avatarColor(c.staff_id)" style="color:#fff;font-weight:600">
                            {{ staffInitials(c.fio) }}
                        </NAvatar>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ c.fio }}</div>
                            <div class="text-xs text-[var(--n-close-icon-color)] truncate">{{ c.serial_number }}</div>
                        </div>
                        <StatusTag :status="c.status" />
                    </div>
                </template>

                <template v-if="staffResults.length">
                    <div class="px-4 pt-3 pb-1 text-xs text-[var(--n-close-icon-color)] uppercase tracking-wide">Сотрудники</div>
                    <div v-for="p in staffResults" :key="'staff-' + p.id"
                         class="flex items-center gap-3 px-4 py-2 cursor-pointer"
                         :class="isActive(p) ? 'bg-emerald-50 dark:bg-emerald-500/10' : ''"
                         @mouseenter="hover(p)" @click="choose({type: 'staff', item: p})">
                        <NAvatar round size="small" :color="avatarColor(p.id)" style="color:#fff;font-weight:600">
                            {{ staffInitials(p.fio) }}
                        </NAvatar>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ p.fio }}</div>
                            <div class="text-xs text-[var(--n-close-icon-color)] truncate">{{ p.position }}</div>
                        </div>
                    </div>
                </template>
            </div>
        </NScrollbar>

        <div class="flex items-center gap-4 px-4 border-t border-[var(--n-border-color)] text-xs text-[var(--n-close-icon-color)]" style="height: 38px">
            <span class="flex items-center gap-1"><IconCornerDownLeft :size="13" /> выбрать</span>
            <span>↑↓ навигация</span>
        </div>
    </NModal>
</template>
