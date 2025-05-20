import {useI18n} from "vue-i18n";
import {router} from "@inertiajs/vue3";

export function generateBreadcrumbs(componentPath, query) {
    // Удаляем возможные динамические параметры (:id)
    const cleanPath = componentPath.replace(/\?.*$/, '');

    // Разбиваем путь на части
    const parts = cleanPath.split('/').filter(Boolean);

    // Генерируем breadcrumbs
    return parts.map((part, index) => {
        const currentParts = parts.slice(0, index + 1);
        const path = '/' + currentParts.join('/');

        // Формируем ключ перевода в вашем формате
        const i18nKey = currentParts.join('.').toLowerCase();
        const translation = useI18n().t(`pages.${i18nKey}`);

        // Для динамических сегментов (если остались)
        const isDynamic = part.startsWith(':');
        const displayText = (translation || part.replace(/-/g, ' '));

        return {
            label: displayText,
            key: i18nKey,
            href: index === parts.length - 1 ? null : path.toLowerCase(),
            isLast: index === parts.length - 1,
            isPreLast: index === parts.length - 2,
        };
    });
}
