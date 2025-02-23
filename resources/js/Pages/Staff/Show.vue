<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    NAvatar,
    NButton,
    NCard, NEllipsis,
    NFlex,
    NGi,
    NGrid,
    NIcon,
    NList,
    NListItem,
    NSpace,
    NTag,
    NText,
    useThemeVars
} from "naive-ui";
import {format, toDate} from "date-fns";
import {IconAlertCircleFilled, IconCloudUp, IconDatabaseImport, IconProgressCheck, IconDownload} from "@tabler/icons-vue";
import AppCopyButton from "@/Components/AppCopyButton.vue";
const props = defineProps({
    staff: Object
})
</script>

<template>
    <AppLayout :title="staff.full_name">
        <NGrid :cols="5" :x-gap="16">
            <NGi span="3">
                <NSpace vertical class="max-w-3xl">
<!--                    <NCard class="relative">-->
<!--                        <NFlex class="absolute top-4 right-4">-->
<!--                            <NTag v-if="staff.mis_user_id != null" type="info" round>-->
<!--                                ТМ:МИС {{ format(new Date(staff.mis_sync_at), 'dd.MM.yyyy') }} в {{ format(new Date(staff.mis_sync_at), 'HH:mm') }}-->
<!--                                <template #icon>-->
<!--                                    <NIcon :component="IconProgressCheck" :size="20" />-->
<!--                                </template>-->
<!--                            </NTag>-->
<!--                        </NFlex>-->
<!--                        <NButton class="absolute top-2 left-0 -translate-x-1/2" :style="{ border: '1px', borderColor: useThemeVars().value.borderColor, borderStyle: 'solid' }" :color="useThemeVars().value.cardColor" :text-color="useThemeVars().value.textColor3" circle>-->
<!--                            <template #icon>-->
<!--                                <NIcon :component="IconChevronLeft" />-->
<!--                            </template>-->
<!--                        </NButton>-->
<!--                        <NAvatar round :size="120" class="font-bold text-3xl">-->
<!--                            {{ staff.last_name[0] }}{{ staff.first_name[0] }}-->
<!--                        </NAvatar>-->
<!--                        <template #action>-->
<!--                            <NFlex justify="space-between" align="center">-->
<!--                                <NText class="text-lg font-bold">-->
<!--                                    {{ staff.full_name }}-->
<!--                                </NText>-->

<!--                                <NText v-if="staff.mis_user_id">-->
<!--                                    #{{ staff.mis_user_id }}-->
<!--                                </NText>-->
<!--                            </NFlex>-->
<!--                        </template>-->
<!--                    </NCard>-->

                    <NGrid cols="2" x-gap="12">
                        <NGi>
                            <NAlert v-if="staff.certification.is_valid" title="Сертификат действителен" type="success" />
                            <NAlert v-else-if="staff.certification.is_valid && staff.certification.is_request_new" title="Срок действия сертификата подходит к концу" type="warning" />
                            <NAlert v-else title="Сертификат не действителен" type="error" />
                        </NGi>
                        <NGi>
                            <NAlert v-if="staff.certification.close_key_is_valid" title="Закрытый ключ действителен" type="success" />
                            <NAlert v-else title="Закрытый ключ не действителен" type="error" />
                        </NGi>
                    </NGrid>

                    <NSpace>
                        <NTag v-if="staff.mis_user_id !== null" type="info">
                            ТМ:МИС {{ format(new Date(staff.mis_sync_at), 'dd.MM.yyyy') }} в {{ format(new Date(staff.mis_sync_at), 'HH:mm') }}
                            <template #icon>
                                <NIcon :component="IconProgressCheck" :size="20" />
                            </template>
                        </NTag>
                    </NSpace>

                    <NCard title="Общая информация">
                        <!--          <template #header-extra> -->
                        <!--            <NButton text @click="showEdit = true"> -->
                        <!--              <template #icon> -->
                        <!--                <IconEdit /> -->
                        <!--              </template> -->
                        <!--              Изменить -->
                        <!--            </NButton> -->
                        <!--          </template> -->
                        <NList hoverable>
                            <NListItem>
                                <template v-if="staff.inn" #suffix>
                                    <AppCopyButton :value="staff.inn" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>ИНН</NText></NGi>
                                    <NGi><NText>{{ staff.inn }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="staff.snils" #suffix>
                                    <AppCopyButton :value="staff.snils" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>СНИЛС</NText></NGi>
                                    <NGi><NText>{{ staff.snils }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="staff.job_title" #suffix>
                                    <AppCopyButton :value="staff.job_title" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>Должность</NText></NGi>
                                    <NGi><NEllipsis>{{ staff.job_title }}</NEllipsis></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <template v-if="staff.division" #suffix>
                                    <AppCopyButton :value="staff.division" />
                                </template>
                                <NGrid :cols="2">
                                    <NGi><NText>Подразделение</NText></NGi>
                                    <NGi><NText>{{ staff.division }}</NText></NGi>
                                </NGrid>
                            </NListItem>
                        </NList>
                    </NCard>

                    <NCard title="Сведения о сертификате">
                        <template #header-extra>
                            <NButtonGroup>

                                <NTooltip>
                                    <template #trigger>
                                        <NButton quaternary>
                                            <template #icon>
                                                <IconDownload />
                                            </template>
                                        </NButton>
                                    </template>
                                    Скачать сертификат
                                </NTooltip>
                                <NTooltip v-if="staff.certification.has_mis_identical === false && staff.mis_user_id != null">
                                    <template #trigger>
                                        <NButton quaternary>
                                            <template #icon>
                                                <NIcon :component="IconCloudUp" />
                                            </template>
                                        </NButton>
                                    </template>
                                    Установить в ТМ:МИС
                                </NTooltip>
                            </NButtonGroup>
                        </template>
                        <NList hoverable>
                            <NListItem>
                                <template #suffix>
                                    <AppCopyButton :value="staff.certification.serial_number" />
                                </template>
                                <NGrid :cols="2" class="flex items-center">
                                    <NGi>
                                        <NText class="font-bold">
                                            {{ staff.certification.serial_number }}
                                        </NText>
                                    </NGi>
                                    <NGi v-if="staff.certification.has_mis_identical === false && staff.mis_user_id != null">
                                        <NTag type="warning" round class="-ml-6">
                                            <div class="!text-sm">
                                                Сертификат отличается
                                            </div>
                                            <template #icon>
                                                <NIcon :component="IconAlertCircleFilled" :size="20" />
                                            </template>
                                        </NTag>
                                    </NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <NGrid :cols="2">
                                    <NGi><NText>Действителен с</NText></NGi>
                                    <NGi><NTime :time="staff.certification.valid_from" format="dd.MM.yyyy HH:MM" /></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <NGrid :cols="2">
                                    <NGi><NText>Действителен по</NText></NGi>
                                    <NGi><NTime :time="staff.certification.valid_to" format="dd.MM.yyyy HH:MM" /></NGi>
                                </NGrid>
                            </NListItem>
                            <NListItem>
                                <NGrid :cols="2">
                                    <NGi><NText>Закрытый ключ по</NText></NGi>
                                    <NGi>
                                        <NTime :time="staff.certification.close_key_valid_to" format="dd.MM.yyyy HH:MM" />
                                    </NGi>
                                </NGrid>
                            </NListItem>
                        </NList>
                    </NCard>
                </NSpace>
            </NGi>
<!--            <NGi span="2">-->
<!--                <NCard title="Учетные записи">-->
<!--                    <template #header-extra>-->
<!--                        <NButton text>-->
<!--                            <template #icon>-->
<!--                                <NIcon :component="IconSquareRoundedPlus" />-->
<!--                            </template>-->
<!--                            Добавить-->
<!--                        </NButton>-->
<!--                    </template>-->
<!--                    <NList v-if="staff.integrations && staff.integrations.length">-->
<!--                        <NScrollbar class="max-h-[360px] px-4">-->
<!--                            <NListItem v-for="integrate in staff.integrations" :key="integrate.id">-->
<!--                                <NThing :title="integrate.name" content-style="margin-top: 10px;">-->
<!--                                    <template #header-extra>-->
<!--                                        <NSpace>-->
<!--                                            <NButton v-if="integrate.link" text tag="a" :href="integrate.link" target="_blank">-->
<!--                                                <template #icon>-->
<!--                                                    <NIcon :size="20" :component="IconExternalLink" />-->
<!--                                                </template>-->
<!--                                            </NButton>-->
<!--                                            <NButton text @click="handleEdit(integrate)">-->
<!--                                                <template #icon>-->
<!--                                                    <NIcon :size="20" :component="IconEdit" />-->
<!--                                                </template>-->
<!--                                            </NButton>-->
<!--                                            <NButton text type="error" @click="removeIntegrate(staff.id, integrate.id)">-->
<!--                                                <template #icon>-->
<!--                                                    <NIcon :size="20" :component="IconTrash" />-->
<!--                                                </template>-->
<!--                                            </NButton>-->
<!--                                        </NSpace>-->
<!--                                    </template>-->
<!--                                    <template #action>-->
<!--                                        <NSpace size="small">-->
<!--                                            <NButton v-if="integrate.login" size="small" secondary @click="copyIntegratedValue(integrate.login)">-->
<!--                                                Логин-->
<!--                                            </NButton>-->
<!--                                            <NButton v-if="integrate.password" size="small" secondary @click="copyIntegratedValue(integrate.password)">-->
<!--                                                Пароль-->
<!--                                            </NButton>-->
<!--                                        </NSpace>-->
<!--                                    </template>-->
<!--                                </NThing>-->
<!--                            </NListItem>-->
<!--                        </NScrollbar>-->
<!--                    </NList>-->
<!--                    <NEmpty v-else description="Учетные записи не найдены" class="py-4 pb-8">-->
<!--                        <template #extra>-->
<!--                            <NButton secondary size="small" @click="showAddStaffIntegrate = true">-->
<!--                                Добавить новую запись-->
<!--                            </NButton>-->
<!--                        </template>-->
<!--                    </NEmpty>-->
<!--                </NCard>-->
<!--            </NGi>-->
        </NGrid>
    </AppLayout>
</template>

<style scoped>

</style>
