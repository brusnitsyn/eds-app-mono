<script setup>

import {
    IconDots,
    IconExclamationCircleFilled,
    IconFileSpreadsheet,
    IconFileZip,
    IconSearch,
    IconSquareRoundedPlus
} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {computed, h, ref} from "vue";
import {NButton, NDropdown, NFlex, NIcon, NTime} from "naive-ui";
import {Link, router, useForm} from "@inertiajs/vue3";
import {useCheckScope} from "@/Composables/useCheckScope.js";
import {debounce} from "@/Utils/debounce.js";
import CreateAccountModal from "@/Pages/MIS/Users/Partials/CreateAccountModal.vue";
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue";

const props = defineProps({
    users: Array,
    departments: Array,
    prvd: Array,
    prvs: Array,
    lpus: Array
})

const { hasScope, scopes } = useCheckScope()
const rowOptions = [
    {
        label: 'Скачать сертификат',
        key: 'download-certification',
        onClick: (row) => {
            window.location = route('certification.download', {
                staff_ids: [row.id],
            }, true)
        },
    },
    {
        type: 'divider',
        show: hasScope(scopes.CAN_DELETE_STAFF),
    },
    {
        label: 'Удалить персону',
        key: 'delete-staff',
        show: hasScope(scopes.CAN_DELETE_STAFF),
        onClick: (row) => {
            router.delete(route('staff.destroy', {
                staff: row.id,
            }), {
                onSuccess: () => {
                    router.visit(route('staff.index'))
                }
            })
        },
    }
]
const columns = [
    {
        type: 'selection',
    },
    {
        title: 'ID',
        key: 'LPUDoctorID',
        width: 50,
    },
    {
        title: 'Код',
        key: 'PCOD',
        width: 50,
    },
    {
        title: 'ФИО',
        key: 'full_name',
        width: 340,
        ellipsis: {
            tooltip: true
        },
        render(row) {
            return h(
                Link,
                {
                    href: route('mis.user', { userId: row.LPUDoctorID }),
                },
                {
                    default: () => `${row.FAM_V} ${row.IM_V} ${row.OT_V}`
                }
            )
        }
    },
    {
        title: 'СНИЛС',
        key: 'SS',
        width: 120,
    },
    {
        title: 'Дата рождения',
        key: 'DR',
        width: 140,
        render(row) {
            return h(
                NTime,
                {
                    format: 'dd.MM.yyyy',
                    time: row.DR
                }
            )
        }
    },
    // {
    //     title: '',
    //     key: 'actions',
    //     width: 60,
    //     render(row) {
    //         return h(
    //             NFlex,
    //             {
    //
    //             },
    //             {
    //                 default: () => h(
    //                     NDropdown,
    //                     {
    //                         trigger: 'click',
    //                         placement: 'bottom-end',
    //                         options: rowOptions,
    //                         onSelect: (key, option) => option.onClick(row)
    //                     },
    //                     {
    //                         default: () => h(NButton, { text: true, class: 'text-xl' }, { default: () => h(NIcon, null, { default: () => h(IconDots) }) })
    //                     }
    //                 )
    //             }
    //         )
    //     }
    // }
]
const form = useForm({ search_field: 'full_name', search_value: router.page.props.ziggy.query.search_value })
const paginationReactive = ref({
    page: props.users.current_page,
    pageSize: props.users.per_page,
    pageCount: props.users.last_page,
    showSizePicker: true,
    pageSizes: [25, 50, 100],
    onChange: (page) => {
        fetchUsers({ ...router.page.props.ziggy.query, page })
    },
    onUpdatePageSize: (pageSize) => {
        fetchUsers({ ...router.page.props.ziggy.query, page: 1, page_size: pageSize })
    }
})
const hasShowCreateAccountModal = ref(false)

const fetchUsers = (query) => {
    router.get(route('mis.users'), { ...query }, {
        preserveState: true,
        onSuccess: () => {
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.users.current_page,
                pageSize: props.users.per_page,
                pageCount: props.users.last_page,
            }
        }
    })
}

function searchUser() {
    form.get(route('mis.users'), {
        preserveState: true,
        onSuccess: () => {
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.users.current_page,
                pageSize: props.users.per_page,
                pageCount: props.users.last_page,
            }
        }
    })
}

const searchValue = computed({
    get() {
        return form.search_value
    },
    set(value) {
        form.search_value = value
        searchUser()
    }
})

</script>

<template>
    <AppLayout>
        <template #subheader>
            <NSpace vertical>
                <EdsSearchInput v-model:search="searchValue" placeholder="ФИО" />
            </NSpace>
        </template>
        <template #headermore>
            <NButton v-if="hasScope(scopes.CAN_CREATE_STAFF)" type="primary" @click="hasShowCreateAccountModal = true" :disabled="form.processing" :loading="form.processing">
                <template #icon>
                    <NIcon :component="IconSquareRoundedPlus" />
                </template>
                Добавить учетную запись
            </NButton>
        </template>
        <NDataTable remote
                    :columns="columns"
                    :pagination="paginationReactive"
                    :data="users.data"
                    min-height="calc(100vh - 416px)"
                    max-height="calc(100vh - 416px)"
                    :row-key="row => row.id"
        />
        <CreateAccountModal v-model:show="hasShowCreateAccountModal" :prvd="prvd" :prvs="prvs" :departments="departments" :lpus="lpus" :user-edit="null" />
    </AppLayout>
</template>

<style scoped>

</style>
