<script setup>
import { onMounted, ref } from 'vue'
import Login from './components/Login.vue'
import LinkBlocks from './components/LinkBlocks.vue'
import Links from './components/Links.vue'

const user = ref(null)
const loading = ref(true)
const menuOpen = ref(false)
const loggingOut = ref(false)

const backgroundLoading = ref(false)
const backgroundError = ref('')

const BACKGROUND_CACHE_KEY = 'linkbox-background-cache'

const getCsrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
}

const readBackgroundCache = () => {
    try {
        const raw = localStorage.getItem(BACKGROUND_CACHE_KEY)

        return raw ? JSON.parse(raw) : null
    } catch (e) {
        return null
    }
}

const writeBackgroundCache = (url, dataUrl) => {
    try {
        localStorage.setItem(
            BACKGROUND_CACHE_KEY,
            JSON.stringify({ url, dataUrl })
        )
    } catch (e) {
        // Хранилище переполнено или недоступно — просто
        // не кешируем, картинка всё равно применится.
    }
}

const clearBackgroundCache = () => {
    try {
        localStorage.removeItem(BACKGROUND_CACHE_KEY)
    } catch (e) {
        // ignore
    }
}

const applyBackground = (dataUrl) => {
    if (dataUrl) {
        document.body.style.backgroundImage = `url(${dataUrl})`
        document.body.style.backgroundSize = 'cover'
        document.body.style.backgroundPosition = 'center'
        document.body.style.backgroundAttachment = 'fixed'
        document.body.style.backgroundRepeat = 'no-repeat'
    } else {
        document.body.style.backgroundImage = ''
        document.body.style.backgroundSize = ''
        document.body.style.backgroundPosition = ''
        document.body.style.backgroundAttachment = ''
        document.body.style.backgroundRepeat = ''
    }
}

const fileToDataUrl = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader()

        reader.onload = () => resolve(reader.result)
        reader.onerror = () => reject(reader.error)

        reader.readAsDataURL(file)
    })
}

const fetchAndCacheBackground = async (url) => {
    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
        })

        if (!response.ok) {
            return
        }

        const blob = await response.blob()
        const dataUrl = await fileToDataUrl(blob)

        writeBackgroundCache(url, dataUrl)
        applyBackground(dataUrl)
    } catch (e) {
        console.error(e)
    }
}

const syncBackground = () => {
    const url = user.value?.background_url

    if (!url) {
        clearBackgroundCache()
        applyBackground(null)
        return
    }

    const cached = readBackgroundCache()

    if (cached && cached.url === url) {
        applyBackground(cached.dataUrl)
        return
    }

    // Кеша нет или картинка сменилась — грузим один раз
    // и кешируем в браузере на будущее.
    fetchAndCacheBackground(url)
}

const onBackgroundFileChange = async (event) => {
    const file = event.target.files[0]

    event.target.value = ''

    if (!file) {
        return
    }

    backgroundError.value = ''
    backgroundLoading.value = true

    try {
        const formData = new FormData()

        formData.append('image', file)

        const response = await fetch('/api/user/background', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: formData,
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Не удалось загрузить картинку')
        }

        user.value = data

        const dataUrl = await fileToDataUrl(file)

        writeBackgroundCache(data.background_url, dataUrl)
        applyBackground(dataUrl)
    } catch (e) {
        backgroundError.value = e.message || 'Произошла ошибка'
    } finally {
        backgroundLoading.value = false
    }
}

const removeBackground = async () => {
    backgroundError.value = ''
    backgroundLoading.value = true

    try {
        const response = await fetch('/api/user/background', {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Не удалось удалить картинку')
        }

        user.value = data

        clearBackgroundCache()
        applyBackground(null)
    } catch (e) {
        backgroundError.value = e.message || 'Произошла ошибка'
    } finally {
        backgroundLoading.value = false
    }
}

const loadUser = async () => {
    try {
        const response = await fetch('/auth/user', {
            headers: {
                Accept: 'application/json',
            },
        })

        if (response.ok) {
            user.value = await response.json()
            syncBackground()
        }
    } finally {
        loading.value = false
    }
}

const logout = async () => {
    loggingOut.value = true

    try {
        await fetch('/auth/logout', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        })
    } finally {
        window.location.reload()
    }
}

onMounted(loadUser)
</script>

<template>
    <div v-if="loading">
        Загрузка...
    </div>

    <Login v-else-if="!user" />

    <template v-else>
        <button
            type="button"
            class="menu-toggle"
            title="Меню"
            @click="menuOpen = true"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div
            v-if="menuOpen"
            class="menu-overlay"
            @click="menuOpen = false"
        ></div>

        <aside
            class="side-menu"
            :class="{ 'is-open': menuOpen }"
        >
            <button
                type="button"
                class="side-menu-close"
                title="Закрыть"
                @click="menuOpen = false"
            >
                ✕
            </button>

            <div class="side-menu-user">
                {{ user.email }}
            </div>

            <div class="side-menu-background">
                <div class="side-menu-background-label">
                    Фоновая картинка
                </div>

                <div
                    v-if="user.background_url"
                    class="side-menu-background-preview"
                    :style="{ backgroundImage: `url(${user.background_url})` }"
                ></div>

                <label class="side-menu-background-upload">
                    {{ backgroundLoading ? 'Загрузка...' : (user.background_url ? 'Изменить фон' : 'Добавить фон') }}

                    <input
                        type="file"
                        accept="image/*"
                        hidden
                        :disabled="backgroundLoading"
                        @change="onBackgroundFileChange"
                    >
                </label>

                <button
                    v-if="user.background_url"
                    type="button"
                    class="side-menu-background-remove"
                    :disabled="backgroundLoading"
                    @click="removeBackground"
                >
                    Удалить фон
                </button>

                <div
                    v-if="backgroundError"
                    class="side-menu-background-error"
                >
                    {{ backgroundError }}
                </div>
            </div>

            <nav class="side-menu-nav">
                <button
                    type="button"
                    class="side-menu-logout"
                    :disabled="loggingOut"
                    @click="logout"
                >
                    {{ loggingOut ? 'Выход...' : 'Выход' }}
                </button>
            </nav>
        </aside>

        <LinkBlocks />

        <Links />
    </template>
</template>

<style scoped>
.menu-toggle {
    position: fixed;
    top: 16px;
    left: 16px;
    z-index: 30;

    width: 40px;
    height: 40px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;

    padding: 0;
    border: 1px solid #e5e5e5;
    border-radius: 8px;

    background: #fff;
    cursor: pointer;

    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.menu-toggle span {
    width: 18px;
    height: 2px;
    background: #333;
}

.menu-overlay {
    position: fixed;
    inset: 0;
    z-index: 40;

    background: rgba(0, 0, 0, .3);
}

.side-menu {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 50;

    width: 260px;

    display: flex;
    flex-direction: column;

    padding: 20px;

    background: #fff;
    box-shadow: 2px 0 12px rgba(0, 0, 0, .12);

    transform: translateX(-100%);
    transition: transform .2s ease;
}

.side-menu.is-open {
    transform: translateX(0);
}

.side-menu-close {
    align-self: flex-end;

    width: 32px;
    height: 32px;

    border: 0;
    border-radius: 6px;

    background: transparent;
    cursor: pointer;
    font-size: 16px;
}

.side-menu-close:hover {
    background: #f5f5f5;
}

.side-menu-user {
    margin: 10px 0 20px;
    padding-bottom: 15px;

    border-bottom: 1px solid #eee;

    font-size: 14px;
    color: #666;
    word-break: break-all;
}

.side-menu-background {
    display: flex;
    flex-direction: column;
    gap: 8px;

    margin-bottom: 20px;
    padding-bottom: 20px;

    border-bottom: 1px solid #eee;
}

.side-menu-background-label {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.side-menu-background-preview {
    height: 80px;

    border-radius: 8px;

    background-color: #f5f5f5;
    background-size: cover;
    background-position: center;
}

.side-menu-background-upload {
    padding: 9px 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    background: transparent;
    color: #333;

    text-align: center;
    font-size: 13px;

    cursor: pointer;
}

.side-menu-background-upload:hover {
    background: #f5f5f5;
}

.side-menu-background-remove {
    padding: 9px 12px;

    border: 0;
    border-radius: 6px;

    background: transparent;
    color: #d00;

    text-align: left;
    font-size: 13px;

    cursor: pointer;
}

.side-menu-background-remove:hover {
    background: #fff0f0;
}

.side-menu-background-remove:disabled,
.side-menu-background-upload:has(input:disabled) {
    opacity: .5;
    cursor: default;
}

.side-menu-background-error {
    color: #d00;
    font-size: 12px;
}

.side-menu-nav {
    display: flex;
    flex-direction: column;
}

.side-menu-logout {
    padding: 10px 12px;

    border: 0;
    border-radius: 6px;

    background: transparent;
    color: #d00;

    text-align: left;
    font-size: 14px;

    cursor: pointer;
}

.side-menu-logout:hover {
    background: #fff0f0;
}

.side-menu-logout:disabled {
    opacity: .5;
    cursor: default;
}
</style>
