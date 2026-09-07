<script setup>
import { onMounted, ref } from 'vue'
import Login from './components/Login.vue'
import LinkBlocks from './components/LinkBlocks.vue'
import Links from './components/Links.vue'

const user = ref(null)
const loading = ref(true)
const menuOpen = ref(false)
const loggingOut = ref(false)

const getCsrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
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
