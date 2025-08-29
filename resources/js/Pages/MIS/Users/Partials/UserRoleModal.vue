<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue"
import {IconCheck, IconTemplate} from "@tabler/icons-vue";
import {router, useForm} from "@inertiajs/vue3";
const show = defineModel('show')

const props = defineProps({
    user: Object,
    xUser: Object,
    roles: Array,
    userRoles: Array,
    roleTemplates: Array
})

const form = useForm({
    user_id: props.xUser.UserID,
    roles: [
        ...props.userRoles.map(item => {
            return item.role_id
        })
    ]
})
const mappedRoles = ref([
    ...props.roles.map((itm) => ({
        label: itm.name,
        value: itm.role_id,
        guid: itm.guid
    }))
])

const handleSelectRole = (key) => {
    const template = props.roleTemplates.find(itm => itm.id === key)
    const roles = template.roles
    form.roles = roles.filter(itm => {
        return props.roles.find(i => i.role_id === itm)
    })
}

const submit = () => {
    form
        .submit('put', route('mis.users.user.roles.update', { userId: props.user.id }),
            {
                onSuccess: () => {
                    show.value = false
                }
            })
}

const onAfterLeave = () => {
    form.reset()
    mappedRoles.value = [
        ...props.roles.map((itm) => ({
            label: itm.name,
            value: itm.role_id,
            guid: itm.guid
        }))
    ]
}
</script>

<template>
    <EdsModal v-model:show="show" class="h-[742px]" title="Роли пользователя" @after-leave="onAfterLeave">
        <NFlex vertical justify="space-between" class="h-full">
            <NFlex vertical class="h-full">
                <NSpace align="center" justify="end">
<!--                    <NCheckbox v-model:checked="onlySelectedRoles" />-->
                    <NDropdown trigger="hover" :options="roleTemplates" label-field="name" key-field="id" placement="bottom-end" @select="handleSelectRole">
                        <NButton secondary type="primary">
                            <template #icon>
                                <NIcon :component="IconTemplate" />
                            </template>
                            Шаблоны
                        </NButton>
                    </NDropdown>
                </NSpace>
                <NForm class="h-full">
                    <NFormItem :show-label="false" :show-feedback="false" class="h-full">
                        <NTransfer
                            v-model:value="form.roles"
                            :options="mappedRoles"
                            source-filterable
                            target-filterable
                            class="h-full"
                        />
                    </NFormItem>
                </NForm>
            </NFlex>
            <NFlex justify="end">
                <NButton attr-type="submit"
                         type="primary"
                         icon-placement="right"
                         @click="submit">
                    <template #icon>
                        <NIcon :component="IconCheck" />
                    </template>
                    Сохранить
                </NButton>
            </NFlex>
        </NFlex>
    </EdsModal>
</template>

<style scoped>

</style>
