<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import DashboardItem from "@/Components/Dashboard/DashboardItem.vue";
const props = defineProps({
    usersCount: Number
})
</script>

<template>
    <AppLayout title="ТМ:МИС">
        <div>
            <NGrid cols="2 s:3 m:3 l:4 xl:5" responsive="screen" x-gap="8" y-gap="8">
                <NGi>
                    <DashboardItem :href="route('mis.users')" title="Учетные записи" :subtitle="usersCount" refresh-hidden />
                </NGi>
                <NGi>
                    <DashboardItem :href="route('mis.templates.roles')" title="Шаблоны ролей" subtitle="Управление шаблонами" refresh-hidden />
                </NGi>
            </NGrid>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
