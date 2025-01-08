<script setup>
import {computed, h, ref} from 'vue'
import {Head, Link, router, usePage} from '@inertiajs/vue3'
import {IconDoorExit, IconSettings2, IconUserHexagon, IconUsers, IconMenu3} from '@tabler/icons-vue'
import Banner from '@/Components/Banner.vue'
import {NIcon} from "naive-ui"
import {useStorage} from "@vueuse/core"
import packageJson from "../../../package.json"
import NaiveLayout from "@/Layouts/NaiveLayout.vue"
import {isLargeScreen, isMediumScreen, isSmallScreen} from "@/Utils/mediaQuery.js";

defineProps({
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

const largeMenuCollapsed = ref(false)
const mobileMenuCollapsed = ref(false)

function renderIcon(icon) {
    return () => h(NIcon, null, { default: () => h(icon) })
}

const menuOptions = [
    {
        label: () => h(
            Link,
            {
                href: '/staff',
            },
            {
                default: () => 'Персонал'
            }
        ),
        key: 'Staff',
        icon: renderIcon(IconUsers)
    },
    {
        label: () => h(
            Link,
            {
                href: '/journal',
            },
            {
                default: () => 'Журналы'
            }
        ),
        key: 'Journal',
        icon: renderIcon(IconUsers)
    },
]

const userOptions = [
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

console.log(page.props)
const showWelcomeDialog = ref(false)


const lastUpdate = useStorage('lastUpdate', null)
if (!lastUpdate.value || lastUpdate.value !== packageJson.version) {
    showWelcomeDialog.value = true
    localStorage.setItem('lastUpdate', '2022-01-01')
}

const logout = () => {
    router.post(route('logout'));
    router.reload()
}
</script>

<template>
    <Head :title="title" />
    <NaiveLayout>
        <Banner />

        <div class="h-screen max-h-screen bg-gray-100">
            <NLayout position="absolute">
                <NLayoutHeader class="p-3.5 px-[24px]" bordered>
                    <NFlex justify="space-between" align="center">
                        <Link href="/">
                            EDS
                        </Link>
                        <NSpace class="-m-5 -mr-[24px]" :size="0">
                            <NDropdown v-if="user && isLargeScreen" trigger="click" placement="top-end" :options="userOptions" @select="(key, option) => option.onClick()">
                                <NButton quaternary class="h-[50px] rounded-none hidden md:block">
                                    <NSpace align="center">
                                        <NSpace vertical align="end" :size="2">
                                            <NText class="font-semibold">
                                                {{ user.name }}
                                            </NText>
                                            <NText>
                                                {{ user.login }}
                                            </NText>
                                        </NSpace>
                                        <NAvatar :src="user.profile_photo_url" round />
                                    </NSpace>
                                </NButton>
                            </NDropdown>
                            <NButton v-if="!isLargeScreen" quaternary class="h-[50px] w-[50px] rounded-none" @click="mobileMenuCollapsed = true">
                                <NIcon :component="IconMenu3" />
                            </NButton>
                        </NSpace>
                    </NFlex>
                </NLayoutHeader>
                <NLayout has-sider position="absolute" style="top: 50px; bottom: 46px">
                    <NLayoutSider v-if="isLargeScreen" collapse-mode="width" collapsed-width="0" width="240" :collapsed="largeMenuCollapsed" show-trigger @collapse="largeMenuCollapsed = true"
                                  @expand="largeMenuCollapsed = false" :collapsed-trigger-class="largeMenuCollapsed === true ? '!-right-5 !top-1/4' : ''" trigger-class="!top-1/4" bordered content-class="">
                        <NMenu :options="menuOptions" :value="currentRoute" />
                    </NLayoutSider>
                    <NLayout content-class="px-4 py-5 lg:px-14 lg:py-7">
                        <main>
                            <NFlex v-if="$slots.header || $slots.headermore" justify="space-between" align="center" class="mb-5">
                                <NH1 v-if="$slots.header" class="!mb-0">
                                    <slot name="header" />
                                </NH1>
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
                    &copy; <Link href="https://github.com/brusnitsyn">@brusnitsyn</Link> 2024
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
        <NModal v-model:show="showWelcomeDialog" preset="card" class="max-w-xl" :mask-closable="false">
            <template #header>
                <NSpace vertical :size="0">
                    <NText class="text-base">
                        ЧТО НОВОГО
                    </NText>
                    <NFlex size="small" align="center">
                        <NTag type="primary" size="small">
                            {{ packageJson.version }}
                        </NTag>
                        <div class="text-sm">
                            от
                        </div>
                        <NTime :time="packageJson.date" format="dd.MM.yyyy" class="text-sm" />
                    </NFlex>
                </NSpace>
            </template>
            <div class="bg-white">

            </div>
        </NModal>
    </NaiveLayout>
</template>
