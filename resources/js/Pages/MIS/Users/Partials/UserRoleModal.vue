<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue"
import {useDebouncedRefHistory} from "@vueuse/core";
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue";
import {IconCheck, IconTemplate} from "@tabler/icons-vue";
import {useForm} from "@inertiajs/vue3";
const show = defineModel('show')

const props = defineProps({
    user: Object,
    xUser: Object,
    roles: Array,
    userRoles: Array,
    roleTemplates: Array
})

const templates = [
    {
        label: 'Тестовый шаблон',
        key: 'Тестовый шаблон',
        roles: [1, 2, 3, 4, 5]
    }
]
const form = useForm({
    user_id: props.xUser.UserID,
    roles: [
        ...props.userRoles.map(item => {
            return item.role_id
        })
    ]
})
const filteredRoles = ref([
    ...props.roles
])
const onlySelectedRoles = ref(false)

const _search = ref('')
const search = computed({
    get() {
        return _search.value
    },
    set(value) {
        _search.value = value
        if (value === '') filteredRoles.value = props.roles
        filteredRoles.value = filteredRoles.value.filter(item => {
            return item.name.toLowerCase().match(value.toLowerCase())
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
        .submit('put', route('mis.users.user.roles.update', { userId: props.user.LPUDoctorID }),
            {
                onSuccess: () => {
                    show.value = false
                    form.reset()
                }
            })
}
</script>

<template>
    <EdsModal v-model:show="show" class="h-[742px]" title="Роли пользователя">
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
                    <NGi :span="2">
<!--                        <NSpace align="center" justify="end">-->
<!--                            <NCheckbox v-model:checked="onlySelectedRoles" />-->
<!--                            <NDropdown trigger="hover" :options="templates" placement="bottom-end" @select="handleSelectRole">-->
<!--                                <NButton secondary type="primary">-->
<!--                                    <template #icon>-->
<!--                                        <NIcon :component="IconTemplate" />-->
<!--                                    </template>-->
<!--                                    Шаблоны-->
<!--                                </NButton>-->
<!--                            </NDropdown>-->
<!--                        </NSpace>-->
                    </NGi>
                </NGrid>
                <NScrollbar class="h-[560px] overflow-hidden relative">
                    <NForm>
                        <NCheckboxGroup v-if="filteredRoles.length > 0" v-model:value="form.roles">
                            <NSpace vertical>
                                <NCheckbox v-for="role in filteredRoles" :value="role.role_id" :label="role.name" />
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
