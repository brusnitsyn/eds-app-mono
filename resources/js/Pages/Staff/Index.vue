<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import {NDataTable, NText, NButton, NIcon, NSpace, NFlex, NRadioGroup, NRadioButton, NInputGroup, NSelect, NInput, NModal} from 'naive-ui'
import {computed, h, ref} from "vue"
import {IconSquareRoundedPlus, IconFileSpreadsheet, IconSearch, IconFileZip} from '@tabler/icons-vue'
import {format, toDate} from 'date-fns'
import {Link, Head, router, usePage, useForm} from "@inertiajs/vue3";
import {debounce} from "@/Utils/debounce.js";
import CreateStaffForm from "@/Pages/Staff/Partials/CreateStaffForm.vue";
import {encode, normalizeURL} from "ufo";

const props = defineProps({
    staffs: Array,
    filterJob: Array
})

const searchInputRef = ref()
const checkedRowKeys = ref([])

onMounted(() => {
    searchInputRef.value.focus()
})

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
                Link,
                {
                    href: `/staff/${row.id}`,
                },
                {
                    default: () => row.full_name,
                }
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
                NText,
                null,
                {
                    default: () => format(toDate(Number(row.certification.valid_to)), 'dd.MM.yyyy'),
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
    //                         options: rowOptions.value,
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

/// TODO: доделать фильтрацию по должностям
const handleFiltersChange = (filters) => {
    const jobTitles = Array.from(filters.job_title)
    router.get('/staff', { ...router.page.props.ziggy.query, filters: { job_title: jobTitles } }, { preserveState: false })
}
const staffType = ref(router.page.props.ziggy.query.valid_type)
const computedStaffType = computed({
    get() {
        return staffType.value
    },
    set(value) {
        staffType.value = value
        router.get('/staff', { ...router.page.props.ziggy.query, valid_type: value }, { preserveState: false })
    }
})

const selectSearchStaffOptions = [
    {
        label: 'ФИО',
        value: 'full_name'
    }
]

const paginationReactive = reactive({
    page: props.staffs.current_page,
    pageSize: props.staffs.per_page,
    pageCount: props.staffs.last_page,
    showSizePicker: true,
    pageSizes: [25, 50, 100],
    onChange: (page) => {
        router.get('/staff', { ...router.page.props.ziggy.query, page }, { preserveState: false })
    },
    onUpdatePageSize: (pageSize) => {
        router.get('/staff', { ...router.page.props.ziggy.query, page: 1, page_size: pageSize }, { preserveState: false })
    }
})

const selectedSearchStaffOption = ref(selectSearchStaffOptions[0].value)

const searchStaffValue = ref(router.page.props.ziggy.query.search_value)

const debounceSearchStaffValue = computed({
    get() {
        return searchStaffValue.value
    },
    set(value) {
        searchStaffValue.value = value
        form.search_value = value
        debounce(searchStaff, 600)
    }
})

const form = useForm({ valid_type: null, search_field: 'full_name', search_value: null })

function searchStaff() {
    form.get('/staff', { preserveState: false })
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

                    <NButton v-if="checkedRowKeys.length" tag="a" target="_blank" secondary :href="onDownloadUrl">
                        <template #icon>
                            <NIcon :component="IconFileZip" />
                        </template>
                        Скачать
                    </NButton>
                </NFlex>
                <NInputGroup class="max-w-xl">
<!--                    <NSelect v-model:value="selectedSearchStaffOption" size="large" :style="{ width: '33%' }" :options="selectSearchStaffOptions" placeholder="Искать по" :disabled="form.processing" :loading="form.processing" />-->
                    <NInput ref="searchInputRef" v-model:value="debounceSearchStaffValue" autofocus size="large" placeholder="Значение поиска" @keydown.enter.prevent="searchStaff" :loading="form.processing" />
                    <NButton :loading="form.processing" size="large" @click="searchStaff">
                        <template #icon>
                            <NIcon :component="IconSearch" />
                        </template>
                    </NButton>
                </NInputGroup>
            </NSpace>
        </template>
        <template #headermore>
            <NButton type="tertiary" :disabled="form.processing" :loading="form.processing">
                <template #icon>
                    <NIcon :component="IconFileSpreadsheet" />
                </template>
                Экспортировать
            </NButton>
            <NButton type="primary" @click="hasShowCreateStaffModal = true" :disabled="form.processing" :loading="form.processing">
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
                    min-height="calc(100vh - 415px)"
                    max-height="calc(100vh - 415px)"
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
