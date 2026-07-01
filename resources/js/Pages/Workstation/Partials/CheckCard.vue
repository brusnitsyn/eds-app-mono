<script setup>
import {NCard, NIcon, NFlex, NText, NDivider, NSpin} from "naive-ui"
import {IconCircleCheck, IconCircleX} from "@tabler/icons-vue"

const props = defineProps({
    status: {type: String, required: true},   // 'pending' | 'ok' | 'fail'
    icon: {type: Object, required: true},
    title: {type: String, required: true},
    subtitle: {type: String, default: ''},
    description: {type: String, default: ''},
})

const statusColor = computed(() => ({
    pending: 'var(--n-close-icon-color)',
    ok: '#18a058',
    fail: '#d03050',
})[props.status] ?? 'var(--n-close-icon-color)')
</script>

<template>
    <NCard>
        <NFlex align="flex-start" :size="16" :wrap="false">
            <NIcon :component="icon" :size="28" :style="{color: statusColor, flexShrink: 0, marginTop: '2px'}" />
            <NFlex vertical :size="4" style="flex:1;min-width:0">
                <NFlex align="center" justify="space-between" :wrap="false">
                    <span style="font-weight:600;font-size:15px">{{ title }}</span>
                    <NSpin v-if="status === 'pending'" size="small" />
                    <NIcon v-else-if="status === 'ok'" :component="IconCircleCheck" :size="20" :style="{color: statusColor}" />
                    <NIcon v-else :component="IconCircleX" :size="20" :style="{color: statusColor}" />
                </NFlex>
                <NText depth="3" style="font-size:12px">{{ subtitle }}</NText>
                <NDivider style="margin:8px 0" />
                <NText depth="3" style="font-size:13px">{{ description }}</NText>
                <div v-if="status === 'fail'" style="margin-top:12px">
                    <slot name="action" />
                </div>
            </NFlex>
        </NFlex>
    </NCard>
</template>
