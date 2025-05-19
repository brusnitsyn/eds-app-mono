<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    IconAlertCircleFilled,
    IconCloudUp,
    IconDatabaseSearch,
    IconProgressCheck,
    IconUserCircle,
    IconEdit, IconCopy, IconPencil, IconPlus, IconLockCog, IconSunglasses
} from "@tabler/icons-vue";
import AppCopyButton from "@/Components/AppCopyButton.vue";
import {NButton, NIcon, NTooltip} from "naive-ui";
import CreatePostModal from "@/Pages/MIS/Users/Partials/CreatePostModal.vue";
import EdsIconButton from "@/Components/Eds/EdsIconButton.vue";
import CreateAccountModal from "@/Pages/MIS/Users/Partials/CreateAccountModal.vue";
import UpdateAccessModal from "@/Pages/MIS/Users/Partials/UpdateAccessModal.vue";
import UserRoleModal from "@/Pages/MIS/Users/Partials/UserRoleModal.vue";

const props = defineProps({
    user: Object,
    x_user: Object,
    jobs: Array,
    departments: Array,
    prvd: Array,
    prvs: Array,
    lpus: Array,
    department_types: Array,
    department_profiles: Array,
    roles: Array,
    user_roles: Array,
})
const fullName = computed(() => `${props.user.FAM_V} ${props.user.IM_V} ${props.user.OT_V}`)
const hasShowCreatePostModal = ref(false)
const hasShowEditAccountModal = ref(false)
const hasShowUpdateAccessModal = ref(false)
const hasShowUserRoleModal = ref(false)
const currentPost = ref(null)
const onShowViewPostModal = (post, value) => {
    if (!value) {
        hasShowCreatePostModal.value = false
        currentPost.value = null
        return
    }

    currentPost.value = post
    hasShowCreatePostModal.value = true
}

const onShowCreatePostModal = () => {
    hasShowCreatePostModal.value = true
    currentPost.value = null
}

watch(() => hasShowCreatePostModal.value, (value) => {
    if (value === false) {
        currentPost.value = null
    }
})
</script>

<template>
    <AppLayout :title="fullName">
        <NGrid :cols="10" :x-gap="16">
            <NGi span="6">
                <NSpace vertical>
                    <NSpace>
                        <NTag v-if="user.PCOD != null" type="info">
                            {{ user.PCOD }}
                            <template #icon>
                                <NTooltip>
                                    <template #trigger>
                                        <NIcon :component="IconDatabaseSearch" :size="20" />
                                    </template>
                                    Код врача
                                </NTooltip>
                            </template>
                        </NTag>
                    </NSpace>

                    <NCard title="Общая информация">
                        <template #header-extra>
                            <NSpace align="center" size="large">
                                <NSpace align="center" size="small">
                                    <NTooltip v-if="user.isSpecial">
                                        <template #trigger>
                                            <NEl class="px-1.5 rounded text-[var(--base-color)] bg-[var(--primary-color)] font-medium cursor-default">
                                                С
                                            </NEl>
                                        </template>
                                        Узкий специалист
                                    </NTooltip>
                                    <NTooltip v-if="user.isDoctor">
                                        <template #trigger>
                                            <NEl class="px-1.5 rounded text-[var(--base-color)] bg-[var(--primary-color)] font-medium cursor-default">
                                                В
                                            </NEl>
                                        </template>
                                        Сотрудник является врачом
                                    </NTooltip>
                                    <NTooltip v-if="user.inTime">
                                        <template #trigger>
                                            <NEl class="px-1.5 rounded text-[var(--base-color)] bg-[var(--primary-color)] font-medium cursor-default">
                                                Р
                                            </NEl>
                                        </template>
                                        Доступен в расписании
                                    </NTooltip>
                                    <NTooltip v-if="user.isDismissal">
                                        <template #trigger>
                                            <NEl class="px-1.5 rounded text-[var(--base-color)] bg-[var(--error-color)] font-medium cursor-default">
                                                У
                                            </NEl>
                                        </template>
                                        Увольнение
                                    </NTooltip>
                                </NSpace>

                                <NDivider vertical class="h-6 w-0.5 bg-gray-200"></NDivider>

                                <NButton text @click="hasShowUserRoleModal = true">
                                    <template #icon>
                                        <IconSunglasses />
                                    </template>
                                    Роли
                                </NButton>

                                <NButton text @click="hasShowEditAccountModal = true">
                                    <template #icon>
                                        <IconPencil />
                                    </template>
                                    Редактировать
                                </NButton>
                            </NSpace>
                        </template>
                        <NList hoverable>
                            <NListItem>
                                <template v-if="user.SS" #suffix>
                                    <AppCopyButton :value="user.SS" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>СНИЛС</NText></NGi>
                                    <NGi><NText>{{ user.SS }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="user.M_NAMES" #suffix>
                                    <AppCopyButton :value="user.M_NAMES" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>ЛПУ</NText></NGi>
                                    <NGi><NText>{{ user.M_NAMES }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="user.DepartmentName" #suffix>
                                    <AppCopyButton :value="user.DepartmentName" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>Отделение</NText></NGi>
                                    <NGi><NText>{{ user.DepartmentName }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="user.PRVS_NAME" #suffix>
                                    <AppCopyButton :value="user.PRVS_NAME" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>Специальность</NText></NGi>
                                    <NGi><NText>{{ user.PRVS_NAME }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="user.NAME" #suffix>
                                    <AppCopyButton :value="user.NAME" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>Должность</NText></NGi>
                                    <NGi><NText>{{ user.NAME }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                        </NList>
                    </NCard>
                </NSpace>
            </NGi>

            <NGi span="4">
                <NSpace vertical :size="16" class="mt-[37px]">
                    <NCard title="Доступ">
                        <template #header-extra>
                            <NButton text @click="hasShowUpdateAccessModal = true">
                                <template #icon>
                                    <IconPencil />
                                </template>
                                Редактировать
                            </NButton>
                        </template>
                        <NList v-if="x_user" hoverable>
                            <NListItem>
                                <template #suffix>
                                    <AppCopyButton :value="x_user.GeneralLogin" />
                                </template>
                                <NGrid :cols="6">
                                    <NGi><NText>Логин</NText></NGi>
                                    <NGi span="5"><NText>{{ x_user.GeneralLogin }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template #suffix>
                                    <NFlex :wrap="false">
                                        <EdsIconButton :icon="IconLockCog" hint="Подменить пароль" />
<!--                                        <AppCopyButton :value="''" />-->
                                    </NFlex>
                                </template>
                                <NGrid :cols="6">
                                    <NGi><NText>Пароль</NText></NGi>
                                    <NGi span="5"><NText class="blur-sm select-none">{{ x_user.GeneralPassword }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                        </NList>
                        <NEmpty v-else class="my-2" />
                    </NCard>

                    <NCard title="Должности">
                        <template #header-extra>
                            <NButton text @click="onShowCreatePostModal">
                                <template #icon>
                                    <IconPlus />
                                </template>
                                Добавить
                            </NButton>
                        </template>
                        <NList hoverable>
                            <NListItem v-if="jobs.length > 0" v-for="job in jobs" :key="job.DocPRVDID">
                                <template #suffix>
                                    <NFlex>
                                        <NTooltip trigger="hover">
                                            <template #trigger>
                                                <NButton text @click="onShowViewPostModal(job, true)">
                                                    <NIcon :component="IconPencil" :size="18" />
                                                </NButton>
                                            </template>
                                            Редактировать
                                        </NTooltip>
                                    </NFlex>
                                </template>
                                <NGrid :cols="6">
                                    <NGi><NText>{{ job.PCOD === '' ? 'Нет кода' : job.PCOD }}</NText></NGi>
                                    <NGi span="4"><NText>{{ job.NAME }}</NText></NGi>
                                    <NGi class="inline-flex">
                                        <NTag v-if="job.MainWorkPlace"
                                              size="small"
                                              type="success">
                                            Основная
                                        </NTag>
                                        <NTag v-if="job.isDismissal"
                                              size="small"
                                              type="error">
                                            Увольнение
                                        </NTag>
                                    </NGi>
                                </NGrid>
                            </NListItem>
                            <NEmpty v-else />
                        </NList>
                    </NCard>
                </NSpace>
            </NGi>
        </NGrid>
        <CreatePostModal v-model:show="hasShowCreatePostModal"
                         :user="user"
                         :prvd="prvd"
                         :prvs="prvs"
                         :departments="departments"
                         :posts="jobs"
                         :departmentTypes="department_types"
                         :departmentProfiles="department_profiles"
                         :post="currentPost"
        />
        <CreateAccountModal v-model:show="hasShowEditAccountModal"
                            :lpus="lpus"
                            :prvd="prvd"
                            :prvs="prvs"
                            :departments="departments"
                            :user-edit="user" />
        <UpdateAccessModal v-model:show="hasShowUpdateAccessModal"
                           :user="user"
                           :x-user="x_user" />
        <UserRoleModal v-model:show="hasShowUserRoleModal"
                       :user="user"
                       :x-user="x_user"
                       :roles="roles"
                       :user-roles="user_roles" />
    </AppLayout>
</template>

<style scoped>
:deep(.n-divider) {
    @apply !w-0.5;
}
</style>
