<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
    data: {
        type: Array,
    }
})

const columns = [
    {
        label: "ИД"
    }
]
</script>

<template>
    <AppLayout>
        <NDataTable :columns="columns" />
    </AppLayout>
</template>

<style scoped>

</style>
