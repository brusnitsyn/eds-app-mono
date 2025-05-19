<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue"
import {useForm} from "@inertiajs/vue3";
import {IconCheck} from "@tabler/icons-vue";
const props = defineProps({
    user: Object,
    xUser: Object
})
const show = defineModel('show')

const hasLoading = ref(false)

const form = useForm({
    GeneralLogin: '',
    GeneralPassword: ''
})

const onAfterLeave = () => {
    form.reset()
    hasLoading.value = true
}

const onAfterEnter = () => {
    form.GeneralLogin = props.xUser.GeneralLogin
    hasLoading.value = false
}

const submit = () => {
    form
        .submit('put', route('mis.users.user.access.update', { userId: props.user.LPUDoctorID }), {
            onSuccess: () => {
                show.value = false
            }
        })
}
</script>

<template>
    <EdsModal v-model:show="show" title="Редактирование доступа" @after-leave="onAfterLeave" @after-enter="onAfterEnter" class="relative">
        <NForm v-model="form" @submit.prevent="submit" class="h-full">
            <NFlex vertical justify="space-between" class="h-full">
                <NGrid cols="6" x-gap="8">
                    <NFormItemGi span="4" label="Логин">
                        <NInput v-model:value="form.GeneralLogin" />
                    </NFormItemGi>
                    <NFormItemGi span="4" label="Пароль">
                        <NInput v-model:value="form.GeneralPassword" />
                    </NFormItemGi>
                </NGrid>

                <NFlex justify="end">
                    <NButton type="success"
                             attr-type="submit"
                             icon-placement="right">
                        <template #icon>
                            <NIcon :component="IconCheck" />
                        </template>
                        Сохранить
                    </NButton>
                </NFlex>
            </NFlex>
        </NForm>
        <div v-if="hasLoading" class="absolute inset-0">
            <NSkeleton height="100%" />
        </div>
    </EdsModal>
</template>

<style scoped>

</style>
