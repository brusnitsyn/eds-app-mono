<script setup>
import {computed, h, ref} from 'vue'
import {Head, Link, router, usePage} from '@inertiajs/vue3'
import {
    IconDoorExit,
    IconMinusVertical,
    IconArrowLeft,
    IconUsers,
    IconMenu3,
    IconTable,
    IconUser, IconDatabaseEdit
} from '@tabler/icons-vue'
import Banner from '@/Components/Banner.vue'
import {NIcon} from "naive-ui"
import {useStorage} from "@vueuse/core"
import packageJson from "../../../package.json"
import NaiveLayout from "@/Layouts/NaiveLayout.vue"
import {isLargeScreen, isMediumScreen, isSmallScreen} from "@/Utils/mediaQuery.js";
import { useI18n } from 'vue-i18n'
import {useCheckScope} from "@/Composables/useCheckScope.js";
const { t } = useI18n()
const {hasRole, hasScope, scopes, roles} = useCheckScope()
import {onMounted} from "vue"

const props = defineProps({
    title: String,
});

const page = usePage()

const showingNavigationDropdown = ref(false)

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
}

const largeMenuCollapsed = useStorage('side-collapsed', false)
const mobileMenuCollapsed = ref(false)

function renderIcon(icon) {
    return () => h(NIcon, null, { default: () => h(icon) })
}

const menuOptions = [
    {
        label: () => h(
            Link,
            {
                href: route('staff.index'),
            },
            {
                default: () => 'Персонал'
            }
        ),
        key: 'Staff',
        icon: renderIcon(IconUsers),
        show: hasScope(scopes.CAN_READ_STAFF)
    },
    {
        label: () => h(
            Link,
            {
                href: route('journals.index'),
            },
            {
                default: () => 'Журналы'
            }
        ),
        key: 'Journals',
        icon: renderIcon(IconTable),
        show: hasScope(scopes.CAN_READ_JOURNALS)
    },
    {
        label: () => h(
            Link,
            {
                href: route('mis.index'),
            },
            {
                default: () => 'ТМ:МИС'
            }
        ),
        key: 'Mis',
        icon: renderIcon(IconDatabaseEdit),
        show: (hasRole(roles.ROLE_HELPER_MIS) || hasRole(roles.ROLE_ADMIN))
    },
    {
        label: () => h(
            Link,
            {
                href: route('admin.index'),
            },
            {
                default: () => 'Администрирование'
            }
        ),
        key: 'Admin',
        icon: renderIcon(IconTable),
        show: hasScope(scopes.CAN_ADMIN)
    }
]

const userOptions = [
    {
        label: 'Мой профиль',
        key: 'my-profile',
        icon: renderIcon(IconUser),
        onClick: () => router.visit(route('profile.show'))
    },
    {
        label: 'Выйти из учетной записи',
        key: 'user-exit',
        icon: renderIcon(IconDoorExit),
        onClick: () => logout()
    },
]

const currentRoute = computed(() => {
    return router.page.component.substring(0, router.page.component.indexOf('/'))
})

const user = ref(page.props.auth.user)

// console.log(page.props)
const showWelcomeDialog = ref(false)
const lastUpdate = useStorage('last-update', null)
if (!lastUpdate.value || lastUpdate.value !== packageJson.date) {
    showWelcomeDialog.value = true
}

const breabcrumbs = computed(() => {
    const pathParts = page?.url?.split('/').filter((part) => part?.trim() !== '');
    return pathParts?.map((part, partIndex) => {
        const previousParts = pathParts.slice(0, partIndex);
        return {
            label: part,
            href: previousParts?.length > 0 ? `/${previousParts?.join('/')}/${part}` : `/${part}`,
            isLast: Boolean(previousParts?.length)
        }
    }) || []
})

const activeTitle = computed(() => {
    if (props.title) {
        return props.title
    }

    const pathParts = page?.props.ziggy.location.split('/').filter((part) => part?.trim() !== '')
    return t(`pages.${pathParts.pop()}`)
})

const logout = () => {
    router.post(route('logout'));
    router.replace(route('login'))
}

const processingCertification = ref({
    status: null,
    message: ''
})

onMounted(() => {
    window.Echo.channel(`certificate.processing`)
        .listen('CertificateProcessingEvent', (data) => {
            processingCertification.value = data
        })
})
</script>

<template>
    <Head :title="activeTitle" />
    <NaiveLayout>
        <Banner />

        <div class="h-screen max-h-screen bg-gray-100">
            <NLayout position="absolute">
                <NLayoutHeader class="py-3.5 px-[24px]" bordered>
                    <NFlex justify="space-between" align="center" class="relative">
                        <Link href="/" class="flex items-center gap-x-4">
                            <NImage src="/assets/svg/logo-short.svg" preview-disabled width="32" height="32" class="h-8 w-8" />
                            <span class="text-lg">
                                ЭРСП
                            </span>
                        </Link>
                        <NSpace class="-m-5 -mr-[24px]" :size="0" align="center">
                            <NDropdown v-if="user && isLargeScreen" trigger="click" placement="top-end" :options="userOptions" @select="(key, option) => option.onClick()">
                                <NButton quaternary class="h-[61px] rounded-none hidden md:block">
                                    <NSpace align="center">
                                        <NSpace vertical align="end" :size="2">
                                            <NText class="font-semibold">
                                                {{ user.name }}
                                            </NText>
                                            <NText>
                                                {{ user.role.name }}
                                            </NText>
                                        </NSpace>
                                        <NAvatar :src="user.profile_photo_url" round />
                                    </NSpace>
                                </NButton>
                            </NDropdown>
                            <NButton v-if="!isLargeScreen" quaternary class="h-[61px] w-[61px] rounded-none" @click="mobileMenuCollapsed = true">
                                <NIcon :component="IconMenu3" />
                            </NButton>
                        </NSpace>
                    </NFlex>
                </NLayoutHeader>
                <NLayout has-sider position="absolute" style="top: 61px; bottom: 47px">
                    <NLayoutSider v-if="isLargeScreen" collapse-mode="width" collapsed-width="0" width="260" :collapsed="largeMenuCollapsed" show-trigger @collapse="largeMenuCollapsed = true"
                                  @expand="largeMenuCollapsed = false" :collapsed-trigger-class="largeMenuCollapsed === true ? '!-right-5 !top-1/4' : ''" trigger-class="!top-1/4" bordered content-class="">
                        <NMenu :options="menuOptions" :value="currentRoute" />
                    </NLayoutSider>
                    <NLayout content-class="px-4 py-5 lg:px-14 lg:py-7">
                        <main>
                            <NFlex justify="space-between" align="center" class="mb-5">
                                <NSpace vertical :size="0">
                                    <NBreadcrumb v-if="breabcrumbs.length > 1">
                                        <template v-for="breadcrumb in breabcrumbs" :key="breadcrumb.label">
                                            <NBreadcrumbItem v-if="!breadcrumb.isLast">
                                                <Link :href="!breadcrumb.isLast ? breadcrumb.href : ''">
                                                    {{ t(`pages.${breadcrumb.label}`) }}
                                                </Link>
                                            </NBreadcrumbItem>
                                        </template>
                                    </NBreadcrumb>
                                    <NFlex align="center" justify="start" :size="6">
<!--                                        <NButton text>-->
<!--                                            <template #icon>-->
<!--                                                <NIcon :component="IconArrowLeft" size="28" />-->
<!--                                            </template>-->
<!--                                        </NButton>-->
<!--                                        <NDivider vertical class="!h-[25px] !bg-[#1f2225]" />-->
                                        <NH1 class="!my-0">
                                            {{ activeTitle }}
                                        </NH1>
                                    </NFlex>
                                </NSpace>
                                <NSpace>
                                    <slot name="headermore" />
                                </NSpace>
                            </NFlex>
                            <NP v-if="$slots.subheader">
                                <slot name="subheader" />
                            </NP>
                            <NMessageProvider>
                                <slot />
                            </NMessageProvider>
                        </main>
                    </NLayout>
                </NLayout>
                <NLayoutFooter
                    bordered
                    position="absolute"
                    class="p-3 px-[24px]"
                >
                    <NFlex justify="space-between" align="center">
                        <div>
                            &copy; <Link href="https://github.com/brusnitsyn">@brusnitsyn</Link> 2024
                        </div>
                        <NTag v-if="processingCertification.status !== null" size="small" round :type="processingCertification.type">
                            <template #icon />
                            {{ processingCertification.message }}
                        </NTag>
                    </NFlex>
                </NLayoutFooter>
            </NLayout>
        </div>

        <NDrawer v-model:show="mobileMenuCollapsed" :placement="isMediumScreen ? 'left' : 'top'" width="240">
            <NDrawerContent header-class="!font-normal !text-base !leading-[1]" body-content-class="!px-0 !py-0" class="">
                <template #header>
                    <NFlex justify="space-between" align="center">
                        <Link href="/">
                            EDS
                        </Link>
                        <NSpace v-if="!isMediumScreen" class="-m-5 -mr-[24px]" :size="0">
                            <NButton quaternary class="h-[50px] w-[50px] rounded-none" @click="mobileMenuCollapsed = false">
                                <NIcon :component="IconMenu3" />
                            </NButton>
                        </NSpace>
                    </NFlex>
                </template>
                <NMenu :options="menuOptions" :value="currentRoute" />
            </NDrawerContent>
        </NDrawer>

        <NModal v-model:show="showWelcomeDialog" :closable="false" preset="card" class="max-w-xl" :mask-closable="false" @after-leave="lastUpdate = packageJson.date">
            <template #header>
                <NSpace vertical :size="0">
                    <NText class="text-base">
                        ЧТО НОВОГО
                    </NText>
                    <NSpace inline justify="center" :size="4" align="center">
                        <NTag type="primary" size="small">
                            {{ packageJson.version }}
                        </NTag>
                        <NText class="text-sm">
                            от
                        </NText>
                        <NTime :time="packageJson.date" format="dd.MM.yyyy" class="text-sm" />
                    </NSpace>
                </NSpace>
            </template>

            <NScrollbar class="max-h-96">
                <div v-html="usePage().props.newspaper" class="prose-naive" />
            </NScrollbar>

            <NButton block secondary type="primary" class="mt-4" @click="showWelcomeDialog = false">
                Понятно, спасибо
            </NButton>
        </NModal>
    </NaiveLayout>
</template>

<style>

</style>
