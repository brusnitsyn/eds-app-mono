import {useStorage} from "@vueuse/core"
import {computed, watch} from "vue"
import {darkTheme} from "naive-ui"

const themePreference = useStorage("app-theme", "light")

// Tailwind's `dark:` variants only activate inside an ancestor with a `.dark`
// class. NModal/NDrawer teleport their content to <body>, outside any class we
// bind in a component template, so the toggle has to live on <html> itself.
watch(themePreference, (value) => {
    document.documentElement.classList.toggle("dark", value === "dark")
}, {immediate: true})

export function useAppTheme() {
    const theme = computed(() => themePreference.value === "dark" ? darkTheme : null)
    const isDark = computed(() => themePreference.value === "dark")

    const toggleTheme = () => {
        themePreference.value = themePreference.value === "dark" ? "light" : "dark"
    }

    return {themePreference, theme, isDark, toggleTheme}
}
