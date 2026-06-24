<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import DashboardPanel from "./Partials/DashboardPanel.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"

defineProps({
    certificates: Array,
    staff: Array,
    stats: Object,
    dashboard: Object,
})

const overlays = ref(null)

const revokeForm = useForm({})

function revoke(cert) {
    revokeForm.post(route("certificates.revoke", cert.id), {
        onSuccess: () => window.$message?.success("Сертификат отозван"),
    })
}
</script>

<template>
    <AppLayout title="Дашборд" subtitle="Обзор состояния сертификатов организации">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <DashboardPanel :stats="stats" :dashboard="dashboard" @open-detail="overlays.openDetail($event)" />

        <CertificateOverlays ref="overlays" :certificates="certificates" :staff="staff" :revoking="revokeForm.processing" @revoke="revoke" />
    </AppLayout>
</template>
