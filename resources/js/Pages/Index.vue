<script setup>
import { NButton } from 'naive-ui'
import AppLayout from "@/Layouts/AppLayout.vue";
</script>

<template>
    <AppLayout title="Главная">
        <template #header>
            Персонал
        </template>
        <template #subheader>
            Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad alias consequatur consequuntur doloremque dolorum eveniet explicabo hic id impedit magnam maxime nemo placeat provident qui quidem, quis quo unde velit.
        </template>
        <div>
            <NButton>
                Кнопка?
            </NButton>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
