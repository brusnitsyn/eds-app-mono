<script setup>
const value = defineModel('value', {
    default: null
})
</script>

<template>
    <NDatePicker
        v-model:value="value"
        v-mask="'##.##.#### ##:##'"
        placeholder=""
        format="dd.MM.yyyy HH:mm"
        type="datetime"
        class="w-full"
    />
</template>

<style scoped>

</style>
