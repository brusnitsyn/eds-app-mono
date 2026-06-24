<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { NButton, NCard, NFlex, NForm, NFormItem, NInput, NText } from 'naive-ui';

const passwordInputRef = ref(null);
const currentPasswordInputRef = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            window.$message?.success('Пароль обновлён');
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInputRef.value?.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInputRef.value?.focus();
            }
        },
    });
};
</script>

<template>
    <NCard title="Пароль">
        <NText depth="3" class="text-sm mb-4" style="display: block">
            Используйте длинный, случайный пароль, чтобы обеспечить безопасность вашей учётной записи.
        </NText>

        <NForm label-placement="top" :model="form" class="max-w-sm" @submit.prevent="updatePassword">
            <NFlex vertical :size="0">
                <NFormItem label="Текущий пароль" :feedback="form.errors.current_password" :validation-status="form.errors.current_password ? 'error' : undefined">
                    <NInput
                        ref="currentPasswordInputRef"
                        v-model:value="form.current_password"
                        type="password"
                        show-password-on="click"
                        autocomplete="current-password"
                    />
                </NFormItem>

                <NFormItem label="Новый пароль" :feedback="form.errors.password" :validation-status="form.errors.password ? 'error' : undefined">
                    <NInput
                        ref="passwordInputRef"
                        v-model:value="form.password"
                        type="password"
                        show-password-on="click"
                        autocomplete="new-password"
                    />
                </NFormItem>

                <NFormItem label="Подтверждение пароля" :feedback="form.errors.password_confirmation" :validation-status="form.errors.password_confirmation ? 'error' : undefined">
                    <NInput
                        v-model:value="form.password_confirmation"
                        type="password"
                        show-password-on="click"
                        autocomplete="new-password"
                        @keyup.enter="updatePassword"
                    />
                </NFormItem>
            </NFlex>
        </NForm>

        <template #footer>
            <NFlex justify="end">
                <NButton type="primary" attr-type="submit" :loading="form.processing" :disabled="form.processing" @click="updatePassword">
                    Сохранить
                </NButton>
            </NFlex>
        </template>
    </NCard>
</template>
