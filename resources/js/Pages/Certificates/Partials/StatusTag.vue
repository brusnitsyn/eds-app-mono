<script setup>
import {NTag} from "naive-ui"
import {certificateStatusDef} from "@/Utils/certificateStatus.js"

const props = defineProps({
    status: {type: String, required: true},
    size: {type: String, default: "small"},
    round: {type: Boolean, default: true},
})

const dotColors = {success: "#18a058", warning: "#f0a020", error: "#d03050", default: "#909399"}
</script>

<template>
    <NTag :type="certificateStatusDef(status).type" :size="size" :round="round">
        <span class="inline-flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full" :style="{background: dotColors[certificateStatusDef(status).type] ?? dotColors.default}" />
            {{ certificateStatusDef(status).label }}
        </span>
    </NTag>
</template>
