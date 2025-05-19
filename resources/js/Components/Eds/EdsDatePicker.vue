<script setup>
import {parse, isDate, format, fromUnixTime} from "date-fns";

const date = defineModel('value')
const nonFormattedValue = ref()
const formatedValue = computed({
    get() {
        if (date.value === null) return null
        const parsedDate = Date.parse(date.value)
        return parsedDate
    },
    set(value) {
        const formated = format(value, 'yyyy-MM-dd HH:mm:ss')
        date.value = formated
    }
})
</script>

<template>
    <NDatePicker
        v-model:value="formatedValue"
        v-mask="'##.##.####'"
        placeholder=""
        format="dd.MM.yyyy"
        type="date"
        class="w-full"
    />
</template>

<style scoped>

</style>
