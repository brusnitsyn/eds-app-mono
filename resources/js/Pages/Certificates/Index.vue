<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import CertificatesPanel from "./Partials/CertificatesPanel.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"

defineProps({
    directory: Object,
    stats: Object,
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
    <AppLayout title="Сертификаты" subtitle="Усиленные квалифицированные электронные подписи">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <CertificatesPanel :directory="directory" :stats="stats" :revoking="revokeForm.processing"
                            @open-detail="overlays.openDetail($event)" @open-wizard="overlays.openWizard()" @revoke="revoke" />

        <CertificateOverlays ref="overlays" :revoking="revokeForm.processing" @revoke="revoke" />
    </AppLayout>
</template>
