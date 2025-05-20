<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import {
    NDataTable,
    NButton,
    NIcon,
    NSpace,
    NFlex,
    NRadioGroup,
    NRadioButton,
    NInputGroup,
    NInput,
    NModal,
    NTime, NDropdown
} from 'naive-ui'
import {computed, h, ref} from "vue"
import {
    IconSquareRoundedPlus,
    IconFileSpreadsheet,
    IconSearch,
    IconFileZip,
    IconExclamationCircleFilled, IconDots
} from '@tabler/icons-vue'
import {Link, Head, router, usePage, useForm} from "@inertiajs/vue3";
import {debounce} from "@/Utils/debounce.js";
import CreateStaffForm from "@/Pages/Staff/Partials/CreateStaffForm.vue";
import {useCheckScope} from "@/Composables/useCheckScope.js";
import EdsSearchInput from "@/Components/Eds/EdsSearchInput.vue";

const { hasScope, scopes } = useCheckScope()

const props = defineProps({
    staffs: Array,
    filterJob: Array
})

const searchInputRef = ref()
const checkedRowKeys = ref([])

onMounted(() => {
    searchInputRef.value.focus()
})

const fetchStaff = (query) => {
    router.get('/staff', { ...query }, {
        preserveState: true,
        onSuccess: () => {
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.staffs.current_page,
                pageSize: props.staffs.per_page,
                pageCount: props.staffs.last_page,
            }
        }
    })
}

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
        key: 'id',
        width: 50,
    },
    {
        title: 'ФИО',
        key: 'full_name',
        width: 340,
        ellipsis: {
            tooltip: true
        },
        sorter: 'default',
        sortOrder: false,
        render(row) {
            return h(
                NFlex,
                {
                    align: 'center',
                },
                [
                    h(
                        Link,
                        {
                            href: `/staff/${row.id}`,
                        },
                        {
                            default: () => row.full_name,
                        }
                    ),
                    row.certification.is_request_new ? h(
                        NIcon,
                        {
                            size: 20,
                            color: '#fcb040',
                            component: IconExclamationCircleFilled
                        }
                    ) : !row.certification.is_valid ? h(
                        NIcon,
                        {
                            size: 20,
                            color: '#de576d',
                            component: IconExclamationCircleFilled
                        }
                    ) : null
                ]
            )
        }
    },
    {
        title: 'СНИЛС',
        key: 'snils',
        width: 120,
        sorter: 'default',
        sortOrder: false,
    },
    {
        title: 'Действует до',
        key: 'certification.valid_to',
        width: 140,
        sortOrder: false,
        sorter: 'default',
        render(row) {
            return h(
                NTime,
                {
                    format: 'dd.MM.yyyy',
                    time: Number(row.certification.valid_to)
                }
            )
        }
    },
    {
        title: 'Должность',
        key: 'job_title',
        sortOrder: false,
        sorter: 'default',
        ellipsis: {
            tooltip: true
        },
        filter: true,
        filterOptionValues: usePage().props.ziggy.query.filters?.job_title,
        filterOptions: props.filterJob
    },
    {
        title: '',
        key: 'actions',
        width: 60,
        render(row) {
            return h(
                NFlex,
                {

                },
                {
                    default: () => h(
                        NDropdown,
                        {
                            trigger: 'click',
                            placement: 'bottom-end',
                            options: rowOptions,
                            onSelect: (key, option) => option.onClick(row)
                        },
                        {
                            default: () => h(NButton, { text: true, class: 'text-xl' }, { default: () => h(NIcon, null, { default: () => h(IconDots) }) })
                        }
                    )
                }
            )
        }
    }
]

const handleFiltersChange = (filters) => {
    const jobTitles = Array.from(filters.job_title)
    fetchStaff({ ...router.page.props.ziggy.query, filters: { job_title: jobTitles }, page: 1 })
}
const staffType = ref(router.page.props.ziggy.query.valid_type)
const computedStaffType = computed({
    get() {
        return staffType.value
    },
    set(value) {
        staffType.value = value
        fetchStaff({ ...router.page.props.ziggy.query, valid_type: value, page: 1 })
    }
})

const selectSearchStaffOptions = [
    {
        label: 'ФИО',
        value: 'full_name'
    }
]

const paginationReactive = ref({
    page: props.staffs.current_page,
    pageSize: props.staffs.per_page,
    pageCount: props.staffs.last_page,
    showSizePicker: true,
    pageSizes: [25, 50, 100],
    onChange: (page) => {
        fetchStaff({ ...router.page.props.ziggy.query, page })
    },
    onUpdatePageSize: (pageSize) => {
        fetchStaff({ ...router.page.props.ziggy.query, page: 1, page_size: pageSize })
    }
})

const selectedSearchStaffOption = ref(selectSearchStaffOptions[0].value)

const searchStaffValue = computed({
    get() {
        return form.search_value
    },
    set(value) {
        form.search_value = value
        searchStaff()
    }
})

const form = useForm({ valid_type: null, search_field: 'full_name', search_value: router.page.props.ziggy.query.search_value })

function searchStaff() {
    form.get('/staff', {
        preserveState: true,
        onSuccess: () => {
            paginationReactive.value = {
                ...paginationReactive.value,
                page: props.staffs.current_page,
                pageSize: props.staffs.per_page,
                pageCount: props.staffs.last_page,
            }
        }
    })
    // router.get('/staff', { ...router.page.props.ziggy.query, search_field: 'full_name', search_value: searchStaffValue.value }, { preserveState: true })
    // console.log(searchStaffValue.value)
}

const hasShowCreateStaffModal = ref(false)

const handleCheck = (rowKeys) => {
    checkedRowKeys.value = rowKeys
}

const onDownloadUrl = computed(() => route('certification.download', {
    staff_ids: checkedRowKeys.value,
}, true))

const onExport = () => {
    window.location.href = route('staff.export');
    // router.visit(route('staff.export'), {
    //     headers: {
    //
    //     },
    //     onSuccess: () => {
    //         // Файл начнет загружаться автоматически
    //     },
    //     onError: (errors) => {
    //         window.$message.error('Ошибка при создании отчета')
    //         console.error('Ошибка при экспорте:', errors);
    //     }
    // });
}
</script>

<template>

    <AppLayout>
        <template #subheader>
            <NSpace vertical>
                <NFlex justify="space-between" align="center">
                    <NRadioGroup v-model:value="computedStaffType" :disabled="form.processing">
                        <NRadioButton label="Все" :value="null" />
                        <NRadioButton label="Требующие перевыпуск" value="new-request" />
                        <NRadioButton label="Недействительные" value="no-valid" />
                    </NRadioGroup>

                    <NButton v-if="checkedRowKeys.length && hasScope(scopes.CAN_DOWNLOAD_CERTIFICATION)" tag="a" target="_blank" secondary :href="onDownloadUrl">
                        <template #icon>
                            <NIcon :component="IconFileZip" />
                        </template>
                        Скачать ({{ checkedRowKeys.length }})
                    </NButton>
                </NFlex>
                <EdsSearchInput v-model:search="searchStaffValue" :loading="form.processing" placeholder="ФИО / СНИЛС / ИНН" @searched="searchStaff" />
<!--                <NInputGroup class="max-w-xl">-->
<!--&lt;!&ndash;                    <NSelect v-model:value="selectedSearchStaffOption" size="large" :style="{ width: '33%' }" :options="selectSearchStaffOptions" placeholder="Искать по" :disabled="form.processing" :loading="form.processing" />&ndash;&gt;-->
<!--                    <NInput ref="searchInputRef" v-model:value="debounceSearchStaffValue" autofocus size="large" placeholder="ФИО / СНИЛС / ИНН" @keydown.enter.prevent="searchStaff" :loading="form.processing" />-->
<!--                    <NButton :loading="form.processing" size="large" @click="searchStaff">-->
<!--                        <template #icon>-->
<!--                            <NIcon :component="IconSearch" />-->
<!--                        </template>-->
<!--                    </NButton>-->
<!--                </NInputGroup>-->
            </NSpace>
        </template>
        <template #headermore>
            <NButton type="primary" tertiary @click="onExport" :disabled="form.processing" :loading="form.processing">
                <template #icon>
                    <NIcon :component="IconFileSpreadsheet" />
                </template>
                Экспортировать
            </NButton>
            <NButton v-if="hasScope(scopes.CAN_CREATE_STAFF)" type="primary" @click="hasShowCreateStaffModal = true" :disabled="form.processing" :loading="form.processing">
                <template #icon>
                    <NIcon :component="IconSquareRoundedPlus" />
                </template>
                Добавить персону
            </NButton>
        </template>
        <NDataTable remote
                    :columns="columns"
                    :pagination="paginationReactive"
                    :data="staffs.data"
                    min-height="calc(100vh - 416px)"
                    max-height="calc(100vh - 416px)"
                    :loading="form.processing"
                    @update:filters="handleFiltersChange"
                    :row-key="row => row.id"
                    @update:checked-row-keys="handleCheck"
        />
    </AppLayout>

    <NModal v-model:show="hasShowCreateStaffModal" preset="card" class="max-w-2xl" title="Добавить персону" :bordered="false">
        <CreateStaffForm @success="hasShowCreateStaffModal = false" />
    </NModal>
</template>

<style scoped>

</style>
