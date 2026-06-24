<script setup>
import {NTimeline, NTimelineItem, NRadioGroup, NRadioButton, NEmpty, NCard, NTime, NIcon, NFlex, NPagination} from "naive-ui"
import {computed, ref, watch} from "vue"
import {IconUpload, IconBan} from "@tabler/icons-vue"

const props = defineProps({
    events: Array,
})

const typeFilter = ref("all")

const filtered = computed(() => typeFilter.value === "all"
    ? props.events
    : props.events.filter(e => e.type === typeFilter.value))

const page = ref(1)
const pageSize = ref(50)
watch(filtered, () => { page.value = 1 })

const paged = computed(() => {
    const start = (page.value - 1) * pageSize.value
    return filtered.value.slice(start, start + pageSize.value)
})

function eventIcon(type) {
    return type === "revoke" ? IconBan : IconUpload
}

function eventColor(type) {
    return type === "revoke" ? "#d03050" : "#18a058"
}
</script>

<template>
    <NFlex vertical :size="16">
        <NRadioGroup v-model:value="typeFilter">
            <NRadioButton label="Все" value="all" />
            <NRadioButton label="Загрузка" value="upload" />
            <NRadioButton label="Отзыв" value="revoke" />
        </NRadioGroup>

        <NCard class="h-[calc(100vh-210px)]" content-scrollable>
            <NEmpty v-if="!filtered.length" description="Событий пока нет" class="py-16" />
            <NFlex v-else vertical :size="0">
                <NFlex v-for="event in paged" :key="event.id" align="center" :size="13" class="py-3 border-b last:border-b-0 border-[var(--n-border-color)]">
                    <span class="w-[9px] h-[9px] rounded-full flex-none" :style="{background: eventColor(event.type)}" />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ event.detail }}</div>
                        <div class="text-xs text-[var(--n-close-icon-color)] truncate">{{ event.owner }}</div>
                    </div>
                    <NTag size="small" :type="event.type === 'revoke' ? 'error' : 'success'">
                        {{ event.type === "revoke" ? "Отзыв" : "Загрузка" }}
                    </NTag>
                    <NTime :time="new Date(event.time)" format="HH:mm" class="text-xs text-[var(--n-close-icon-color)] flex-none" style="width: 40px; text-align: right" />
                </NFlex>
            </NFlex>
        </NCard>

        <NFlex v-if="filtered.length" justify="end">
            <NPagination v-model:page="page" v-model:page-size="pageSize" :item-count="filtered.length" show-size-picker :page-sizes="[10, 20, 50]" />
        </NFlex>
    </NFlex>
</template>
