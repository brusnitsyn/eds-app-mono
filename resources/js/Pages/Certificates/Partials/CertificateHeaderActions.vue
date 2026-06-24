<script setup>
import {NButton, NIcon, NInput} from "naive-ui"
import {IconSearch, IconSquareRoundedPlus} from "@tabler/icons-vue"
import {useCheckScope} from "@/Composables/useCheckScope.js"

const emit = defineEmits(["open-search", "open-wizard"])

const {hasScope, scopes} = useCheckScope()

// Plain click handler only — NOT @focus too. The search modal restores focus to
// this input when it closes; if @focus also opened the modal, that refocus would
// immediately reopen it, making the modal impossible to close.
function open() {
    // Blur whatever actually has focus (not necessarily e.target — could be the
    // prefix icon), so the modal's focus-restore-on-close has nothing to land on.
    document.activeElement?.blur?.()
    emit("open-search")
}
</script>

<template>
    <NInput readonly placeholder="Поиск по сотрудникам и сертификатам…" style="width: 260px; cursor: pointer" @click="open">
        <template #prefix>
            <NIcon :component="IconSearch" />
        </template>
        <template #suffix>
            <span class="text-xs text-[var(--n-close-icon-color)] border border-[var(--n-border-color)] rounded px-1">Ctrl K</span>
        </template>
    </NInput>

    <NButton v-if="hasScope(scopes.CAN_CREATE_STAFF)" type="primary" @click="emit('open-wizard')">
        <template #icon>
            <NIcon :component="IconSquareRoundedPlus" />
        </template>
        Загрузить УКЭП
    </NButton>
</template>
