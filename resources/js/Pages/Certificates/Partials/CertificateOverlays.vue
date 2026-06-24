<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue"
import {router, usePage} from "@inertiajs/vue3"
import UploadWizardModal from "./UploadWizardModal.vue"
import SearchPalette from "./SearchPalette.vue"
import CertificateDetailDrawer from "./CertificateDetailDrawer.vue"

const props = defineProps({
    certificates: {type: Array, default: () => []},
    staff: {type: Array, default: () => []},
    revoking: Boolean,
})

const trustedCas = computed(() => usePage().props.trustedCertificateAuthorities ?? [])

const emit = defineEmits(["revoke"])

const wizardOpen = ref(false)
const detailOpen = ref(false)
const searchOpen = ref(false)
const selectedId = ref(null)

const selected = computed(() => props.certificates.find(c => c.id === selectedId.value) ?? null)

function openDetail(cert) {
    selectedId.value = cert.id
    detailOpen.value = true
}

function openWizard() {
    wizardOpen.value = true
}

function openSearch() {
    searchOpen.value = true
}

function onSelectStaff() {
    router.visit(route("staff"))
}

function onGlobalKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
        e.preventDefault()
        searchOpen.value = !searchOpen.value
    }
}

onMounted(() => window.addEventListener("keydown", onGlobalKeydown))
onUnmounted(() => window.removeEventListener("keydown", onGlobalKeydown))

defineExpose({openDetail, openWizard, openSearch})
</script>

<template>
    <UploadWizardModal v-model:show="wizardOpen" />

    <SearchPalette v-model:show="searchOpen" :certificates="certificates" :staff="staff"
                   @select-certificate="openDetail" @select-staff="onSelectStaff" />

    <CertificateDetailDrawer v-model:show="detailOpen" :certificate="selected" :revoking="revoking" :trusted-cas="trustedCas"
                              @revoke="cert => emit('revoke', cert)" @renew="openWizard" />
</template>
