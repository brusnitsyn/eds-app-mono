<script setup>
import {NCard, NEl} from 'naive-ui'
const props = defineProps({
    header: {
        type: String
    }
})
</script>

<template>
    <NCard header-style="padding: 0;" class="rounded-xl">
        <template v-if="$slots.header || header" #header>
            <NEl class="border-b border-[var(--n-border-color)]" style="font-size: var(--font-size-medium)">
                <NFlex align="center" justify="space-between">
                    <div class="p-3 pl-6">
                        <span v-if="header">{{header}}</span>
                        <slot v-else name="header" />
                    </div>
                    <div class="pr-6">
                        <slot v-if="$slots['header-extra']" name="header-extra" />
                    </div>
                </NFlex>
            </NEl>
        </template>
        <div class="pt-2">
            <slot />
        </div>
    </NCard>
</template>

<style scoped>

</style>
