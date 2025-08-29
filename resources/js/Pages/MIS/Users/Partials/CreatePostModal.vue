<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue";
import {useForm} from "@inertiajs/vue3";
import {IconCheck} from "@tabler/icons-vue";
import {format} from "date-fns"
import EdsDatePicker from "@/Components/Eds/EdsDatePicker.vue";
import {NEllipsis, NTooltip} from "naive-ui";

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
    doctor_id: props.user.id,
    name: '',
    rate: 1.00,
    code: null,
    prvs_id: null,
    department_id: null,
    prvd_id: null,
    department_profile_id: null,
    department_type_id: null,
    main_work_place: false,
    in_time: false,
    shown_in_schedule: false,
    is_special: false,
    is_dismissal: false,
    start_at: null,
    end_at: null,
})
console.log(format(new Date(), 'yyyy-MM-dd'))
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
    const departmentType = props.departmentTypes.find(itm => itm.value === department.type_id)
    const departmentProfile = props.departmentProfiles.find(itm => itm.value === department.profile_id)
    form.department_profile_id = departmentProfile.value
    form.department_type_id = departmentType.value
}

const submit = () => {
    if (editMode.value) {
        form.transform((data) => ({
            ...data,
            id: props.post.id,
            department_profile_id: data.department_profile_id ?? 0,
            department_type_id: data.department_type_id ?? 0,
            guid: props.post.guid
        })).submit('put', route('mis.users.user.post.update', { userId: props.user.id }), {
            onSuccess: () => {
                show.value = false
                form.reset()
            }
        })

        return
    }

    form.code = `${props.user.code}-${props.posts.length + 1}`

    form.transform((data) => ({
        ...data,
        rate: data.rate.toFixed(2),
        name: data.name === null || data.name === '' ? props.prvd.find(itm => itm.value === form.prvd_id).name : data.name
    }))
        .submit('post', route('mis.users.post.create', {userId: props.user.id}), {
            onSuccess: () => {
                show.value = false
                form.reset()
            }
        })
}

const renderLabel = (option) => {
    return h(
        NEllipsis,
        {},
        {
            default: () => option.label
        }
    )
}
</script>

<template>
    <EdsModal v-model:show="show" :title="title" @after-leave="onAfterLeave" @after-enter="onAfterEnter" class="h-[742px] relative">
        <NForm v-model="form" @submit.prevent="submit" class="h-full">
            <NFlex vertical justify="space-between" class="h-full">
                <NGrid cols="6" x-gap="8">
                    <NFormItemGi span="6" label="Наименование должности">
                        <NInput v-model:value="form.name" placeholder="Поле можно оставить пустым" />
                    </NFormItemGi>
                    <NFormItemGi span="4" label="Отделение">
                        <NSelect v-model:value="form.department_id" :options="departments" filterable :render-label="renderLabel" @update:value="value => updateDepartment(value)" />
                    </NFormItemGi>
                    <NFormItemGi span="2" label="Тип отделения">
                        <NSelect v-model:value="form.department_type_id" :options="departmentTypes" filterable :render-label="renderLabel" />
                    </NFormItemGi>
                    <NFormItemGi span="5" label="Должность">
                        <NSelect v-model:value="form.prvd_id" :options="prvd" filterable :render-label="renderLabel" />
                    </NFormItemGi>
                    <NFormItemGi span="1" label="Ставка">
                        <NInputNumber v-model:value="form.rate" min="0.25" max="1.00" precision="2" step="0.25" />
                    </NFormItemGi>
                    <NFormItemGi span="3" label="Специальность">
                        <NSelect v-model:value="form.prvs_id" :options="prvs" filterable :render-label="renderLabel" />
                    </NFormItemGi>
                    <NFormItemGi span="3" label="Профиль">
                        <NSelect v-model:value="form.department_profile_id" :options="departmentProfiles" filterable :render-label="renderLabel" />
                    </NFormItemGi>

                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.main_work_place" label="Основная должность" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.is_special" label="Узкий специалист" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.in_time" label="Ведёт приём" />
                    </NFormItemGi>
                    <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.shown_in_schedule" label="Доступен в расписании" />
                    </NFormItemGi>
                    <NFormItemGi span="2" :show-label="false" :show-feedback="false">
                        <NCheckbox v-model:checked="form.is_dismissal" label="Увольнение" />
                    </NFormItemGi>
                    <NFormItemGi v-if="form.is_dismissal" span="4" :show-label="false" :show-feedback="false">
                        <EdsDatePicker v-model:value="form.end_at" class="!w-1/2" />
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
