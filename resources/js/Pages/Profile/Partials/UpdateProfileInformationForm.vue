<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { NAvatar, NButton, NCard, NFlex, NForm, NFormItemGi, NGrid, NIcon, NInput, NTag, NText, NUpload } from 'naive-ui';
import { IconCamera } from '@tabler/icons-vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const photoPreview = ref(null);

const updateProfileInformation = () => {
    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            window.$message?.success('Профиль обновлён');
        },
    });
};

const onPhotoChange = ({ fileList }) => {
    const file = fileList[fileList.length - 1]?.file;

    if (! file) return;

    form.photo = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            form.photo = null;
        },
    });
};
</script>

<template>
    <NCard>
        <NFlex :size="20" align="start" :wrap="false">
            <div class="flex-none text-center">
                <NUpload
                    v-if="$page.props.jetstream.managesProfilePhotos"
                    :max="1"
                    :default-upload="false"
                    :show-file-list="false"
                    accept="image/png,image/jpeg"
                    @change="onPhotoChange"
                >
                    <div class="relative cursor-pointer group">
                        <NAvatar :size="84" round :src="photoPreview || user.profile_photo_url" :object-fit="'cover'" />
                        <div class="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/40 flex items-center justify-center transition-colors">
                            <NIcon :component="IconCamera" :size="20" class="text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    </div>
                </NUpload>
                <NAvatar v-else :size="84" round :src="user.profile_photo_url" :object-fit="'cover'" />

                <div v-if="form.errors.photo" class="text-xs text-red-500 mt-2 max-w-[84px]">
                    {{ form.errors.photo }}
                </div>

                <NButton
                    v-if="$page.props.jetstream.managesProfilePhotos && user.profile_photo_path"
                    text
                    type="error"
                    size="tiny"
                    class="mt-2"
                    @click.prevent="deletePhoto"
                >
                    Удалить фото
                </NButton>
            </div>

            <div class="flex-1 min-w-0">
                <NFlex align="center" :size="8" class="mb-4">
                    <NText class="text-base font-semibold">
                        {{ user.name }}
                    </NText>
                    <NTag v-if="user.role?.name" size="small" round type="primary">
                        {{ user.role.name }}
                    </NTag>
                </NFlex>

                <NForm label-placement="top" :model="form" @submit.prevent="updateProfileInformation">
                    <NGrid cols="1 m:2" :x-gap="16" responsive="screen">
                        <NFormItemGi label="Имя" :feedback="form.errors.name" :validation-status="form.errors.name ? 'error' : undefined">
                            <NInput id="name" v-model:value="form.name" type="text" autocomplete="name" />
                        </NFormItemGi>

                        <NFormItemGi label="Email" :feedback="form.errors.email" :validation-status="form.errors.email ? 'error' : undefined">
                            <NInput id="email" v-model:value="form.email" type="text" autocomplete="email" />
                        </NFormItemGi>
                    </NGrid>
                </NForm>

                <NText depth="3" class="text-xs">
                    Логин для входа: {{ user.login }}
                </NText>
            </div>
        </NFlex>

        <template #footer>
            <NFlex justify="end">
                <NButton type="primary" attr-type="submit" :loading="form.processing" :disabled="form.processing" @click="updateProfileInformation">
                    Сохранить
                </NButton>
            </NFlex>
        </template>
    </NCard>
</template>
