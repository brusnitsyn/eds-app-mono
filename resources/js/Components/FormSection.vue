<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <NForm @submit.prevent="$emit('submitted')">
                <NCard>
                    <NGrid cols="10">
                        <slot name="form" />
                    </NGrid>
                    <template v-if="hasActions" #action>
                        <slot name="actions" />
                    </template>
                </NCard>
            </NForm>
        </div>
    </div>
</template>
