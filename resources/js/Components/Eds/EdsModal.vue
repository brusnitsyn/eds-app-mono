<script setup>
import {NModal} from "naive-ui";

const show = defineModel('show')
</script>

<template>
    <NModal v-model:show="show" preset="card" class="max-w-2xl">
        <slot />
    </NModal>
</template>

<style scoped>

</style>
