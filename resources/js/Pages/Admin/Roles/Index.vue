<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import DataTable from "@/Pages/Admin/Roles/Partials/DataTable.vue"
const props = defineProps({
    roles: {
        type: Array
    }
})
</script>

<template>
    <AppLayout>
        <DataTable :roles="roles" />
    </AppLayout>
</template>

<style scoped>

</style>
