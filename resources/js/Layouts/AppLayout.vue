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
    IconUser, IconDatabaseEdit,
    IconCertificate,
    IconLayoutDashboard,
    IconHistory,
    IconSettings
} from '@tabler/icons-vue'
import Banner from '@/Components/Banner.vue'
import {NIcon, NFlex} from "naive-ui"
import {useStorage} from "@vueuse/core"
import packageJson from "../../../package.json"
import NaiveLayout from "@/Layouts/NaiveLayout.vue"
import {isLargeScreen, isMediumScreen, isSmallScreen} from "@/Utils/mediaQuery.js";
import { useI18n } from 'vue-i18n'
import {useCheckScope} from "@/Composables/useCheckScope.js";
import {useAppTheme} from "@/Composables/useAppTheme.js";
import {IconSun, IconMoon} from "@tabler/icons-vue";
const { t } = useI18n()
const {hasRole, hasScope, scopes, roles} = useCheckScope()
const {isDark, toggleTheme} = useAppTheme()
import {onMounted} from "vue"
import {generateBreadcrumbs} from "@/Utils/breadcrumbs.js";
import EdsBreadcrumbs from "@/Components/Eds/EdsBreadcrumbs.vue";

const props = defineProps({
    title: String,
    subtitle: String,
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

/** Plain link label, or a link label with a small count badge on the right. */
function renderLinkLabel(text, href, badge) {
    return () => h(
        Link,
        { href },
        {
            default: () => badge
                ? [
                    h(
                        NFlex,
                        {
                            justify: 'space-between',
                            align: 'center',
                        },
                        {
                            default: () => [
                                h('span', null, text),
                                h('span', {
                                    style: 'margin-left:auto;font-size:11px;font-weight:600;padding:1px 7px;border-radius:9px;background:rgba(24,160,88,.12);color:var(--primary-color);'
                                }, String(badge))
                            ]
                        }
                    ),
                ]
                : text
        }
    )
}

const certificateTotal = computed(() => page.props.certificateStats?.total ?? 0)

const rawMenuOptions = computed(() => [
    {
        type: 'group',
        key: 'g-main',
        label: 'ОСНОВНОЕ',
        children: [
            // {
            //     label: renderLinkLabel('Персонал', route('staff.index')),
            //     key: 'staff',
            //     icon: renderIcon(IconUsers),
            //     show: hasScope(scopes.CAN_READ_STAFF)
            // },
            {
                label: renderLinkLabel('Дашборд', route('dashboard')),
                key: 'dashboard',
                icon: renderIcon(IconLayoutDashboard),
                show: hasScope(scopes.CAN_READ_STAFF)
            },
            {
                label: renderLinkLabel('Сертификаты', route('certificates.index'), certificateTotal.value),
                key: 'certificates-index',
                icon: renderIcon(IconCertificate),
                show: hasScope(scopes.CAN_READ_STAFF)
            },
            {
                label: renderLinkLabel('Сотрудники', route('staff')),
                key: 'staff',
                icon: renderIcon(IconUsers),
                show: hasScope(scopes.CAN_READ_STAFF)
            },
            {
                label: renderLinkLabel('Журнал событий', route('journal')),
                key: 'journal',
                icon: renderIcon(IconHistory),
                show: hasScope(scopes.CAN_READ_STAFF)
            },
        ].filter(item => item.show),
    },
    {
        type: 'group',
        key: 'g-admin',
        label: 'АДМИНИСТРИРОВАНИЕ',
        children: [
            {
                label: renderLinkLabel('Настройки', route('settings')),
                key: 'settings',
                icon: renderIcon(IconSettings),
                show: hasScope(scopes.CAN_ADMIN)
            },
            // {
            //     label: renderLinkLabel('Журналы', route('journals.index')),
            //     key: 'journals',
            //     icon: renderIcon(IconTable),
            //     show: hasScope(scopes.CAN_READ_JOURNALS)
            // },
            {
                label: renderLinkLabel('ТМ:МИС', route('mis.index')),
                key: 'mis',
                icon: renderIcon(IconDatabaseEdit),
                show: (hasRole(roles.ROLE_HELPER_MIS) || hasRole(roles.ROLE_ADMIN))
            },
            {
                label: renderLinkLabel('Администрирование', route('admin.index')),
                key: 'admin',
                icon: renderIcon(IconTable),
                show: hasScope(scopes.CAN_ADMIN)
            },
        ].filter(item => item.show),
    },
])

const menuOptions = computed(() => rawMenuOptions.value.filter(group => group.children.length))

const CERTIFICATES_KEY_BY_PATH = {
    '/dashboard': 'dashboard',
    '/certificates': 'certificates-index',
    '/journal': 'journal',
    '/staff': 'staff',
    '/settings': 'settings',
}

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
    const path = router.page.url.split('?')[0]
    if (CERTIFICATES_KEY_BY_PATH[path])
        return CERTIFICATES_KEY_BY_PATH[path]

    const crumbs = generateBreadcrumbs(router.page.url)
    if (crumbs.length > 0)
        return crumbs[0].key
    return crumbs
})

const user = ref(page.props.auth.user)

// console.log(page.props)
const showWelcomeDialog = ref(false)
const lastUpdate = useStorage('last-update', null)
if (!lastUpdate.value || lastUpdate.value !== packageJson.date) {
    showWelcomeDialog.value = true
}

const breabcrumbs = computed(() => generateBreadcrumbs(router.page.url))

const activeTitle = computed(() => {
    if (props.title) {
        return props.title
    }
    return breabcrumbs.value.pop().label
})

const logout = () => {
    router.post(route('logout'))
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

        <div class="h-screen max-h-screen relative overflow-hidden">
            <NLayout position="absolute">
                <NLayoutHeader class="py-3.5 pr-[24px]" bordered>
                    <NFlex justify="space-between" align="center" class="relative" :wrap="false">
                        <NFlex justify="space-between" :size="0" class="relative">
                            <Link href="/" class="flex items-center gap-x-4 flex-none" :style="isLargeScreen ? 'width:260px;padding:0 24px' : 'padding:0 24px'">
                                <NImage src="/assets/svg/logo-short.svg" preview-disabled width="32" height="32" class="h-8 w-8" />
                                <span class="text-lg">
                                    ЭРСП
                                </span>
                            </Link>
                            <div class="border-r border-[var(--n-border-color)] absolute inset-y-0 right-0 -top-1/2 h-[65px]"></div>
                        </NFlex>
                        <div class="flex-1 min-w-0 px-4">
                            <div class="text-base font-semibold leading-tight truncate">{{ activeTitle }}</div>
                            <NText v-if="subtitle" depth="3" class="text-xs truncate" style="display:block">
                                {{ subtitle }}
                            </NText>
                        </div>
                        <NFlex align="center" :wrap="false" class="flex-none">
                            <slot name="headerActions" />
                        </NFlex>
                        <NSpace class="-m-5 ml-0 -mr-[24px]" :size="0" align="center">
                            <NButton title="Сменить тему" @click="toggleTheme">
                                <NIcon :component="isDark ? IconSun : IconMoon" size="16" />
                            </NButton>
                            <NDropdown v-if="user && isLargeScreen" trigger="click" placement="top-end" :options="userOptions" @select="(key, option) => option.onClick()">
                                <NButton quaternary class="h-[65px] rounded-none hidden md:block">
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
                            <NButton v-if="!isLargeScreen" quaternary class="h-[65px] w-[65px] rounded-none" @click="mobileMenuCollapsed = true">
                                <NIcon :component="IconMenu3" />
                            </NButton>
                        </NSpace>
                    </NFlex>
                </NLayoutHeader>
                <NLayout has-sider position="absolute" style="top: 65px; bottom: 0;">
                    <NLayoutSider v-if="isLargeScreen" collapse-mode="width" bordered collapsed-width="0" width="260">
                        <NMenu :options="menuOptions" :value="currentRoute" :indent="18" />
                    </NLayoutSider>
                    <NLayout content-class="px-4 py-5 lg:px-8 lg:py-2">
                        <main>
                            <NFlex v-if="breabcrumbs.length > 0 || $slots.headermore" justify="space-between" align="center" class="mb-5">
                                <EdsBreadcrumbs v-if="breabcrumbs.length > 0" :items="breabcrumbs" />
                                <div v-else />
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
                <!-- <NLayoutFooter
                    bordered
                    position="absolute"
                    class="p-3 px-[24px]"
                >
                    <NFlex justify="space-between" align="center">
                        <div>
                            &copy; <a href="https://github.com/brusnitsyn" target="_blank">@brusnitsyn</a> 2024 - 2025
                        </div>
                        <NTag v-if="processingCertification.status !== null" size="small" round :type="processingCertification.type">
                            <template #icon />
                            {{ processingCertification.message }}
                        </NTag>
                    </NFlex>
                </NLayoutFooter> -->
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
                <NMenu :options="menuOptions" :value="currentRoute" :indent="18" />
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
