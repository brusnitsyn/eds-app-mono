<script setup>
import { computed } from 'vue';
import {Link, router, usePage} from '@inertiajs/vue3';
import { NBreadcrumb, NBreadcrumbItem } from 'naive-ui';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
});

// Получаем текущие query-параметры
const currentQuery = computed(() => usePage().props.ziggy.query || {});

// Формируем крошки с сохранением query
const crumbs = computed(() => {
    return props.items.map(item => ({
        ...item,
        query: { ...currentQuery.value, ...(item.query || {}) }
    }));
});

// Навигация с сохранением query
const navigate = (crumb) => {
    if (!crumb.href) return;

    router.get(crumb.href, crumb.query, {
        preserveState: true,
        preserveScroll: true,
        replace: false
    });
};

const isNotOnlyLast = computed(() => props.items.filter(itm => itm.isLast === false).length > 0)
</script>

<template>
    <NBreadcrumb>
        <template v-for="(crumb, index) in crumbs" :key="index">
            <NBreadcrumbItem v-if="!crumb.isLast"
                             :clickable="!!crumb.href"
                             :separator="crumb.isPreLast ? '' : '/'"
                             :href="crumb.href">
                {{ crumb.label }}
            </NBreadcrumbItem>
        </template>
        <NBreadcrumbItem v-if="isNotOnlyLast" />
    </NBreadcrumb>
</template>
