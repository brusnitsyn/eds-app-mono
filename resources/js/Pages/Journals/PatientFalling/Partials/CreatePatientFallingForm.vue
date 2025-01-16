<script setup>
import DatePicker from "@/Components/DatePicker.vue"
import {router, useForm} from "@inertiajs/vue3"
import DivisionSelect from "@/Components/DivisionSelect.vue";

const emits = defineEmits(['success'])
const formRef = ref()

const form = useForm({
    event_at: null,
    division_id: '',
    full_name_patient: '',
    reason_event: '',
    place_event: '',
    has_helping: '',
    consequences: '',
})

const rules = {
    full_name_patient: [
        {
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    event_at: [
        {
            type: 'number',
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    reason_event: [
        {
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    place_event: [
        {
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    has_helping: [
        {
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    consequences: [
        {
            required: true,
            message: 'Это поле обязательно!',
            trigger: ['input', 'blur']
        }
    ],
    division_id: [
        {
            type: 'number',
            required: true,
            trigger: ['change', 'blur']
        }
    ]
}

function handleSubmit() {
    formRef.value?.validate(
        (errors) => {
            if (!errors) {
                form.post(route('journals.patient-falling.store'), {
                    onSuccess: () => {
                        window.$message.success('Событие успешно добавлено')
                        router.reload({
                            only: ['data']
                        })
                    },
                    onError: (err) => console.log(err)
                })
            }
            else {
                console.log(errors)
            }
        }
    )
}
</script>

<template>
    <NForm :model="form" :rules="rules" ref="formRef" @submit="handleSubmit">
        <NGrid cols="2" x-gap="16" y-gap="4">
            <NFormItemGi span="2" label="ФИО пациента" path="full_name_patient">
                <NInput v-model:value="form.full_name_patient" placeholder="" />
            </NFormItemGi>
            <NFormItemGi label="Дата события" path="event_at">
                <DatePicker v-model:value="form.event_at" />
            </NFormItemGi>
            <NFormItemGi label="Причина падения" path="reason_event">
                <NInput v-model:value="form.reason_event" placeholder="" />
            </NFormItemGi>
            <NFormItemGi label="Место падения" path="place_event">
                <NInput v-model:value="form.place_event" placeholder="" />
            </NFormItemGi>
            <NFormItemGi label="Проведено мероприятие" path="has_helping">
                <NInput v-model:value="form.has_helping" placeholder="" />
            </NFormItemGi>
            <NFormItemGi span="2" label="Последствия" path="consequences">
                <NInput type="textarea" :autosize="{
                    minRows: 4,
                    maxRows: 4,
                  }" v-model:value="form.consequences" placeholder="" />
            </NFormItemGi>
            <NFormItemGi span="2" label="Отделение" path="division_id">
                <DivisionSelect v-model:value="form.division_id" />
            </NFormItemGi>
            <NFormItemGi :show-feedback="false" class="flex flex-col items-start justify-end">
                <NButton secondary attr-type="reset">
                    Отмена
                </NButton>
            </NFormItemGi>
            <NFormItemGi :show-feedback="false" class="flex flex-col items-end justify-end">
                <NButton type="primary" attr-type="submit">
                    Добавить событие
                </NButton>
            </NFormItemGi>
        </NGrid>
    </NForm>
</template>

<style scoped>

</style>
