<script setup>
import {NFlex, NTooltip, NButton, useMessage, NIcon} from "naive-ui";
import {IconCopy} from "@tabler/icons-vue";
import {h} from "vue";
const props = defineProps({
    value: {
        type: String
    }
})

const message = useMessage()
function copyValue() {
    navigator.clipboard.writeText(props.value)
    message.success('Значение скопировано в буфер обмена', {
        icon: () => h(NIcon, null, { default: () => h(IconCopy) })
    })
}
</script>

<template>
    <NFlex>
        <NTooltip trigger="hover">
            <template #trigger>
                <NButton text @click="copyValue">
                    <NIcon :component="IconCopy" :size="18" />
                </NButton>
            </template>
            Скопировать значение
        </NTooltip>
    </NFlex>
</template>

<style scoped>

</style>
