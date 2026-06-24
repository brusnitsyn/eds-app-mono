<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import StaffPanel from "./Partials/StaffPanel.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"

defineProps({
    certificates: Array,
    staff: Array,
    divisions: Array,
    mis: Object,
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
    <AppLayout title="Сотрудники" subtitle="Медицинский персонал и привязка УКЭП">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <StaffPanel :staff="staff" :divisions="divisions" :mis="mis" />

        <CertificateOverlays ref="overlays" :certificates="certificates" :staff="staff" :revoking="revokeForm.processing" @revoke="revoke" />
    </AppLayout>
</template>
