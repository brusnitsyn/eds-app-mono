<script setup>
import {IconExternalLink, IconRefresh} from "@tabler/icons-vue";
const props = defineProps({
    title: {
        type: String,
        default: 'Заголовок карты'
    },
    subtitle: {
        type: String,
        default: '999'
    },
    href: {
        type: String
    },
    refreshHidden: {
        type: Boolean,
        default: false
    }
})
const emits = defineEmits(['clickRefresh', 'clickNavigate'])
</script>

<template>
    <NCard :title="title" header-class="!pb-2" footer-class="!p-0">
        <NP v-if="props.subtitle" class="text-3xl font-medium">
            {{ props.subtitle }}
        </NP>
        <template #footer>
            <slot v-if="$slots.action" name="action" />
            <NFlex v-else :vertical="false" :wrap="false" :size="0" class="-mx-px -mb-px">
                <NButton v-if="!refreshHidden" class="rounded-none h-10 w-1/2" @click="emits('clickRefresh')">
                    <template #icon>
                        <NIcon :component="IconRefresh" />
                    </template>
                    Обновить
                </NButton>
                <NButton tag="a" :href="props.href" class="rounded-none h-10" :block="refreshHidden" :class="refreshHidden ? '' : 'w-[calc(50%+1px)] -ml-px'">
                    <template #icon>
                        <NIcon :component="IconExternalLink" />
                    </template>
                    Перейти
                </NButton>
            </NFlex>
        </template>
    </NCard>
</template>

<style scoped>

</style>
