import type { Plugin } from "vue"
import { AccessScopesEnum } from "./auth.constants"
import { checkHasScope } from "./auth.service"

declare module "@vue/runtime-core" {
    interface ComponentCustomProperties {
        $scopes: typeof AccessScopesEnum
        $checkHasScope(scopes: AccessScopesEnum[]): boolean
    }
}

export const AuthPlugin: Plugin = {
    install(app) {
        app.config.globalProperties.$scopes = AccessScopesEnum
        // app.config.globalProperties.$checkHasScope = checkHasScope
    },
};

