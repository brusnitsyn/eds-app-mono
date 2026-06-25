<script setup>
import {computed, ref} from "vue"
import {useForm} from "@inertiajs/vue3"
import {NButton, NIcon, NScrollbar} from "naive-ui"
import {IconFolderUp, IconCertificate, IconCircleCheck, IconAlertTriangle, IconX, IconTrash} from "@tabler/icons-vue"

const form = useForm({
    files: [],
    paths: [],
})

const emits = defineEmits(["success"])

// {file: File, path: string} — относительный путь внутри выбранной папки.
const entries = ref([])
const isDragging = ref(false)

const dirInput = ref(null)

// --- Сбор файлов из выбранной папки или перетаскивания ---

function addEntries(newEntries) {
    const seen = new Set(entries.value.map((e) => e.path))
    for (const entry of newEntries) {
        if (entry.path && !seen.has(entry.path)) {
            entries.value.push(entry)
            seen.add(entry.path)
        }
    }
}

function onPick(event) {
    const picked = Array.from(event.target.files ?? []).map((file) => ({
        file,
        path: file.webkitRelativePath || file.name,
    }))
    addEntries(picked)
    event.target.value = ""
}

function walkEntry(entry, out) {
    return new Promise((resolve) => {
        if (entry.isFile) {
            entry.file(
                (file) => {
                    out.push({file, path: entry.fullPath.replace(/^\//, "")})
                    resolve()
                },
                () => resolve()
            )
        } else if (entry.isDirectory) {
            const reader = entry.createReader()
            const readChunk = () => {
                reader.readEntries(
                    async (batch) => {
                        if (!batch.length) return resolve()
                        for (const child of batch) await walkEntry(child, out)
                        readChunk() // readEntries отдаёт записи порциями
                    },
                    () => resolve()
                )
            }
            readChunk()
        } else {
            resolve()
        }
    })
}

async function onDrop(event) {
    isDragging.value = false
    const items = Array.from(event.dataTransfer?.items ?? [])
    const roots = items
        .map((item) => item.webkitGetAsEntry?.())
        .filter(Boolean)

    if (roots.length) {
        const collected = []
        for (const root of roots) await walkEntry(root, collected)
        addEntries(collected)
        return
    }

    // Фолбэк для браузеров без Entries API — простые файлы без структуры.
    const files = Array.from(event.dataTransfer?.files ?? []).map((file) => ({
        file,
        path: file.webkitRelativePath || file.name,
    }))
    addEntries(files)
}

function clearAll() {
    entries.value = []
}

function removePackage(root) {
    entries.value = entries.value.filter((entry) => packageRootOf(entry.path) !== root)
}

// --- Группировка в пакеты (зеркалит серверную логику по .cer) ---

function dirname(path) {
    const idx = path.lastIndexOf("/")
    return idx === -1 ? "" : path.slice(0, idx)
}

function basename(path) {
    const idx = path.lastIndexOf("/")
    return idx === -1 ? path : path.slice(idx + 1)
}

// Корни пакетов — директории файлов .cer, по длине убыванию (приоритет вложенным).
const packageRoots = computed(() => {
    const roots = new Set()
    for (const entry of entries.value) {
        if (entry.path.toLowerCase().endsWith(".cer")) {
            roots.add(dirname(entry.path))
        }
    }
    return Array.from(roots).sort((a, b) => b.length - a.length)
})

function packageRootOf(path) {
    for (const root of packageRoots.value) {
        if (root === "" || path.startsWith(root + "/")) return root
    }
    return null
}

const packages = computed(() => {
    const map = new Map()
    for (const entry of entries.value) {
        const root = packageRootOf(entry.path)
        if (root === null) continue
        if (!map.has(root)) map.set(root, [])
        map.get(root).push(entry)
    }

    return Array.from(map.entries()).map(([root, items]) => {
        const cer = items.find((e) => e.path.toLowerCase().endsWith(".cer"))
        const hasKey = items.some((e) => basename(e.path).toLowerCase() === "header.key")
        return {
            root,
            name: basename(root) || basename(cer?.path ?? "") || "Контейнер",
            fileCount: items.length,
            certName: cer ? basename(cer.path) : null,
            hasKey,
        }
    })
})

// Файлы, не попавшие ни в один пакет (нет рядом .cer).
const orphanCount = computed(
    () => entries.value.filter((e) => packageRootOf(e.path) === null).length
)

const certCount = computed(() => packages.value.length)
const allValid = computed(
    () => certCount.value > 0 && packages.value.every((p) => p.hasKey)
)

// --- Отправка ---

function submit() {
    form.files = entries.value.map((e) => e.file)
    form.paths = entries.value.map((e) => e.path)

    form.post("/staff", {
        forceFormData: true,
        // Без этого Inertia после back()-редиректа пересоздаёт страницу с нуля
        // (новый key у корневого компонента) — модалка теряет состояние шага
        // и выглядит так, будто страница просто перезагрузилась.
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            emits("success")
            clearAll()
        },
    })
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Зона загрузки -->
        <div
            class="relative rounded-xl border-2 border-dashed transition-colors px-6 py-8 text-center cursor-pointer"
            :class="isDragging
                ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10'
                : 'border-gray-200 dark:border-white/15 hover:border-emerald-300 dark:hover:border-emerald-500/40'"
            @click="dirInput?.click()"
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <input
                ref="dirInput"
                type="file"
                class="hidden"
                webkitdirectory
                directory
                multiple
                @change="onPick"
            />
            <div class="flex flex-col items-center gap-2 pointer-events-none">
                <span class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                    <NIcon :component="IconFolderUp" :size="26" />
                </span>
                <div class="text-sm font-semibold">
                    Перетащите папки сертификатов сюда
                </div>
                <div class="text-xs text-gray-400 dark:text-white/40 max-w-sm">
                    Или нажмите, чтобы выбрать папку. Загружайте папку с файлом сертификата (.cer)
                    и контейнером закрытого ключа КриптоПро как есть — архив собирать не нужно.
                </div>
            </div>
        </div>

        <!-- Превью обнаруженных сертификатов -->
        <div v-if="entries.length" class="flex flex-col gap-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium" :class="allValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                    Обнаружено сертификатов: {{ certCount }}
                </span>
                <button class="text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1" @click="clearAll">
                    <NIcon :component="IconTrash" :size="14" />
                    Очистить
                </button>
            </div>

            <NScrollbar style="max-height: 180px">
                <div class="flex flex-col gap-2 pr-1">
                    <div
                        v-for="pkg in packages"
                        :key="pkg.root"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg border"
                        :class="pkg.hasKey
                            ? 'border-gray-200 dark:border-white/10'
                            : 'border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10'"
                    >
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg flex-none bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-white/50">
                            <NIcon :component="IconCertificate" :size="18" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ pkg.name }}</div>
                            <div class="text-xs text-gray-400 dark:text-white/40 flex items-center gap-1.5">
                                <template v-if="pkg.hasKey">
                                    <NIcon :component="IconCircleCheck" :size="13" class="text-emerald-500" />
                                    {{ pkg.certName }} · файлов: {{ pkg.fileCount }}
                                </template>
                                <template v-else>
                                    <NIcon :component="IconAlertTriangle" :size="13" class="text-amber-500" />
                                    Не найден контейнер ключа (header.key)
                                </template>
                            </div>
                        </div>
                        <button class="text-gray-300 hover:text-red-500 transition-colors flex-none" @click="removePackage(pkg.root)">
                            <NIcon :component="IconX" :size="16" />
                        </button>
                    </div>

                    <div v-if="orphanCount" class="text-xs text-gray-400 dark:text-white/40 px-1">
                        Файлов без сертификата (.cer) рядом: {{ orphanCount }} — будут пропущены.
                    </div>
                </div>
            </NScrollbar>
        </div>

        <div class="flex justify-end pt-1">
            <NButton
                type="primary"
                :loading="form.processing"
                :disabled="certCount === 0"
                @click="submit"
            >
                Загрузить {{ certCount > 1 ? `(${certCount})` : "" }}
            </NButton>
        </div>
    </div>
</template>
