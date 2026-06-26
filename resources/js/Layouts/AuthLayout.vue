<script setup>
import {Head} from '@inertiajs/vue3'
import Banner from '@/Components/Banner.vue'
import NaiveLayout from "@/Layouts/NaiveLayout.vue"
import { useI18n } from 'vue-i18n'
</script>

<template>
    <Head :title="activeTitle" />
    <NaiveLayout>
        <Banner />

        <div class="h-screen max-h-screen relative overflow-hidden">
            <NLayout position="absolute">
                <NLayout position="absolute">
                    <NLayout>
                        <main>
                            <NMessageProvider>
                                <slot />
                            </NMessageProvider>
                        </main>
                    </NLayout>
                </NLayout>
            </NLayout>
        </div>
    </NaiveLayout>
</template>

<style>

</style>
