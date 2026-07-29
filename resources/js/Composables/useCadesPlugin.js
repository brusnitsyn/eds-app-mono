import {execute, getSystemInfo as _cadesGetSystemInfo} from "crypto-pro-actual-cades-plugin"

/**
 * Установка сертификата выполняется браузерным плагином КриптоПро (CAdESCOM)
 * напрямую на компьютере пользователя — сервер в этом не участвует. Плагин
 * может добавить в хранилище Windows только публичный сертификат; перенести
 * сам закрытый ключ через этот API физически невозможно.
 */
export function useCadesPlugin() {
    /**
     * Проверяет доступность плагина через реальный вызов CAdESCOM.Certificate.
     * MV3 service worker может не успеть стартовать к первому вызову,
     * поэтому при неудаче делается повторная попытка через 2 секунды.
     */
    async function isAvailable() {
        for (let attempt = 0; attempt < 3; attempt++) {
            if (attempt > 0) await new Promise(r => setTimeout(r, 2000))
            try {
                await execute(({cadesplugin}) => new Promise((resolve, reject) => {
                    cadesplugin.async_spawn(function* () {
                        yield cadesplugin.CreateObjectAsync('CAdESCOM.Certificate')
                        resolve()
                    }, resolve, reject)
                }))
                return true
            } catch {}
        }
        return false
    }

    /**
     * Возвращает версию плагина и CSP через штатный API пакета.
     */
    async function getSystemInfo() {
        try {
            const info = await _cadesGetSystemInfo()
            return {
                pluginVersion: info?.cadesVersion ?? null,
                cspVersion: info?.cspVersion ?? null,
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
                try {
                    // Создаём и импортируем сертификат
                    const oCertificate = yield cadesplugin.CreateObjectAsync("CAdESCOM.Certificate");
                    yield oCertificate.Import(clean);

                    // Открываем хранилище
                    const oStore = yield cadesplugin.CreateObjectAsync("CAdESCOM.Store");
                    yield oStore.Open(
                        cadesplugin.CAPICOM_CURRENT_USER_STORE,
                        storeName,
                        cadesplugin.CAPICOM_STORE_OPEN_READ_WRITE
                    );

                    // Проверяем, есть ли уже такой сертификат (по отпечатку)
                    const certs = yield oStore.Certificates; // коллекция
                    let exists = false;
                    const count = yield certs.Count;
                    for (let i = 1; i <= count; i++) {
                        const cert = yield certs.Item(i);
                        const certThumbprint = yield oCertificate.Thumbprint
                        const installedThumbprint = yield cert.Thumbprint
                        if (certThumbprint === installedThumbprint) {
                            exists = true;
                            break;
                        }
                    }

                    // Добавляем, только если отсутствует
                    if (!exists) {
                        yield oStore.Add(oCertificate);
                        console.log('Сертификат добавлен в ' + storeName);
                    } else {
                        console.log('Сертификат уже есть в ' + storeName + ', пропускаем');
                    }

                    yield oStore.Close();
                    resolve();
                } catch (e) {
                    console.error('Ошибка:', e.message);
                    reject(e);
                }
            }, resolve, reject);
        }))
    }

    return {isAvailable, getSystemInfo, installToStore}
}
