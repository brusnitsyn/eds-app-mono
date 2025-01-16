<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import CreatePatientFallingForm from "@/Pages/Journals/PatientFalling/Partials/CreatePatientFallingForm.vue"
import {NModal} from "naive-ui"
import {IconSquareRoundedPlus} from "@tabler/icons-vue"
import {useI18n} from "vue-i18n"
import {usePage} from "@inertiajs/vue3";

defineProps({
    data: {
        type: Array,
    },
    divisions: {
        type: Array
    }
})

const page = usePage()

console.log(page)

const { t } = useI18n()

const columns = [
    {
        key: 'id',
        title: t('fallingPatient.id'),
    },
    {
        key: 'full_name_patient',
        title: t('fallingPatient.full_name_patient'),
    },
    {
        key: 'reason_event',
        title: t('fallingPatient.reason_event'),
    },
    {
        key: 'place_event',
        title: t('fallingPatient.place_event'),
    },
    {
        key: 'has_helping',
        title: t('fallingPatient.has_helping'),
    },
    {
        key: 'consequences',
        title: t('fallingPatient.consequences'),
    },
]

const hasShowCreatePatientFallingModal = ref(false)
</script>

<template>
    <AppLayout>
        <template #headermore>
            <NButton type="primary" @click="hasShowCreatePatientFallingModal = true">
                <template #icon>
                    <NIcon :component="IconSquareRoundedPlus" />
                </template>
                Добавить событие
            </NButton>
        </template>
        <NDataTable class="mt-4" :data="data" :columns="columns" />

        <NModal v-model:show="hasShowCreatePatientFallingModal" preset="card" class="max-w-2xl" title="Добавить событие" :bordered="false">
            <CreatePatientFallingForm @success="hasShowCreatePatientFallingModal = false" />
        </NModal>
    </AppLayout>
</template>

<style scoped>

</style>
