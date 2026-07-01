import {execute, getSystemInfo as _cadesGetSystemInfo} from "crypto-pro-actual-cades-plugin"

/**
 * Установка сертификата выполняется браузерным плагином КриптоПро (CAdESCOM)
 * напрямую на компьютере пользователя — сервер в этом не участвует. Плагин
 * может добавить в хранилище Windows только публичный сертификат; перенести
 * сам закрытый ключ через этот API физически невозможно.
 */
export function useCadesPlugin() {
    /**
     * Проверяет доступность плагина через реальный вызов CAdESCOM.About.
     * Надёжнее isValidSystemSetup — работает с MV2 и MV3 расширениями.
     */
    async function isAvailable() {
        try {
            await execute(({cadesplugin}) => new Promise((resolve, reject) => {
                cadesplugin.async_spawn(function* () {
                    yield cadesplugin.CreateObjectAsync('CAdESCOM.About')
                    resolve()
                }, resolve, reject)
            }))
            return true
        } catch {
            return false
        }
    }

    /**
     * Возвращает версию плагина и CSP через штатный API пакета.
     */
    async function getSystemInfo() {
        try {
            const info = await _cadesGetSystemInfo()
            return {
                pluginVersion: info?.pluginVersion ?? info?.PluginVersion ?? null,
                cspVersion: info?.cspVersion ?? info?.CspVersion ?? info?.CSPVersion ?? null,
            }
        } catch {
            return null
        }
    }

    /**
     * @param {string} base64Content Сертификат в DER, закодированный в base64 (без PEM-заголовков)
     * @param {"Root"|"CA"|"My"} storeName Имя системного хранилища Windows
     */
    function installToStore(base64Content, storeName) {
        // Убираем PEM-заголовки и все пробельные символы — CAdESCOM.Certificate.Import
        // требует чистый base64 без переносов строк и без заголовков.
        const clean = base64Content
            .replace(/-----BEGIN CERTIFICATE-----/g, '')
            .replace(/-----END CERTIFICATE-----/g, '')
            .replace(/\s+/g, '')

        return execute(({cadesplugin}) => new Promise((resolve, reject) => {
            cadesplugin.async_spawn(function* () {
                var oCertificate = yield cadesplugin.CreateObjectAsync("CAdESCOM.Certificate")
                yield oCertificate.Import(clean)

                var oStore = yield cadesplugin.CreateObjectAsync("CAdESCOM.Store")
                yield oStore.Open(
                    cadesplugin.CAPICOM_CURRENT_USER_STORE,
                    storeName,
                    cadesplugin.CAPICOM_STORE_OPEN_READ_WRITE
                )

                try {
                    yield oStore.Add(oCertificate)
                } finally {
                    yield oStore.Close()
                }
            }, resolve, reject)
        }))
    }

    return {isAvailable, getSystemInfo, installToStore}
}
