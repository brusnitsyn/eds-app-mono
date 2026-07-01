<script setup>
import {ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import AppLayout from "@/Layouts/AppLayout.vue"
import StaffPanel from "./Partials/StaffPanel.vue"
import StaffDetailDrawer from "./Partials/StaffDetailDrawer.vue"
import CertificateHeaderActions from "./Partials/CertificateHeaderActions.vue"
import CertificateOverlays from "./Partials/CertificateOverlays.vue"
import CreateTemplateModal from "@/Pages/MIS/Roles/Partials/CreateTemplateModal.vue"
import UpdateTemplateModal from "@/Pages/MIS/Roles/Partials/UpdateTemplateModal.vue"
import {NButton, NIcon, NDrawer, NDrawerContent, NGrid, NGridItem, NCard, NFlex, NSpace, NTag, NTime} from "naive-ui"
import {IconTemplate, IconSquareRoundedPlus, IconEdit} from "@tabler/icons-vue"
import EdsIconButton from "@/Components/Eds/EdsIconButton.vue"
import EdsTags from "@/Components/Eds/EdsTags.vue"
import {useCheckScope} from "@/Composables/useCheckScope.js"

const {hasRole, roles: appRoles} = useCheckScope()

defineProps({
    directory: Object,
    mis: Object,
    templates: {type: Array, default: () => []},
    roles: {type: Array, default: () => []},
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

const templatesDrawerOpen = ref(false)
const hasShowCreateTemplateModal = ref(false)
const hasShowUpdateTemplateModal = ref(false)
const selectedTemplate = ref(null)

function onClickEditTemplate(template) {
    selectedTemplate.value = template
    hasShowUpdateTemplateModal.value = true
}
</script>

<template>
    <AppLayout title="Сотрудники" subtitle="Медицинский персонал и привязка УКЭП">
        <template #headerActions>
            <CertificateHeaderActions @open-search="overlays.openSearch()" @open-wizard="overlays.openWizard()" />
        </template>

        <template #headermore>
            <NButton @click="templatesDrawerOpen = true">
                <template #icon>
                    <NIcon :component="IconTemplate" />
                </template>
                Шаблоны ролей
            </NButton>
        </template>

        <StaffPanel :directory="directory" :mis="mis" @open-detail="openDetail" />

        <CertificateOverlays ref="overlays" :revoking="revokeForm.processing" @revoke="revoke" />
        <StaffDetailDrawer v-model:show="detailOpen" :row="selectedRow" />

        <NDrawer v-model:show="templatesDrawerOpen" :width="720" placement="right">
            <NDrawerContent title="Шаблоны ролей" closable>
                <template #header-extra>
                    <NButton
                        v-if="hasRole(appRoles.ROLE_HELPER_MIS) || hasRole(appRoles.ROLE_ADMIN)"
                        type="primary"
                        size="small"
                        @click="hasShowCreateTemplateModal = true"
                    >
                        <template #icon>
                            <NIcon :component="IconSquareRoundedPlus" />
                        </template>
                        Добавить шаблон
                    </NButton>
                </template>
                <NGrid cols="2" x-gap="16" y-gap="16">
                    <NGridItem v-for="template in templates" :key="template.id">
                        <NCard hoverable>
                            <template #header>
                                <NSpace vertical>
                                    <NFlex align="center" justify="space-between">
                                        <div>{{ template.name }}</div>
                                        <EdsIconButton :icon="IconEdit" hint="Редактировать" @click="onClickEditTemplate(template)" />
                                    </NFlex>
                                    <NFlex size="small">
                                        <NTag type="info">
                                            <NTime :time="template.created_at" format="dd.MM.yyyy" />
                                        </NTag>
                                        <NTag type="primary">
                                            {{ template.create_user.name }}
                                        </NTag>
                                    </NFlex>
                                </NSpace>
                            </template>
                            <EdsTags :max="3" :params="template.roles" item-label="Name" />
                        </NCard>
                    </NGridItem>
                </NGrid>
            </NDrawerContent>
        </NDrawer>

        <UpdateTemplateModal v-model:show="hasShowUpdateTemplateModal" :template="selectedTemplate" :roles="roles" />
        <CreateTemplateModal v-model:show="hasShowCreateTemplateModal" :roles="roles" />
    </AppLayout>
</template>
