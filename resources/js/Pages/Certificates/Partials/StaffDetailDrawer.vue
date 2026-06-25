<script setup>
import {router} from "@inertiajs/vue3"
import {IconPencil, IconPlus, IconLockCog, IconSunglasses} from "@tabler/icons-vue"
import {staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import {useCheckScope} from "@/Composables/useCheckScope.js"
import AppCopyButton from "@/Components/AppCopyButton.vue"
import EdsIconButton from "@/Components/Eds/EdsIconButton.vue"
import CreateAccountModal from "@/Pages/MIS/Users/Partials/CreateAccountModal.vue"
import UpdateAccessModal from "@/Pages/MIS/Users/Partials/UpdateAccessModal.vue"
import UserRoleModal from "@/Pages/MIS/Users/Partials/UserRoleModal.vue"
import CreatePostModal from "@/Pages/MIS/Users/Partials/CreatePostModal.vue"

const show = defineModel("show")

const props = defineProps({
    row: Object,
})

const {hasRole, roles} = useCheckScope()
const canEdit = computed(() => hasRole(roles.ROLE_HELPER_MIS) || hasRole(roles.ROLE_ADMIN))

const detail = ref(null)
const loading = ref(false)

async function fetchDetail() {
    if (!props.row?.mis_user_id) {
        detail.value = null
        return
    }

    loading.value = true
    try {
        const {data} = await window.axios.get(route('mis.users.user.detail', {userId: props.row.mis_user_id}))
        detail.value = data
    } finally {
        loading.value = false
    }
}

watch(() => show.value, (value) => {
    if (value) fetchDetail()
    else detail.value = null
})

const hasShowEditAccountModal = ref(false)
const hasShowUpdateAccessModal = ref(false)
const hasShowUserRoleModal = ref(false)
const hasShowCreatePostModal = ref(false)
const currentPost = ref(null)

watch(hasShowEditAccountModal, (value) => { if (!value) fetchDetail() })
watch(hasShowUpdateAccessModal, (value) => { if (!value) fetchDetail() })
watch(hasShowUserRoleModal, (value) => { if (!value) fetchDetail() })
watch(hasShowCreatePostModal, (value) => {
    if (!value) {
        currentPost.value = null
        fetchDetail()
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

function changePassword() {
    router.post(route('mis.users.password.change', {userId: props.row.mis_user_id}), {}, {
        onSuccess: () => {
            window.$message?.success('Пароль изменен')
            fetchDetail()
        }
    })
}

function certDef(row) {
    if (!row.has_certificate) return {label: "Нет УКЭП", type: "default"}
    if (row.cert_status === "expiring") return {label: "Истекает", type: "warning"}
    if (row.cert_status === "valid") return {label: "УКЭП активна", type: "success"}
    return {label: "УКЭП недействительна", type: "error"}
}
</script>

<template>
    <NDrawer v-model:show="show" width="560" block-scroll>
        <NDrawerContent title="Сведения о сотруднике" closable v-if="row">
            <NFlex align="center" :size="16" class="mb-5">
                <NAvatar round :size="60" :color="avatarColor(row.mis_user_id ?? row.staff_id ?? 0)" style="color:#fff;font-weight:600;font-size:20px">
                    {{ staffInitials(row.fio) }}
                </NAvatar>
                <div class="flex-1 min-w-0">
                    <div class="text-lg font-semibold leading-tight">{{ row.fio }}</div>
                    <div class="text-sm mb-2">{{ row.position }}</div>
                    <NTag :type="certDef(row).type" round size="small">{{ certDef(row).label }}</NTag>
                </div>
            </NFlex>

            <NSkeleton v-if="loading" text :repeat="6" />

            <NSpace v-else-if="detail" vertical :size="16">
                <NCard title="Общая информация" size="small">
                    <template #header-extra>
                        <NSpace>
                            <NButton v-if="canEdit" text @click="hasShowUserRoleModal = true">
                                <template #icon><NIcon :component="IconSunglasses" /></template>
                                Роли
                            </NButton>
                            <NButton v-if="canEdit" text @click="hasShowEditAccountModal = true">
                                <template #icon><NIcon :component="IconPencil" /></template>
                                Редактировать
                            </NButton>
                        </NSpace>
                    </template>
                    <NList hoverable>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>Код врача</NText></NGi>
                                <NGi><NText>{{ detail.user.code }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>СНИЛС</NText></NGi>
                                <NGi><NText>{{ detail.user.snils }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>ЛПУ</NText></NGi>
                                <NGi><NText>{{ detail.user.lpu_name }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>Отделение</NText></NGi>
                                <NGi><NText>{{ detail.user.department_name }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>Специальность</NText></NGi>
                                <NGi><NText>{{ detail.user.prvs_name }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <NGrid :cols="2">
                                <NGi><NText>Должность</NText></NGi>
                                <NGi><NText>{{ detail.user.prvd_name }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                    </NList>
                </NCard>

                <NCard title="Доступ" size="small">
                    <template #header-extra>
                        <NButton v-if="canEdit && detail.x_user" text @click="hasShowUpdateAccessModal = true">
                            <template #icon><NIcon :component="IconPencil" /></template>
                            Редактировать
                        </NButton>
                    </template>
                    <NList v-if="detail.x_user" hoverable>
                        <NListItem>
                            <template #suffix>
                                <AppCopyButton :value="detail.x_user.GeneralLogin" />
                            </template>
                            <NGrid :cols="6">
                                <NGi><NText>Логин</NText></NGi>
                                <NGi span="5"><NText>{{ detail.x_user.GeneralLogin }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                        <NListItem>
                            <template v-if="canEdit" #suffix>
                                <EdsIconButton :icon="IconLockCog" hint="Подменить пароль" @click="changePassword" />
                            </template>
                            <NGrid :cols="6">
                                <NGi><NText>Пароль</NText></NGi>
                                <NGi span="5"><NText class="blur-sm select-none">{{ detail.x_user.GeneralPassword }}</NText></NGi>
                            </NGrid>
                        </NListItem>
                    </NList>
                    <NEmpty v-else class="my-2" />
                </NCard>

                <NCard title="Должности" size="small">
                    <template #header-extra>
                        <NButton v-if="canEdit" text @click="onShowCreatePostModal">
                            <template #icon><NIcon :component="IconPlus" /></template>
                            Добавить
                        </NButton>
                    </template>
                    <NList hoverable>
                        <NListItem v-for="job in detail.jobs" :key="job.DocPRVDID">
                            <template v-if="canEdit" #suffix>
                                <NButton text @click="onShowViewPostModal(job)">
                                    <NIcon :component="IconPencil" :size="18" />
                                </NButton>
                            </template>
                            <NGrid :cols="6">
                                <NGi><NText>{{ job.code === '' ? 'Нет кода' : job.code }}</NText></NGi>
                                <NGi span="4"><NText>{{ job.name }}</NText></NGi>
                                <NGi class="inline-flex">
                                    <NTag v-if="job.main_work_place" size="small" type="success">Основная</NTag>
                                    <NTag v-if="job.is_dismissal" size="small" type="error">Увольнение</NTag>
                                </NGi>
                            </NGrid>
                        </NListItem>
                        <NEmpty v-if="detail.jobs.length === 0" />
                    </NList>
                </NCard>
            </NSpace>

            <NEmpty v-else description="Локальный сотрудник без учётной записи МИС" class="my-6" />

            <template #footer>
                <NFlex justify="space-between" style="width: 100%">
                    <NButton v-if="row.staff_id" tag="a" :href="route('staff.show', {staff: row.staff_id})">
                        Перейти к сертификату
                    </NButton>
                    <NButton v-if="row.mis_user_id" tag="a" :href="route('mis.user', {userId: row.mis_user_id})" secondary>
                        Открыть в ТМ:МИС
                    </NButton>
                </NFlex>
            </template>
        </NDrawerContent>
    </NDrawer>

    <template v-if="detail">
        <CreateAccountModal v-model:show="hasShowEditAccountModal" :lpus="detail.lpus" :prvd="detail.prvd" :prvs="detail.prvs" :departments="detail.departments" :user-edit="detail.user" />
        <UpdateAccessModal v-model:show="hasShowUpdateAccessModal" :user="detail.user" :x-user="detail.x_user" />
        <UserRoleModal v-if="detail.x_user" v-model:show="hasShowUserRoleModal" :user="detail.user" :x-user="detail.x_user" :roles="detail.roles" :user-roles="detail.user_roles" :role-templates="detail.role_templates" />
        <CreatePostModal v-model:show="hasShowCreatePostModal" :user="detail.user" :prvd="detail.prvd" :prvs="detail.prvs" :departments="detail.departments" :posts="detail.jobs" :department-types="detail.department_types" :department-profiles="detail.department_profiles" :post="currentPost" />
    </template>
</template>
