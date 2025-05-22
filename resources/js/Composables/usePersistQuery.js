// resources/js/composables/usePersistQuery.ts
import { useStorage, useUrlSearchParams } from '@vueuse/core'
import {router, usePage} from '@inertiajs/vue3'
import { watch } from 'vue'

export function usePersistQuery() {

    // 1. Берём текущие параметры из URL
    const urlParams = useUrlSearchParams('history')

    // 2. Храним ВСЕ параметры в localStorage
    const savedQuery = useStorage('inertia_query_params', {})

    // 3. При изменении URL - сохраняем параметры
    router.on('navigate', (event) => {
        savedQuery.value = { ...savedQuery.value, ...urlParams }
    })

    // 4. При инициализации - подставляем сохранённые параметры
    if (Object.keys(savedQuery.value).length > 0) {
        router.replace(usePage().url, {
            query: { ...savedQuery.value, ...urlParams }
        })
    }

    return { savedQuery }
}
