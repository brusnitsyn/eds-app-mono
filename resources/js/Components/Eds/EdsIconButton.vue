<script setup>

import {IconCopy} from "@tabler/icons-vue";
import {NButton, NFlex, NIcon, NTooltip} from "naive-ui";
const props = defineProps({
    icon: h,
    hint: String
})
</script>

<template>
    <NFlex>
        <NTooltip trigger="hover">
            <template #trigger>
                <NButton text>
                    <NIcon :component="icon" :size="18" />
                </NButton>
            </template>
            {{ hint }}
        </NTooltip>
    </NFlex>
</template>

<style scoped>

</style>
