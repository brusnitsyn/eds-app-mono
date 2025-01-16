<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import NaiveLayout from "@/Layouts/NaiveLayout.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    login: '',
    password: '',
    remember: true,
});
const formRef = ref()
const defaultMessage = 'Это поле обязательно'
const rules = {
    login: [
        {
            required: true,
            message: defaultMessage,
            trigger: ['blur', 'input']
        }
    ],
    password: [
        {
            required: true,
            message: defaultMessage,
            trigger: ['blur', 'input'],
        }
    ]
}
function submit(e) {
    e.preventDefault()
    formRef.value?.validate(
        (errors) => {
            if (!errors) {
                form.transform(data => ({
                    ...data,
                    remember: form.remember ? 'on' : '',
                })).post(route('login'), {
                    onFinish: () => {
                        if (form.hasErrors) window.$message.error(form.errors.password || form.errors.login)
                        form.reset('password')
                    },
                });
            }
            else {
                console.log(errors)
            }
        }
    )
}
</script>

<template>
    <Head title="Авторизация" />

    <NaiveLayout message-placement="bottom">
        <AuthenticationCard>
            <template #logo>
                <NImage src="/assets/svg/logo.svg" width="82" height="82" preview-disabled />
            </template>
            <NForm @submit.prevent="submit" :model="form" :rules="rules" ref="formRef">
                <NFormItem label="Имя пользователя" path="login">
                    <NInput v-model:value="form.login" placeholder="" />
                </NFormItem>
                <NFormItem label="Пароль" path="password">
                    <NInput type="password" v-model:value="form.password" show-password-on="click" placeholder="" />
                </NFormItem>
                <NButton type="primary" block :loading="form.processing" :disabled="form.processing" attr-type="submit">
                    Войти
                </NButton>
            </NForm>
        </AuthenticationCard>
    </NaiveLayout>
</template>
