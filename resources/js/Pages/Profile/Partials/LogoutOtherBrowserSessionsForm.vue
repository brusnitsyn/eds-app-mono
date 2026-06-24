<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { NButton, NCard, NEmpty, NFlex, NIcon, NInput, NList, NListItem, NTag, NText } from 'naive-ui';
import { IconDeviceDesktop, IconDeviceMobile } from '@tabler/icons-vue';
import EdsModal from '@/Components/Eds/EdsModal.vue';

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInputRef = ref(null);

const form = useForm({
    password: '',
});

const confirmLogout = () => {
    confirmingLogout.value = true;

    setTimeout(() => passwordInputRef.value?.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            window.$message?.success('Остальные сеансы завершены');
        },
        onError: () => passwordInputRef.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.reset();
};
</script>

<template>
    <NCard title="Активные сеансы">
        <NText depth="3" class="text-sm mb-4" style="display: block">
            При необходимости вы можете выйти из всех своих активных сеансов на других браузерах и устройствах. Список ниже может быть неполным — если считаете, что учётная запись скомпрометирована, также обновите пароль.
        </NText>

        <NList v-if="sessions.length > 0" bordered>
            <NListItem v-for="(session, i) in sessions" :key="i">
                <NFlex align="center" :size="12" :wrap="false">
                    <NIcon :component="session.agent.is_desktop ? IconDeviceDesktop : IconDeviceMobile" :size="22" :depth="3" />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium">
                            {{ session.agent.platform || 'Неизвестное устройство' }} · {{ session.agent.browser || 'Неизвестный браузер' }}
                        </div>
                        <NFlex align="center" :size="6" class="text-xs text-gray-400 dark:text-white/40">
                            <span>{{ session.ip_address }}</span>
                            <NTag v-if="session.is_current_device" type="success" size="small" round>
                                Это устройство
                            </NTag>
                            <span v-else>· {{ session.last_active }}</span>
                        </NFlex>
                    </div>
                </NFlex>
            </NListItem>
        </NList>
        <NEmpty v-else description="Нет данных об активных сеансах" />

        <template #footer>
            <NButton @click="confirmLogout">
                Завершить другие сеансы
            </NButton>
        </template>
    </NCard>

    <EdsModal v-model:show="confirmingLogout" title="Выход из других сеансов" @after-leave="closeModal">
        <NText depth="3" class="text-sm mb-3" style="display: block">
            Введите пароль, чтобы подтвердить выход из других сеансов браузера на всех ваших устройствах.
        </NText>

        <NInput
            ref="passwordInputRef"
            v-model:value="form.password"
            type="password"
            show-password-on="click"
            placeholder="Пароль"
            autocomplete="current-password"
            @keyup.enter="logoutOtherBrowserSessions"
        />
        <NText v-if="form.errors.password" type="error" class="text-xs mt-1" style="display: block">
            {{ form.errors.password }}
        </NText>

        <NFlex justify="end" class="mt-4">
            <NButton @click="closeModal">
                Отмена
            </NButton>
            <NButton type="primary" :loading="form.processing" :disabled="form.processing" @click="logoutOtherBrowserSessions">
                Завершить сеансы
            </NButton>
        </NFlex>
    </EdsModal>
</template>
