<script setup>
import {IconPencil} from "@tabler/icons-vue"
import {NIcon} from "naive-ui"

defineProps({
    label: String,
    editing: Boolean,
    editable: {type: Boolean, default: true},
})

const emit = defineEmits(["edit"])
</script>

<template>
    <div class="group flex items-start gap-3 py-1.5 -mx-2 px-2 rounded-md transition-colors"
         :class="editing ? 'bg-[var(--n-close-color-hover)]' : 'hover:bg-[var(--n-close-color-hover)]'">
        <div class="w-[150px] flex-none text-sm text-[var(--n-close-icon-color)] pt-1">{{ label }}</div>
        <div class="flex-1 min-w-0">
            <slot v-if="editing" name="edit" />
            <div v-else class="flex items-center gap-2 min-h-[28px] cursor-pointer" :class="{'cursor-default': !editable}" @click="editable && emit('edit')">
                <div class="flex-1 min-w-0 text-sm truncate"><slot /></div>
                <NIcon v-if="editable" :component="IconPencil" :size="14" class="opacity-0 group-hover:opacity-60 transition-opacity flex-none" />
            </div>
        </div>
    </div>
</template>
