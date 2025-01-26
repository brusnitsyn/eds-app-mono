<script setup>
import {h} from "vue";
import {Link} from "@inertiajs/vue3";
const props = defineProps({
    roles: {
        type: Array
    }
})
const columns = ref([
    {
        title: 'ID',
        key: 'id',
        width: 50,
    },
    {
        title: 'Наименование роли',
        key: 'name',
        width: 280,
        sorter: 'default',
        sortOrder: false,
        // render(row) {
        //     return h(
        //         Link,
        //         {
        //             href: `/staff/${row.id}`,
        //         },
        //         {
        //             default: () => row.full_name,
        //         }
        //     )
        // }
    },
])
</script>

<template>
    <NDataTable :columns="columns" :data="roles" />
</template>

<style scoped>

</style>
