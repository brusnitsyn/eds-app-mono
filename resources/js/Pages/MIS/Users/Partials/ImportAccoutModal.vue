<script setup lang="ts">
import {router, useForm} from "@inertiajs/vue3";
import {NTabs, NTabPane, NForm, NFormItem, NInput, NUpload, NUploadDragger, NIcon, NText, NCheckbox, NFlex, NSpace, NButton} from 'naive-ui'
import {IconFileTypeXls} from '@tabler/icons-vue'
import EdsModal from "@/Components/Eds/EdsModal.vue";

const show = defineModel('show')
const form = useForm({
    file: null,
})

function handleUpload() {
    form.submit('post', route('mis.import.doctors'), {
        onSuccess: () => {
            show.value = false
            router.reload()
        }
    });
}
</script>

<template>
    <EdsModal v-model:show="show" title="Импорт учетных записей">
        <NFlex vertical justify="space-between" class="h-[253px]">
            <NSpace vertical>
                <NUpload
                    directory-dnd
                    :default-upload="false"
                    :max="1"
                    @change="(data) => form.file = data.fileList[0].file"
                >
                    <NUploadDragger>
                        <div style="margin-bottom: 12px">
                            <NIcon size="48" :depth="5">
                                <IconFileTypeXls />
                            </NIcon>
                        </div>
                        <NText style="font-size: 16px">
                            Нажмите или перетащите Excel-файл в эту область, чтобы загрузить
                        </NText>
                    </NUploadDragger>
                </NUpload>
            </NSpace>
            <NFlex justify="space-between" align="center">
                <NButton type="primary" @click="handleUpload" :loading="form.processing" :disabled="form.file === null">Импортировать</NButton>
            </NFlex>
        </NFlex>
    </EdsModal>
</template>

<style scoped>

</style>
