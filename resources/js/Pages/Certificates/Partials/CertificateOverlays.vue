<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue"
import {router, usePage} from "@inertiajs/vue3"
import UploadWizardModal from "./UploadWizardModal.vue"
import SearchPalette from "./SearchPalette.vue"
import CertificateDetailDrawer from "./CertificateDetailDrawer.vue"

defineProps({
    revoking: Boolean,
})

const trustedCas = computed(() => usePage().props.trustedCertificateAuthorities ?? [])

const emit = defineEmits(["revoke"])

const wizardOpen = ref(false)
const detailOpen = ref(false)
const searchOpen = ref(false)
const selected = ref(null)

function openDetail(cert) {
    selected.value = cert
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

    <SearchPalette v-model:show="searchOpen" @select-certificate="openDetail" @select-staff="onSelectStaff" />

    <CertificateDetailDrawer v-model:show="detailOpen" :certificate="selected" :revoking="revoking" :trusted-cas="trustedCas"
                              @revoke="cert => emit('revoke', cert)" @renew="openWizard" />
</template>
