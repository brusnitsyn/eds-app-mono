<script setup>
import {
    NGrid, NGi, NCard, NStatistic, NFlex, NIcon, NButton, NTag, NTime, NEmpty,
    NList, NListItem, NThing, NAvatar, NText, NSpace
} from "naive-ui"
import {Link} from "@inertiajs/vue3"
import {IconCircleCheck, IconClock, IconArrowRight} from "@tabler/icons-vue"
import {certificateStatusDef, certificateDaysLabel, staffInitials, avatarColor} from "@/Utils/certificateStatus.js"
import StatusTag from "./StatusTag.vue"
import EdsWidget from "../../../Components/Eds/EdsWidget.vue";

defineProps({
    stats: Object,
    dashboard: Object,
})

const emit = defineEmits(["open-detail"])

function daysColor(status) {
    const type = certificateStatusDef(status).type
    return type === "success" ? "#18a058" : type === "warning" ? "#f0a020" : "#d03050"
}

function eventColor(type) {
    return type === "revoke" ? "#d03050" : "#18a058"
}
</script>

<template>
    <NFlex vertical :size="16">
        <NGrid :cols="4" :x-gap="16">
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace>
                            <span>Всего сертификатов</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.total" />
                    <div class="text-xs text-[var(--n-close-icon-color)] mt-2">по {{ dashboard.staffTotal }} сотрудникам</div>
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <span>Действительны</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.valid" />
                    <div class="text-xs text-[var(--n-close-icon-color)] mt-2">активные подписи</div>
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <span>Истекают за 30 дней</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="stats.expiring" />
                    <div class="text-xs text-[var(--n-close-icon-color)] mt-2">требуют продления</div>
                </EdsWidget>
            </NGi>
            <NGi>
                <EdsWidget>
                    <template #header>
                        <NSpace align="center">
                            <span>Покрытие УКЭП</span>
                        </NSpace>
                    </template>
                    <NStatistic :value="dashboard.coverage" suffix="%" />
                    <div class="text-xs text-[var(--n-close-icon-color)] mt-2">сотрудников с сертификатом</div>
                </EdsWidget>
            </NGi>
        </NGrid>

        <EdsWidget header="Распределение сертификатов по статусу">
            <div class="flex h-4 rounded-full overflow-hidden bg-gray-100 dark:bg-white/10 mt-3">
                <div v-for="seg in dashboard.distSegments" :key="seg.key" :style="{width: seg.width, background: seg.color}" />
            </div>
            <NFlex class="mt-4" :size="24">
                <NFlex v-for="seg in dashboard.distSegments" :key="seg.key" align="center" :size="8">
                    <span class="inline-block w-2.5 h-2.5 rounded" :style="{background: seg.color}" />
                    <NText depth="3">{{ seg.label }}</NText>
                    <NText strong>{{ seg.count }}</NText>
                </NFlex>
            </NFlex>
        </EdsWidget>

        <NGrid :cols="24" :x-gap="16">
            <NGi :span="14">
                <EdsWidget header="Истекают в ближайшие 30 дней" class="max-h-[420px] overflow-hidden" content-class="relative" content-scrollable>
                    <template #header-extra>
                        <Link :href="route('certificates.index')">
                            <NButton text type="primary">
                                Все
                                <template #icon>
                                    <NIcon :component="IconArrowRight" />
                                </template>
                            </NButton>
                        </Link>
                    </template>
                    <NEmpty v-if="!dashboard.expiringSoon.length" class="absolute inset-x-0 inset-y-1/3" description="Нет сертификатов, требующих внимания" />
                    <NList v-else hoverable clickable>
                        <NListItem v-for="cert in dashboard.expiringSoon" :key="cert.id" class="cursor-pointer" @click="emit('open-detail', cert)">
                            <NThing>
                                <template #avatar>
                                    <NAvatar round :color="avatarColor(cert.staff_id)" style="color:#fff;font-weight:600">
                                        {{ staffInitials(cert.fio) }}
                                    </NAvatar>
                                </template>
                                <template #header>{{ cert.fio }}</template>
                                <template #description>
                                    <NFlex :size="4" align="center">
                                        <span>{{ cert.position }} · до</span>
                                        <NTime format="dd.MM.yyyy" :time="Number(cert.valid_to)" />
                                    </NFlex>
                                </template>
                                <template #header-extra>
                                    <NFlex align="center" :size="8">
                                        <NText class="text-xs font-semibold" :style="{color: daysColor(cert.status)}">
                                            {{ certificateDaysLabel(cert) }}
                                        </NText>
                                        <StatusTag :status="cert.status" />
                                    </NFlex>
                                </template>
                            </NThing>
                        </NListItem>
                    </NList>
                </EdsWidget>
            </NGi>
            <NGi :span="10">
                <EdsWidget header="Активность загрузок · 7 дней" style="height: 100%">
                    <NFlex align="flex-end" :size="10" style="height: 150px">
                        <NFlex v-for="day in dashboard.activity" :key="day.date" vertical align="center" justify="flex-end" :size="8" style="flex:1; height:100%">
                            <NText depth="3" class="text-xs">{{ day.n }}</NText>
                            <div class="rounded-t mx-auto" style="width:100%;max-width:28px;background:#18a058;opacity:.85" :style="{height: day.barH, minHeight: '4px'}" />
                            <NText depth="2" class="text-xs capitalize">{{ day.d }}</NText>
                        </NFlex>
                    </NFlex>
                </EdsWidget>
            </NGi>
        </NGrid>

        <EdsWidget header="Последние события" class="max-h-[270px] overflow-hidden" content-scrollable>
            <template #header-extra>
                <Link :href="route('journal')">
                    <NButton text type="primary">
                        Весь журнал
                        <template #icon>
                            <NIcon :component="IconArrowRight" />
                        </template>
                    </NButton>
                </Link>
            </template>
            <NEmpty v-if="!dashboard.recentEvents.length" description="Событий пока нет" />
            <NFlex v-else vertical :size="0">
                <NFlex v-for="event in dashboard.recentEvents" :key="event.id" align="center" :size="13" class="py-3 border-b border-[var(--n-border-color)] last:border-b-0 last:dark:border-b-0">
                    <span class="w-[9px] h-[9px] rounded-full flex-none" :style="{background: eventColor(event.type)}" />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ event.detail }}</div>
                        <div class="text-xs truncate text-[var(--n-close-icon-color)]">{{ event.owner }}</div>
                    </div>
                    <NTag size="small" :type="event.type === 'revoke' ? 'error' : 'success'">
                        {{ event.type === "revoke" ? "Отзыв" : "Загрузка" }}
                    </NTag>
                    <NTime :time="new Date(event.time)" format="HH:mm" class="text-xs flex-none" style="width: 40px; text-align: right" />
                </NFlex>
            </NFlex>
        </EdsWidget>
    </NFlex>
</template>
