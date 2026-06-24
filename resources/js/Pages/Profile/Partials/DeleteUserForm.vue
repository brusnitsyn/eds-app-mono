<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { NAlert, NButton, NCard, NFlex, NInput, NText } from 'naive-ui';
import EdsModal from '@/Components/Eds/EdsModal.vue';

const confirmingUserDeletion = ref(false);
const passwordInputRef = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInputRef.value?.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInputRef.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <NCard title="Удаление аккаунта">
        <NAlert type="error" class="mb-4">
            Это действие необратимо. После удаления учётной записи все связанные с ней данные и ресурсы будут удалены безвозвратно.
        </NAlert>
        <NText depth="3" class="text-sm" style="display: block">
            Перед удалением учётной записи сохраните все данные, которые хотите оставить.
        </NText>

        <template #footer>
            <NButton type="error" @click="confirmUserDeletion">
                Удалить аккаунт
            </NButton>
        </template>
    </NCard>

    <EdsModal v-model:show="confirmingUserDeletion" title="Удаление аккаунта" @after-leave="closeModal">
        <NText depth="3" class="text-sm mb-3" style="display: block">
            Вы уверены, что хотите удалить аккаунт? Все данные будут удалены безвозвратно. Введите пароль, чтобы подтвердить удаление.
        </NText>

        <NInput
            ref="passwordInputRef"
            v-model:value="form.password"
            type="password"
            show-password-on="click"
            placeholder="Пароль"
            autocomplete="current-password"
            @keyup.enter="deleteUser"
        />
        <NText v-if="form.errors.password" type="error" class="text-xs mt-1" style="display: block">
            {{ form.errors.password }}
        </NText>

        <NFlex justify="end" class="mt-4">
            <NButton @click="closeModal">
                Отмена
            </NButton>
            <NButton type="error" :loading="form.processing" :disabled="form.processing" @click="deleteUser">
                Удалить аккаунт
            </NButton>
        </NFlex>
    </EdsModal>
</template>
