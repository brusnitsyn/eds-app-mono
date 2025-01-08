<script setup>
import {Link} from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
</script>

<template>
    <AppLayout>
        <template #header>
            Журналы
        </template>
        <NGrid cols="3" x-gap="6" y-gap="6">
            <NGridItem>
                <NCard title="Журнал">
                    <Link :href="route('journal.patient-falling')">
                        Журнал регистрации падений пациентов и посетителей
                    </Link>
                </NCard>
            </NGridItem>
            <NGridItem>
                <NCard title="Журнал">
                    Журнал регистрации падений пациентов и посетителей
                </NCard>
            </NGridItem>
            <NGridItem>
                <NCard title="Журнал">
                    Журнал регистрации падений пациентов и посетителей
                </NCard>
            </NGridItem>
            <NGridItem>
                <NCard title="Журнал">
                    Журнал регистрации падений пациентов и посетителей
                </NCard>
            </NGridItem>
            <NGridItem>
                <NCard title="Журнал">
                    Журнал регистрации падений пациентов и посетителей
                </NCard>
            </NGridItem>
        </NGrid>
    </AppLayout>
</template>

<style scoped>

</style>
