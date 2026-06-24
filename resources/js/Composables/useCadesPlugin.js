import {execute, isValidSystemSetup, getSystemInfo} from "crypto-pro-actual-cades-plugin"

/**
 * Установка сертификата выполняется браузерным плагином КриптоПро (CAdESCOM)
 * напрямую на компьютере пользователя — сервер в этом не участвует. Плагин
 * может добавить в хранилище Windows только публичный сертификат; перенести
 * сам закрытый ключ через этот API физически невозможно.
 */
export function useCadesPlugin() {
    async function isAvailable() {
        try {
            return await isValidSystemSetup()
        } catch {
            return false
        }
    }

    /**
     * @param {string} base64Content Сертификат в DER, закодированный в base64 (без PEM-заголовков)
     * @param {"Root"|"CA"|"My"} storeName Имя системного хранилища Windows
     */
    function installToStore(base64Content, storeName) {
        return execute(({cadesplugin}) => new Promise((resolve, reject) => {
            cadesplugin.async_spawn(function* () {
                var oCertificate = yield cadesplugin.CreateObjectAsync("CAdESCOM.Certificate")
                yield oCertificate.Import(base64Content)

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
