<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue";
import {useForm} from "@inertiajs/vue3";
import {IconCheck} from "@tabler/icons-vue";

const show = defineModel('show')
const props = defineProps({
    user: Object,
    posts: Array,
    departments: Array,
    prvs: Array,
    prvd: Array,
    departmentTypes: Array,
    departmentProfiles: Array,
    post: Object
})

const form = useForm({
    S_ST: 1.00,
    PCOD: null,
    rf_PRVSID: null,
    rf_DepartmentID: null,
    rf_PRVDID: null,
    rf_kl_DepartmentProfileID: null,
    rf_kl_DepartmentTypeID: null,
    MainWorkPlace: false,
    InTime: false,
    ShownInSchedule: false,
    isSpecial: false,
    isDismissal: false,
})
const title = computed(() => editMode.value === true ? 'Редактирование должности' : 'Добавить должность')
const editMode = computed(() => props.post !== null)
const hasLoading = ref(true)

// watch(() => previewMode.value, (value) => {
//     if (value === true) {
//         for (const formKey of Object.keys(form.data())) {
//             form[formKey] = props.post[formKey]
//         }
//     }
// })

const onAfterEnter = () => {
    if (editMode.value === true) {
        for (const formKey of Object.keys(form.data())) {
            form[formKey] = props.post[formKey]
        }
    }
    hasLoading.value = false
}

const onAfterLeave = () => {
    form.reset()
    hasLoading.value = true
}

const updateDepartment = (value) => {
    const department = props.departments.find(itm => itm.value === value)
    const departmentType = props.departmentTypes.find(itm => itm.value = department.type_id)
    const departmentProfile = props.departmentProfiles.find(itm => itm.value = department.profile_id)
    form.rf_kl_DepartmentProfileID = departmentProfile.value
    form.rf_kl_DepartmentTypeID = departmentType.value
}

const submit = () => {
    if (editMode.value) {
        form.transform((data) => ({
            ...data,
            DocPRVDID: props.post.DocPRVDID,
            rf_kl_DepartmentProfileID: data.rf_kl_DepartmentProfileID ?? 0,
            rf_kl_DepartmentTypeID: data.rf_kl_DepartmentTypeID ?? 0,
        })).submit('put', route('mis.users.user.post.update', { userId: props.user.LPUDoctorID }), {
            onSuccess: () => {
                show.value = false
                form.reset()
            }
        })

        return
    }
    form.PCOD = `${props.user.PCOD}-${props.posts.length + 1}`

    form.transform((data) => ({
        ...data,
        S_ST: data.S_ST.toFixed(2)
    }))
        .submit('post', route('mis.users.post.create', {userId: props.user.LPUDoctorID}))

    console.log(form.data())
}
</script>

<template>
    <EdsModal v-model:show="show" :title="title" @after-leave="onAfterLeave" @after-enter="onAfterEnter" class="h-[742px] relative">
        <NForm v-model="form" @submit.prevent="submit" class="h-full">
            <NFlex vertical justify="space-between" class="h-full">
                <NGrid cols="6" x-gap="8">
                    <NFormItemGi span="4" label="Отделение">
                        <NSelect v-model:value="form.rf_DepartmentID" :options="departments" filterable @update:value="value => updateDepartment(value)" />
                    </NFormItemGi>
                    <NFormItemGi span="2" label="Тип отделения">
                        <NSelect v-model:value="form.rf_kl_DepartmentTypeID" :options="departmentTypes" filterable />
                    </NFormItemGi>
                    <NFormItemGi span="5" label="Должность">
                        <NSelect v-model:value="form.rf_PRVDID" :options="prvd" filterable />
                    </NFormItemGi>
                    <NFormItemGi span="1" label="Ставка">
                        <NInputNumber v-model:value="form.S_ST" min="0.25" max="1.00" precision="2" step="0.25" />
                    </NFormItemGi>
                    <NFormItemGi span="3" label="Специальность">
                        <NSelect v-model:value="form.rf_PRVSID" :options="prvs" filterable />
                    </NFormItemGi>
                    <NFormItemGi span="3" label="Профиль">
                        <NSelect v-model:value="form.rf_kl_DepartmentProfileID" :options="departmentProfiles" filterable />
                    </NFormItemGi>

                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.MainWorkPlace" label="Основная должность" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.isSpecial" label="Узкий специалист" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.InTime" label="Ведёт приём" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.ShownInSchedule" label="Доступен в расписании" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.isDismissal" label="Увольнение" />
                    </NFormItemGi>
                </NGrid>

                <NFlex justify="end">
                    <NButton type="success"
                             attr-type="submit"
                             icon-placement="right">
                        <template #icon>
                            <NIcon :component="IconCheck" />
                        </template>
                        {{ editMode ? 'Сохранить' : 'Добавить' }}
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
