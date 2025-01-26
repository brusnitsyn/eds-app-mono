import { usePage } from '@inertiajs/vue3';

export function useCheckScope() {
    const { props } = usePage()

    const hasScope = (scope) => {
        return props.user && props.user.scopes.includes(scope)
    }

    return {
        hasScope
    }
}
