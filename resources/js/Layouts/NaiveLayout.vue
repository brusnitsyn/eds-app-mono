<script setup>
import {NConfigProvider} from 'naive-ui'
import {ruRU, dateRuRU} from 'naive-ui'
import NMessageContent from "@/Components/Naive/NMessageContent.vue"
import {useAppTheme} from "@/Composables/useAppTheme.js"
const props = defineProps({
    messagePlacement: {
        type: String,
        default: 'top'
    }
})
const {theme} = useAppTheme()

// Сжимаем пункты бокового меню (по умолчанию naive-ui делает их выше, чем в макете).
const themeOverrides = {
    Menu: {
        itemHeight: '36px',
    },
}
</script>

<template>
    <NDialogProvider />

    <NConfigProvider :theme="theme" :theme-overrides="themeOverrides" :locale="ruRU" :date-locale="dateRuRU">
        <NMessageProvider keep-alive-on-hover :placement="messagePlacement">
            <NMessageContent />
        </NMessageProvider>
        <slot />
    </NConfigProvider>
</template>

<style scoped>

</style>
