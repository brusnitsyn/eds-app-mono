<script setup>
const props = defineProps({
    params: Array,
    max: {
        type: Number,
        default: 1
    },
    itemLabel: {
        type: String,
        default: 'name'
    }
})

const visibleParamsMax = computed(() => props.params.length === props.max ? 1 : props.max)
const visibleParams = computed(() => props.params.slice(0, visibleParamsMax.value))
const paramsClipCount = computed(() => props.params.length - visibleParamsMax.value)
</script>

<template>
    <NFlex :size="6" justify="space-between">
        <NSpace :size="4">
            <NTag v-if="params.length === max" v-for="param in visibleParams" type="info">
                {{ param[itemLabel] }}
            </NTag>
            <NTag v-else v-for="param in visibleParams" type="info">
                {{ param[itemLabel] }}
            </NTag>
        </NSpace>
        <NPopover v-if="params.length > visibleParamsMax" overlap :show-arrow="false" placement="top">
            <template #trigger>
                <NTag type="info">
                    +{{ paramsClipCount }}
                </NTag>
            </template>
            <NFlex :size="6" wrap class="max-w-[320px] py-1">
                <NTag v-for="param in params" type="info">
                    {{ param[itemLabel] }}
                </NTag>
            </NFlex>
        </NPopover>
    </NFlex>
</template>

<style scoped>

</style>
