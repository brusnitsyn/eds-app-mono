<script setup>
import EdsModal from "@/Components/Eds/EdsModal.vue";
import {IconCheck} from "@tabler/icons-vue";
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue";
import {useForm} from "@inertiajs/vue3";

const show = defineModel('show')
const props = defineProps({
    roles: {
        type: Array,
        default: []
    }
})

const form = useForm({
    name: '',
    roles: []
})
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

const submit = () => {
    form
        .submit('post', route('mis.templates.roles.create'),
            {
                onSuccess: () => {
                    show.value = false
                    form.reset()
                }
            })
}

const onAfterEnter = () => {

}
</script>

<template>
    <EdsModal v-model:show="show" title="Добавить шаблон" class="h-[742px]" @after-enter="onAfterEnter">
        <NForm class="h-full">
            <NFlex vertical justify="space-between" class="h-full">
                    <NSpace vertical>
                        <NFormItem label="Наименование роли" :show-feedback="false">
                            <NInput v-model:value="form.name" />
                        </NFormItem>
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
                        <NScrollbar class="h-[480px] overflow-hidden relative">
                                <NCheckboxGroup v-if="filteredRoles.length > 0" v-model:value="form.roles">
                                    <NSpace vertical>
                                        <NCheckbox v-for="role in filteredRoles" :value="role.RoleID" :label="role.Name" />
                                    </NSpace>
                                </NCheckboxGroup>
                                <div v-else class="absolute inset-0 flex items-center justify-center">
                                    <NEmpty />
                                </div>
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
        </NForm>
    </EdsModal>
</template>

<style scoped>

</style>
