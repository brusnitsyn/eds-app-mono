<script setup>
import {router, useForm} from "@inertiajs/vue3"
import {IconPlus, IconLockCog, IconSunglasses, IconPencil} from "@tabler/icons-vue"
import {staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import {useCheckScope} from "@/Composables/useCheckScope.js"
import AppCopyButton from "@/Components/AppCopyButton.vue"
import EdsIconButton from "@/Components/Eds/EdsIconButton.vue"
import EdsPropertyRow from "@/Components/Eds/EdsPropertyRow.vue"
import EdsInputSnils from "@/Components/Eds/EdsInputSnils.vue"
import EdsDatePicker from "@/Components/Eds/EdsDatePicker.vue"
import UserRoleModal from "@/Pages/MIS/Users/Partials/UserRoleModal.vue"
import CreatePostModal from "@/Pages/MIS/Users/Partials/CreatePostModal.vue"
import CertificateDetailDrawer from "@/Pages/Certificates/Partials/CertificateDetailDrawer.vue"

const show = defineModel("show")

const props = defineProps({
    row: Object,
})

const {hasRole, roles} = useCheckScope()
const canEdit = computed(() => hasRole(roles.ROLE_HELPER_MIS) || hasRole(roles.ROLE_ADMIN))

const detail = ref(null)
const loading = ref(false)

async function fetchDetail({silent = false} = {}) {
    if (!props.row?.mis_user_id) {
        detail.value = null
        return
    }

    if (!silent) loading.value = true
    try {
        const {data} = await window.axios.get(route('mis.users.user.detail', {userId: props.row.mis_user_id}))
        detail.value = data
    } finally {
        if (!silent) loading.value = false
    }
}

watch(() => show.value, (value) => {
    if (value) fetchDetail()
    else {
        detail.value = null
        editingField.value = null
    }
})

const headerName = computed(() => {
    const u = detail.value?.user
    if (!u) return props.row?.fio
    return [u.last_name, u.first_name, u.middle_name].filter(Boolean).join(' ')
})
const headerPosition = computed(() => detail.value?.user?.prvd_name ?? props.row?.position)

// --- Inline editing: "Общая информация" ---------------------------------

const personForm = useForm({
    code: null, middle_name: '', first_name: '', last_name: '', brith_at: null, snils: '',
    is_doctor: false, in_time: false, is_special: false, is_dismissal: false, rate: 1.00,
    lpu_id: null, prvs_id: null, department_id: null, prvd_id: null,
    start_at: null, end_at: null, guid: null,
})

function syncPersonForm() {
    if (!detail.value?.user) return
    for (const key of Object.keys(personForm.data())) {
        personForm[key] = detail.value.user[key]
    }
}

const editingField = ref(null)

function startEdit(field) {
    editingField.value = field
}

function cancelEdit() {
    // A field's own save is already in flight (e.g. a select's blur fires
    // right after its update:value triggers a submit) — don't stomp on it.
    if (personForm.processing || accessForm.processing) return
    syncPersonForm()
    syncAccessForm()
    editingField.value = null
}

function handleDrawerMousedown(event) {
    if (!editingField.value) return
    if (event.target.closest('input, textarea, .n-input, .n-select, .n-select-menu, .n-base-select-option, .n-date-picker, .n-date-panel')) return
    cancelEdit()
}

function savePersonField(field) {
    personForm.submit('put', route('mis.users.user.update', {userId: props.row.mis_user_id}), {
        preserveScroll: true,
        onSuccess: async () => {
            editingField.value = null
            await fetchDetail({silent: true})
        },
    })
}

function handlePersonBlur(field) {
    if (editingField.value !== field) return
    savePersonField(field)
}

function setAndSave(field, value) {
    personForm[field] = value
    savePersonField(field)
}

// --- Inline editing: "Доступ" ---------------------------------------------

const accessForm = useForm({GeneralLogin: '', GeneralPassword: ''})

function syncAccessForm() {
    if (!detail.value?.x_user) return
    accessForm.GeneralLogin = detail.value.x_user.GeneralLogin
    accessForm.GeneralPassword = detail.value.x_user.GeneralPassword
}

function saveAccessField() {
    accessForm.submit('put', route('mis.users.user.access.update', {userId: props.row.mis_user_id}), {
        preserveScroll: true,
        onSuccess: async () => {
            editingField.value = null
            await fetchDetail({silent: true})
        },
    })
}

function handleAccessBlur() {
    if (editingField.value !== 'login') return
    saveAccessField()
}

watch(detail, () => {
    syncPersonForm()
    syncAccessForm()
})

function changePassword() {
    router.post(route('mis.users.password.change', {userId: props.row.mis_user_id}), {}, {
        onSuccess: () => {
            window.$message?.success('Пароль изменен')
            fetchDetail({silent: true})
        }
    })
}

// --- Roles / posts modals (kept as dedicated dialogs) ----------------------

const hasShowUserRoleModal = ref(false)
const hasShowCreatePostModal = ref(false)
const currentPost = ref(null)

watch(hasShowUserRoleModal, (value) => { if (!value) fetchDetail({silent: true}) })
watch(hasShowCreatePostModal, (value) => {
    if (!value) {
        currentPost.value = null
        fetchDetail({silent: true})
    }
})

function onShowViewPostModal(post) {
    currentPost.value = post
    hasShowCreatePostModal.value = true
}

function onShowCreatePostModal() {
    currentPost.value = null
    hasShowCreatePostModal.value = true
}

function certDef(row) {
    if (!row.has_certificate) return {label: "Нет УКЭП", type: "default"}
    if (row.cert_status === "expiring") return {label: "Истекает", type: "warning"}
    if (row.cert_status === "valid") return {label: "УКЭП активна", type: "success"}
    return {label: "УКЭП недействительна", type: "error"}
}

const certDrawerOpen = ref(false)
</script>

<template>
    <NDrawer v-model:show="show" width="560" native-scrollbar block-scroll @mousedown.capture="handleDrawerMousedown">
        <NDrawerContent title="Сведения о сотруднике" closable v-if="row">
            <NFlex align="center" :size="16" class="mb-6">
                <NAvatar round :size="60" :color="avatarColor(row.mis_user_id ?? row.staff_id ?? 0)" style="color:#fff;font-weight:600;font-size:20px">
                    {{ staffInitials(headerName) }}
                </NAvatar>
                <div class="flex-1 min-w-0">
                    <div class="text-lg font-semibold leading-tight">{{ headerName }}</div>
                    <div class="text-sm text-[var(--n-close-icon-color)] mb-2">{{ headerPosition }}</div>
                    <NFlex align="center" :size="8">
                        <NTag :type="certDef(row).type" round size="small">{{ certDef(row).label }}</NTag>
                        <NText v-if="detail?.user?.code" depth="3" class="text-xs">· код {{ detail.user.code }}</NText>
                    </NFlex>
                </div>
            </NFlex>

            <NSkeleton v-if="loading" text :repeat="6" />

            <NFlex v-else-if="detail" vertical :size="20">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-[var(--n-close-icon-color)] mb-1">Общая информация</div>
                    <NDivider class="!my-2" />

                    <EdsPropertyRow label="Фамилия" :editable="canEdit" :editing="editingField === 'last_name'" @edit="startEdit('last_name')">
                        {{ detail.user.last_name }}
                        <template #edit>
                            <NInput v-model:value="personForm.last_name" size="small" autofocus @blur="handlePersonBlur('last_name')" @keyup.enter="$event.target.blur()" @keyup.esc="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Имя" :editable="canEdit" :editing="editingField === 'first_name'" @edit="startEdit('first_name')">
                        {{ detail.user.first_name }}
                        <template #edit>
                            <NInput v-model:value="personForm.first_name" size="small" autofocus @blur="handlePersonBlur('first_name')" @keyup.enter="$event.target.blur()" @keyup.esc="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Отчество" :editable="canEdit" :editing="editingField === 'middle_name'" @edit="startEdit('middle_name')">
                        {{ detail.user.middle_name }}
                        <template #edit>
                            <NInput v-model:value="personForm.middle_name" size="small" autofocus @blur="handlePersonBlur('middle_name')" @keyup.enter="$event.target.blur()" @keyup.esc="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Дата рождения" :editable="canEdit" :editing="editingField === 'brith_at'" @edit="startEdit('brith_at')">
                        <NTime v-if="detail.user.brith_at" format="dd.MM.yyyy" :time="new Date(detail.user.brith_at)" />
                        <template #edit>
                            <EdsDatePicker :value="personForm.brith_at" size="small" @update:value="value => setAndSave('brith_at', value)" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="СНИЛС" :editable="canEdit" :editing="editingField === 'snils'" @edit="startEdit('snils')">
                        {{ detail.user.snils }}
                        <template #edit>
                            <EdsInputSnils v-model:value="personForm.snils" size="small" autofocus @blur="handlePersonBlur('snils')" @keyup.enter="$event.target.blur()" @keyup.esc="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Код врача" :editable="false">{{ detail.user.code }}</EdsPropertyRow>

                    <EdsPropertyRow label="ЛПУ" :editable="canEdit" :editing="editingField === 'lpu_id'" @edit="startEdit('lpu_id')">
                        {{ detail.user.lpu_name }}
                        <template #edit>
                            <NSelect :value="personForm.lpu_id" :options="detail.lpus" filterable size="small" autofocus
                                     @update:value="value => setAndSave('lpu_id', value)" @blur="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Отделение" :editable="canEdit" :editing="editingField === 'department_id'" @edit="startEdit('department_id')">
                        {{ detail.user.department_name }}
                        <template #edit>
                            <NSelect :value="personForm.department_id" :options="detail.departments" filterable size="small" autofocus
                                     @update:value="value => setAndSave('department_id', value)" @blur="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Специальность" :editable="canEdit" :editing="editingField === 'prvs_id'" @edit="startEdit('prvs_id')">
                        {{ detail.user.prvs_name }}
                        <template #edit>
                            <NSelect :value="personForm.prvs_id" :options="detail.prvs" filterable size="small" autofocus
                                     @update:value="value => setAndSave('prvs_id', value)" @blur="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Должность" :editable="canEdit" :editing="editingField === 'prvd_id'" @edit="startEdit('prvd_id')">
                        {{ detail.user.prvd_name }}
                        <template #edit>
                            <NSelect :value="personForm.prvd_id" :options="detail.prvd" filterable size="small" autofocus
                                     @update:value="value => setAndSave('prvd_id', value)" @blur="cancelEdit" />
                        </template>
                    </EdsPropertyRow>

                    <EdsPropertyRow label="Признаки" :editable="false">
                        <NFlex :size="[12, 4]">
                            <NCheckbox :checked="personForm.is_doctor" label="Врач" :disabled="!canEdit" @update:checked="value => setAndSave('is_doctor', value)" />
                            <NCheckbox :checked="personForm.is_special" label="Узкий специалист" :disabled="!canEdit" @update:checked="value => setAndSave('is_special', value)" />
                            <NCheckbox :checked="personForm.in_time" label="В расписании" :disabled="!canEdit" @update:checked="value => setAndSave('in_time', value)" />
                            <NCheckbox :checked="personForm.is_dismissal" label="Увольнение" :disabled="!canEdit" @update:checked="value => setAndSave('is_dismissal', value)" />
                        </NFlex>
                    </EdsPropertyRow>
                </div>

                <div>
                    <NFlex align="center" justify="space-between" class="mb-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[var(--n-close-icon-color)]">Доступ</span>
                        <EdsIconButton v-if="canEdit" :icon="IconSunglasses" hint="Роли" @click="hasShowUserRoleModal = true" />
                    </NFlex>
                    <NDivider class="!my-2" />

                    <template v-if="detail.x_user">
                        <EdsPropertyRow label="Логин" :editable="canEdit" :editing="editingField === 'login'" @edit="startEdit('login')">
                            <NFlex align="center" :size="6" :wrap="false">
                                <span class="truncate">{{ detail.x_user.GeneralLogin }}</span>
                                <span @click.stop><AppCopyButton :value="detail.x_user.GeneralLogin" /></span>
                            </NFlex>
                            <template #edit>
                                <NInput v-model:value="accessForm.GeneralLogin" size="small" autofocus @blur="handleAccessBlur" @keyup.enter="$event.target.blur()" @keyup.esc="cancelEdit" />
                            </template>
                        </EdsPropertyRow>

                        <EdsPropertyRow label="Пароль" :editable="false">
                            <NFlex align="center" :size="6" :wrap="false">
                                <span class="blur-sm select-none">{{ detail.x_user.GeneralPassword }}</span>
                                <EdsIconButton v-if="canEdit" :icon="IconLockCog" hint="Подменить пароль" @click="changePassword" />
                            </NFlex>
                        </EdsPropertyRow>
                    </template>
                    <NEmpty v-else class="my-2" />
                </div>

                <div>
                    <NFlex align="center" justify="space-between" class="mb-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[var(--n-close-icon-color)]">Должности</span>
                        <EdsIconButton v-if="canEdit" :icon="IconPlus" hint="Добавить должность" @click="onShowCreatePostModal" />
                    </NFlex>
                    <NDivider class="!my-2" />

                    <NFlex v-for="job in detail.jobs" :key="job.DocPRVDID" align="center" justify="space-between" :wrap="false"
                           class="group py-1.5 -mx-2 px-2 rounded-md hover:bg-[var(--n-close-color-hover)]">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm truncate">{{ job.name }}</div>
                            <div class="text-xs text-[var(--n-close-icon-color)]">{{ job.code === '' ? 'Нет кода' : job.code }}</div>
                        </div>
                        <NFlex align="center" :size="6" :wrap="false">
                            <NTag v-if="job.main_work_place" size="small" type="success">Основная</NTag>
                            <NTag v-if="job.is_dismissal" size="small" type="error">Увольнение</NTag>
                            <NButton v-if="canEdit" text class="opacity-0 group-hover:opacity-60" @click="onShowViewPostModal(job)">
                                <NIcon :component="IconPencil" :size="14" />
                            </NButton>
                        </NFlex>
                    </NFlex>
                    <NEmpty v-if="detail.jobs.length === 0" class="my-2" />
                </div>
            </NFlex>

            <NEmpty v-else description="Локальный сотрудник без учётной записи МИС" class="my-6" />

            <template #footer v-if="row.staff_id && row.cert">
                <NButton @click="certDrawerOpen = true" style="width: 100%">
                    Сертификат пользователя
                </NButton>
            </template>
        </NDrawerContent>
    </NDrawer>

    <CertificateDetailDrawer v-model:show="certDrawerOpen" :certificate="row?.cert" />
    <template v-if="detail">
        <UserRoleModal v-if="detail.x_user" v-model:show="hasShowUserRoleModal" :user="detail.user" :x-user="detail.x_user" :roles="detail.roles" :user-roles="detail.user_roles" :role-templates="detail.role_templates" />
        <CreatePostModal v-model:show="hasShowCreatePostModal" :user="detail.user" :prvd="detail.prvd" :prvs="detail.prvs" :departments="detail.departments" :posts="detail.jobs" :department-types="detail.department_types" :department-profiles="detail.department_profiles" :post="currentPost" />
    </template>
</template>
