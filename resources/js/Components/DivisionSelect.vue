<script setup>
import {usePage} from "@inertiajs/vue3";

const options = usePage().props.divisions
const value = defineModel('value')
</script>

<template>
    <NSelect placeholder="" v-model:value="value" :options="options" value-field="id" filterable />
</template>

<style scoped>

</style>
