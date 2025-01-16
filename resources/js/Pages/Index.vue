<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import DashboardItem from "@/Components/Dashboard/DashboardItem.vue";
</script>

<template>
    <AppLayout title="Главная">
        <div>
            <NGrid cols="2 s:3 m:3 l:4 xl:5" responsive="screen" x-gap="8" y-gap="8">
                <NGi>
                    <DashboardItem :href="route('staff.index')" title="Персонал" refresh-hidden />
                </NGi>
            </NGrid>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
