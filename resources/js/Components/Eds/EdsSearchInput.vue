<script setup>
import {useDebouncedRefHistory} from "@vueuse/core";
import {IconSearch} from "@tabler/icons-vue";
import {NInput} from "naive-ui";

const model = defineModel('search')
const props = defineProps({
    debounce: {
        type: Number,
        default: 500
    },
    placeholder: String,
    loading: Boolean,
    size: {
        type: String,
        default: 'large'
    }
})
const emits = defineEmits(['searched'])

const searchValue = shallowRef('')
const searchInputRef = ref()
const { last } = useDebouncedRefHistory(searchValue, { debounce: props.debounce })

watch(() => last.value, (newSearch) => {
    model.value = newSearch.snapshot
})

onMounted(() => {
    searchInputRef.value.focus()
})
</script>

<template>
    <NInputGroup class="max-w-xl">
        <NInput ref="searchInputRef" v-model:value="searchValue" autofocus :size="size" :placeholder="placeholder" @keydown.enter.prevent="emits('searched', last)" :loading="loading" />
        <NButton :loading="loading" :size="size" @click="emits('searched', last)">
            <template #icon>
                <NIcon :component="IconSearch" />
            </template>
        </NButton>
    </NInputGroup>
</template>

<style scoped>

</style>
