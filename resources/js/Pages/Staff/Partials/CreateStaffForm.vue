<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {NTabs, NTabPane, NForm, NFormItem, NInput, NUpload, NUploadDragger, NIcon, NText, NCheckbox, NFlex, NSpace, NButton} from 'naive-ui'
import {IconArchive} from '@tabler/icons-vue'

const form = useForm({
    certificate: null,
    is_package: false
})

const emits = defineEmits('success')

function handleUpload() {
    form.post('/staff', {
        onSuccess: () => emits('success')
    });
}
</script>

<template>
    <NFlex vertical justify="space-between" class="h-[253px]">
        <NSpace vertical>
            <NUpload
                directory-dnd
                :default-upload="false"
                :max="1"
                @change="(data: { fileList }) => form.certificate = data.fileList[0].file"
            >
                <NUploadDragger>
                    <div style="margin-bottom: 12px">
                        <NIcon size="48" :depth="3">
                            <IconArchive />
                        </NIcon>
                    </div>
                    <NText style="font-size: 16px">
                        Нажмите или перетащите архив в эту область, чтобы загрузить
                    </NText>
                </NUploadDragger>
            </NUpload>
        </NSpace>
        <NFlex justify="space-between" align="center">
            <NCheckbox v-model:checked="form.is_package" label="Архив содержит несколько сертификатов" />
            <NButton type="primary" @click="handleUpload" :loading="form.processing" :disabled="form.certificate === null">Загрузить</NButton>
        </NFlex>
    </NFlex>
</template>

<style scoped>

</style>
