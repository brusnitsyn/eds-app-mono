<script setup>

import EdsModal from "@/Components/Eds/EdsModal.vue";
import {useForm} from "@inertiajs/vue3";
import EdsInputSnils from "@/Components/Eds/EdsInputSnils.vue";
import EdsDatePicker from "@/Components/Eds/EdsDatePicker.vue";
import {IconArrowLeft, IconArrowRight, IconCheck} from "@tabler/icons-vue";
const show = defineModel('show')
const props = defineProps({
    user: Object,
    lpus: Array,
    departments: Array,
    prvs: Array,
    prvd: Array,
    userEdit: Object,
})

const editMode = computed(() => props.userEdit !== null)
const title = computed(() => editMode.value === true ? 'Редактирование персоны' : 'Добавить персону')
const hasLoading = ref(true)
const activeTab = ref('person')

const form = useForm({
    code: null,
    middle_name: '',
    first_name: '',
    last_name: '',
    brith_at: null,
    snils: '',
    is_doctor: false,
    in_time: false,
    is_special: false,
    is_dismissal: false,
    rate: 1.00,
    lpu_id: props.lpus[0].value,
    prvs_id: null,
    department_id: null,
    prvd_id: null,
    start_at: null,
    end_at: null,
    guid: null
})

const onAfterEnter = () => {
    if (editMode.value === true) {
        for (const formKey of Object.keys(form.data())) {
            form[formKey] = props.userEdit[formKey]
        }
    }
    hasLoading.value = false
}

const onAfterLeave = () => {
    form.reset()
    activeTab.value = 'person'
    hasLoading.value = true
}

const submit = () => {
    if (editMode.value) {
        form.submit('put', route('mis.users.user.update', { userId: props.userEdit.id }), {
            onSuccess: () => {
                show.value = false
                form.reset()
            }
        })
        return
    }

    const cleanSS = form.snils.replace(/[^\d]/g, '')
    form.code = cleanSS.slice(-6)

    form.transform((data) => ({
        ...data,
        rate: data.rate.toFixed(2)
    }))
        .submit('post', route('mis.users.create'), {
            onSuccess: () => {
                show.value = false
                form.reset()
            }
        })
}
</script>

<template>
    <EdsModal v-model:show="show" :title="title" class="h-[742px] relative" @after-leave="onAfterLeave" @after-enter="onAfterEnter">
        <NForm v-model="form" class="h-full" @submit.prevent="submit">
            <NTabs type="segment"
                   animated
                   class="h-full"
                   :active-name="activeTab"
                   @update:value="(value) => activeTab = value">
                <NTabPane name="person" tab="Персональная информация" class="h-full">
                    <NFlex vertical justify="space-between" class="h-full">
                        <NGrid cols="6" x-gap="8">
                            <NFormItemGi span="6" label="Фамилия">
                                <NInput v-model:value="form.last_name" />
                            </NFormItemGi>
                            <NFormItemGi span="6" label="Имя">
                                <NInput v-model:value="form.first_name" />
                            </NFormItemGi>
                            <NFormItemGi span="6" label="Отчество">
                                <NInput v-model:value="form.middle_name" />
                            </NFormItemGi>
                            <NFormItemGi span="3" label="Дата рождения">
                                <EdsDatePicker v-model:value="form.brith_at" class="w-full" />
                            </NFormItemGi>
                            <NFormItemGi span="3" label="СНИЛС">
                                <EdsInputSnils v-model:value="form.snils" />
                            </NFormItemGi>
                        </NGrid>

                        <NFlex justify="end">
                            <NButton type="success"
                                     icon-placement="right"
                                     @click="activeTab = 'post'">
                                <template #icon>
                                    <NIcon :component="IconArrowRight" />
                                </template>
                                Далее
                            </NButton>
                        </NFlex>
                    </NFlex>
                </NTabPane>
                <NTabPane name="post" tab="Настройки" class="h-full">
                    <NFlex vertical justify="space-between" class="h-full">
                        <NGrid cols="6" x-gap="8">
                            <NFormItemGi span="4" label="ЛПУ">
                                <NSelect v-model:value="form.lpu_id" :options="lpus" filterable />
                            </NFormItemGi>
                            <NFormItemGi span="4" label="Отделение">
                                <NSelect v-model:value="form.department_id" :options="departments" filterable />
                            </NFormItemGi>
                            <NFormItemGi :span="editMode ? 4 : 5" label="Должность">
                                <NSelect v-model:value="form.prvd_id" :options="prvd" filterable />
                            </NFormItemGi>
                            <NFormItemGi v-if="!editMode" span="1" label="Ставка">
                                <NInputNumber v-model:value="form.rate" min="0.25" max="1.00" precision="2" step="0.25" />
                            </NFormItemGi>
                            <NFormItemGi span="4" label="Специальность">
                                <NSelect v-model:value="form.prvs_id" :options="prvs" filterable />
                            </NFormItemGi>

                            <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                                <NCheckbox v-model:checked="form.is_doctor" label="Является врачом" />
                            </NFormItemGi>
                            <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                                <NCheckbox v-model:checked="form.is_special" label="Узкий специалист" />
                            </NFormItemGi>
                            <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                                <NCheckbox v-model:checked="form.in_time" label="Доступен в расписании" />
                            </NFormItemGi>
                            <NFormItemGi span="6" :show-label="false" :show-feedback="false">
                                <NCheckbox v-model:checked="form.is_dismissal" label="Увольнение" />
                            </NFormItemGi>

                        </NGrid>

                        <NFlex justify="space-between">
                            <NButton type="success"
                                     secondary
                                     icon-placement="left"
                                     @click="activeTab = 'person'">
                                <template #icon>
                                    <NIcon :component="IconArrowLeft" />
                                </template>
                                Назад
                            </NButton>

                            <NButton type="success"
                                     attr-type="submit"
                                     icon-placement="right" @click="activeTab = 'post'">
                                <template #icon>
                                    <NIcon :component="IconCheck" />
                                </template>
                                {{ editMode ? 'Сохранить' : 'Добавить' }}
                            </NButton>
                        </NFlex>
                    </NFlex>
                </NTabPane>
            </NTabs>
        </NForm>
        <div v-if="hasLoading" class="absolute inset-0 z-50">
            <NSkeleton height="100%" />
        </div>
    </EdsModal>
</template>

<style scoped>
:deep(.n-tabs-pane-wrapper) {
    @apply h-full;
}
</style>
