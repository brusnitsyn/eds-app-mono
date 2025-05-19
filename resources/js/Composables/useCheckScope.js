import { usePage } from '@inertiajs/vue3';
import {Roles, Scopes} from "@/auth.constants.js";

export function useCheckScope() {
    const { props } = usePage()
    const hasScope = (scope) => {
        if (!props.auth.user)
            return false

        if (props.auth.user.role.scopes.find(itm => itm.name === scope) === undefined)
            return false

        return props.auth.user.role.scopes.find(itm => itm.name === scope)
    }

    const hasRole = (role) => {
        if (!props.auth.user)
            return false

        return props.auth.user.role.slug === role
    }

    const scopes = Scopes

    const roles = Roles

    return {
        hasScope,
        hasRole,
        scopes,
        roles
    }
}
