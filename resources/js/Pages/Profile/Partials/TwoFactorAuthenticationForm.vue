<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { NAlert, NButton, NCard, NFlex, NIcon, NInput, NTag, NText } from 'naive-ui';
import { IconCopy } from '@tabler/icons-vue';
import EdsModal from '@/Components/Eds/EdsModal.vue';

const props = defineProps({
    requiresConfirmation: Boolean,
});

const page = usePage();
const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => ! enabling.value && page.props.auth.user?.two_factor_enabled,
);

watch(twoFactorEnabled, () => {
    if (! twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

// Перед чувствительными действиями (включение/отключение 2ФА, генерация кодов
// восстановления) проверяем, не подтверждён ли пароль уже в этой сессии —
// и только если нет, показываем модалку подтверждения.
const passwordModal = ref(false);
const passwordInputRef = ref(null);
const passwordForm = reactive({
    password: '',
    error: '',
    processing: false,
});
let pendingAction = null;

const withPasswordConfirmation = (action) => {
    axios.get(route('password.confirmation')).then(({ data }) => {
        if (data.confirmed) {
            action();
            return;
        }

        pendingAction = action;
        passwordModal.value = true;
        setTimeout(() => passwordInputRef.value?.focus(), 250);
    });
};

const confirmPassword = () => {
    passwordForm.processing = true;

    axios.post(route('password.confirm'), {
        password: passwordForm.password,
    }).then(() => {
        passwordModal.value = false;

        const action = pendingAction;
        pendingAction = null;
        action?.();
    }).catch((error) => {
        passwordForm.error = error.response?.data?.errors?.password?.[0];
        passwordInputRef.value?.focus();
    }).finally(() => {
        passwordForm.processing = false;
    });
};

const closePasswordModal = () => {
    passwordModal.value = false;
    passwordForm.password = '';
    passwordForm.error = '';
    pendingAction = null;
};

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(route('two-factor.enable'), {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const showQrCode = () => {
    return axios.get(route('two-factor.qr-code')).then(response => {
        qrCode.value = response.data.svg;
    });
};

const showSetupKey = () => {
    return axios.get(route('two-factor.secret-key')).then(response => {
        setupKey.value = response.data.secretKey;
    });
};

const showRecoveryCodes = () => {
    return axios.get(route('two-factor.recovery-codes')).then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route('two-factor.confirm'), {
        errorBag: 'confirmTwoFactorAuthentication',
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
            window.$message?.success('Двухфакторная аутентификация подключена');
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route('two-factor.recovery-codes'))
        .then(() => {
            showRecoveryCodes();
            window.$message?.success('Коды восстановления обновлены');
        });
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
            window.$message?.success('Двухфакторная аутентификация отключена');
        },
    });
};

const { copy } = useClipboard();

const copySetupKey = () => {
    copy(setupKey.value);
    window.$message?.success('Ключ скопирован');
};
</script>

<template>
    <NCard title="Двухфакторная аутентификация">
        <template #header-extra>
            <NTag :type="twoFactorEnabled && ! confirming ? 'success' : 'default'" round size="small">
                {{ twoFactorEnabled && ! confirming ? 'Включена' : 'Отключена' }}
            </NTag>
        </template>

        <NText depth="3" class="text-sm" style="display: block">
            При включении двухфакторной аутентификации во время входа потребуется ввести безопасный случайный код. Этот код можно получить в приложении-аутентификаторе на телефоне.
        </NText>

        <template v-if="twoFactorEnabled">
            <div v-if="qrCode" class="mt-4">
                <NAlert :type="confirming ? 'warning' : 'success'" :show-icon="false" class="mb-4">
                    <template v-if="confirming">
                        Чтобы закончить включение двухфакторной аутентификации, отсканируйте QR-код приложением-аутентификатором или введите ключ настройки, затем укажите сгенерированный код.
                    </template>
                    <template v-else>
                        Двухфакторная аутентификация включена. QR-код и ключ настройки можно использовать для подключения дополнительных устройств.
                    </template>
                </NAlert>

                <div class="inline-block p-3 bg-white rounded-lg border dark:border-white/10" v-html="qrCode" />

                <div v-if="setupKey" class="mt-3 text-sm">
                    <NFlex align="center" :size="6">
                        <NText depth="3">Ключ настройки:</NText>
                        <NText code>{{ setupKey }}</NText>
                        <NButton text size="tiny" @click="copySetupKey">
                            <template #icon>
                                <NIcon :component="IconCopy" />
                            </template>
                        </NButton>
                    </NFlex>
                </div>

                <div v-if="confirming" class="mt-4 max-w-xs">
                    <NInput
                        v-model:value="confirmationForm.code"
                        placeholder="Код из приложения"
                        inputmode="numeric"
                        autofocus
                        autocomplete="one-time-code"
                        @keyup.enter="confirmTwoFactorAuthentication"
                    />
                    <NText v-if="confirmationForm.errors.code" type="error" class="text-xs mt-1" style="display: block">
                        {{ confirmationForm.errors.code }}
                    </NText>
                </div>
            </div>

            <div v-if="recoveryCodes.length > 0 && ! confirming" class="mt-4">
                <NAlert type="info" :show-icon="false" class="mb-3">
                    Сохраните эти коды восстановления в надёжном менеджере паролей. Они помогут восстановить доступ к аккаунту, если устройство аутентификации будет потеряно.
                </NAlert>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 p-4 rounded-lg bg-gray-100 dark:bg-white/5 font-mono text-sm">
                    <div v-for="code in recoveryCodes" :key="code">
                        {{ code }}
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <NFlex>
                <NButton v-if="! twoFactorEnabled" type="primary" :loading="enabling" :disabled="enabling" @click="withPasswordConfirmation(enableTwoFactorAuthentication)">
                    Включить
                </NButton>

                <template v-else>
                    <NButton v-if="confirming" type="primary" :disabled="enabling" @click="withPasswordConfirmation(confirmTwoFactorAuthentication)">
                        Подтвердить
                    </NButton>

                    <NButton v-if="recoveryCodes.length > 0 && ! confirming" @click="withPasswordConfirmation(regenerateRecoveryCodes)">
                        Сгенерировать новые коды
                    </NButton>

                    <NButton v-if="recoveryCodes.length === 0 && ! confirming" @click="withPasswordConfirmation(showRecoveryCodes)">
                        Показать коды восстановления
                    </NButton>

                    <NButton v-if="confirming" :disabled="disabling" @click="withPasswordConfirmation(disableTwoFactorAuthentication)">
                        Отмена
                    </NButton>

                    <NButton v-if="! confirming" type="error" secondary :loading="disabling" :disabled="disabling" @click="withPasswordConfirmation(disableTwoFactorAuthentication)">
                        Отключить
                    </NButton>
                </template>
            </NFlex>
        </template>
    </NCard>

    <EdsModal v-model:show="passwordModal" title="Подтверждение пароля" @after-leave="closePasswordModal">
        <NText depth="3" class="text-sm mb-3" style="display: block">
            Для безопасности подтвердите пароль, чтобы продолжить.
        </NText>

        <NInput
            ref="passwordInputRef"
            v-model:value="passwordForm.password"
            type="password"
            show-password-on="click"
            placeholder="Пароль"
            autocomplete="current-password"
            @keyup.enter="confirmPassword"
        />
        <NText v-if="passwordForm.error" type="error" class="text-xs mt-1" style="display: block">
            {{ passwordForm.error }}
        </NText>

        <NFlex justify="end" class="mt-4">
            <NButton @click="closePasswordModal">
                Отмена
            </NButton>
            <NButton type="primary" :loading="passwordForm.processing" :disabled="passwordForm.processing" @click="confirmPassword">
                Подтвердить
            </NButton>
        </NFlex>
    </EdsModal>
</template>
