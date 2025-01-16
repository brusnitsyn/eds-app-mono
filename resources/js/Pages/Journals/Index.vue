<script setup>
import {Link, usePage} from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import {useI18n} from "vue-i18n";
const { t } = useI18n()

</script>

<template>
    <AppLayout>
        <template #header>
            {{ t('journal') }}
        </template>
        <NGrid cols="3" x-gap="16" y-gap="16">
            <NGridItem>
                <Link :href="route('journals.patient-falling.index')">
                    <NCard title="Журнал" hoverable>
                            Журнал регистрации падений пациентов и посетителей
                    </NCard>
                </Link>
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
