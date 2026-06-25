<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import StaffPanel from "./Partials/StaffPanel.vue"
import StaffDetailDrawer from "./Partials/StaffDetailDrawer.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"

defineProps({
    certificates: Array,
    staff: Array,
    directory: Object,
    mis: Object,
})

const overlays = ref(null)

const revokeForm = useForm({})

function revoke(cert) {
    revokeForm.post(route("certificates.revoke", cert.id), {
        onSuccess: () => window.$message?.success("Сертификат отозван"),
    })
}

const selectedRow = ref(null)
const detailOpen = ref(false)

function openDetail(row) {
    selectedRow.value = row
    detailOpen.value = true
}
</script>

<template>
    <AppLayout title="Сотрудники" subtitle="Медицинский персонал и привязка УКЭП">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <StaffPanel :directory="directory" :mis="mis" @open-detail="openDetail" />

        <CertificateOverlays ref="overlays" :certificates="certificates" :staff="staff" :revoking="revokeForm.processing" @revoke="revoke" />
        <StaffDetailDrawer v-model:show="detailOpen" :row="selectedRow" />
    </AppLayout>
</template>
