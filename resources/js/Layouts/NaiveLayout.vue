<script setup>
import {NConfigProvider} from 'naive-ui'
import {ruRU, dateRuRU} from 'naive-ui'
import NMessageContent from "@/Components/Naive/NMessageContent.vue"
const props = defineProps({
    messagePlacement: {
        type: String,
        default: 'top'
    }
})
</script>

<template>
    <NDialogProvider />

    <NConfigProvider :locale="ruRU" :date-locale="dateRuRU">
        <NMessageProvider keep-alive-on-hover :placement="messagePlacement">
            <NMessageContent />
        </NMessageProvider>
        <slot />
    </NConfigProvider>
</template>

<style scoped>

</style>
