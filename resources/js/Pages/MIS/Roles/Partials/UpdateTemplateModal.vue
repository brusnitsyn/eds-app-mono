<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue";
import {IconCheck} from "@tabler/icons-vue";
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue";
import {useForm} from "@inertiajs/vue3";

const show = defineModel('show')
const props = defineProps({
    template: Object,
    roles: Array
})

const templates = [
    {
        label: 'Тестовый шаблон',
        key: 'Тестовый шаблон',
        roles: [1, 2, 3, 4, 5]
    }
]
const form = useForm({
    roles: []
})

console.log(props.roles)
const filteredRoles = ref([
    ...props.roles
])

const _search = ref('')
const search = computed({
    get() {
        return _search.value
    },
    set(value) {
        console.log(value)
        _search.value = value
        if (value === '') filteredRoles.value = props.roles
        filteredRoles.value = props.roles.filter(item => {
            return item.Name.toLowerCase().match(value.toLowerCase())
        })
    }
})

const handleSelectRole = (key) => {
    const template = templates.find(itm => itm.label === key)
    const roles = template.roles
    form.roles = roles.filter(itm => {
        return props.roles.find(i => i.role_id === itm)
    })
}

const submit = () => {
    form
        .submit('put', route('mis.templates.roles.update', { template: props.template.id }),
            {
                onSuccess: () => {
                    show.value = false
                    form.reset()
                }
            })
}

const onAfterEnter = () => {
    const roles = props.template.roles.map(item => {
        return item.RoleID
    })

    form.roles = roles
}
</script>

<template>
    <EdsModal v-model:show="show" title="Редактирование шаблона" class="h-[742px]" @after-enter="onAfterEnter">
        <NFlex vertical justify="space-between" class="h-full">
            <NSpace vertical>
                <NGrid :cols="5">
                    <NGi :span="3">
                        <EdsSearchInput v-model:search="search"
                                        :debounce="500"
                                        placeholder="Поиск роли"
                                        @searched="(value) => search = value"
                                        size="medium"
                                        class="w-full" />
                    </NGi>
                </NGrid>
                <NScrollbar class="h-[560px] overflow-hidden relative">
                    <NForm>
                        <NCheckboxGroup v-if="filteredRoles.length > 0" v-model:value="form.roles">
                            <NSpace vertical>
                                <NCheckbox v-for="role in filteredRoles" :value="role.RoleID" :label="role.Name" />
                            </NSpace>
                        </NCheckboxGroup>
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <NEmpty />
                        </div>
                    </NForm>
                </NScrollbar>
            </NSpace>
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
