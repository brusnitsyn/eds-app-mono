<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {Link} from "@inertiajs/vue3";
import EdsTags from "@/Components/Eds/EdsTags.vue";
import EdsIconButton from "@/Components/Eds/EdsIconButton.vue";
import {IconEdit, IconSquareRoundedPlus} from "@tabler/icons-vue";
import UpdateTemplateModal from "@/Pages/MIS/Roles/Partials/UpdateTemplateModal.vue";
import {NButton, NIcon} from "naive-ui";
import {useCheckScope} from "@/Composables/useCheckScope.js";
import CreateTemplateModal from "@/Pages/MIS/Roles/Partials/CreateTemplateModal.vue";

const {hasRole, roles: appRoles} = useCheckScope()
const props = defineProps({
    templates: Array,
    roles: Array
})
const hasShowUpdateTemplateModal = ref(false)
const hasShowCreateTemplateModal = ref(false)
const selectedTemplate = ref()

const onClickEdit = (template) => {
    selectedTemplate.value = template
    hasShowUpdateTemplateModal.value = true
}
</script>

<template>
    <AppLayout>
        <template #headermore>
            <NButton v-if="hasRole(appRoles.ROLE_HELPER_MIS) || hasRole(appRoles.ROLE_ADMIN)" type="primary" @click="hasShowCreateTemplateModal = true">
                <template #icon>
                    <NIcon :component="IconSquareRoundedPlus" />
                </template>
                Добавить шаблон
            </NButton>
        </template>
        <NGrid cols="4" x-gap="16" y-gap="16">
            <NGridItem v-for="template in templates" :key="template.id">
                <NCard hoverable>
                    <template #header>
                        <NSpace vertical>
                            <NFlex align="center" justify="space-between">
                                <div>
                                    {{ template.name }}
                                </div>
                                <EdsIconButton :icon="IconEdit" hint="Редактировать" @click="onClickEdit(template)" />
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
        <UpdateTemplateModal v-model:show="hasShowUpdateTemplateModal" :template="selectedTemplate" :roles="roles" />
        <CreateTemplateModal v-model:show="hasShowCreateTemplateModal" :roles="roles" />
    </AppLayout>
</template>

<style scoped>

</style>
