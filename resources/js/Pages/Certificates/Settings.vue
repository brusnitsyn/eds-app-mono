<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import SettingsPanel from "./Partials/SettingsPanel.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"

defineProps({
    parser: Object,
    storage: Object,
    mis: Object,
    trustedCas: Array,
    workstationSoftware: {type: Object, default: () => ({})},
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
    <AppLayout title="Настройки" subtitle="Парсер сертификатов, интеграции и уведомления">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <SettingsPanel :parser="parser" :storage="storage" :mis="mis" :trusted-cas="trustedCas" :workstation-software="workstationSoftware" />

        <CertificateOverlays ref="overlays" :revoking="revokeForm.processing" @revoke="revoke" />
    </AppLayout>
</template>
