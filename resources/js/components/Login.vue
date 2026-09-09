<script setup>
import { ref } from 'vue'

const email = ref('')
const code = ref('')

const step = ref('email')
const loading = ref(false)
const error = ref('')

const getCsrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
}

const requestCode = async () => {
    error.value = ''
    loading.value = true

    try {
        const response = await fetch('/auth/request-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: email.value,
            }),
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Ошибка')
        }

        step.value = 'code'
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

const verifyCode = async () => {
    error.value = ''
    loading.value = true

    try {
        const response = await fetch('/auth/verify-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: email.value,
                code: code.value,
            }),
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.message || 'Ошибка')
        }

        window.location.reload()
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="login">
        <div class="intro">
            <svg
                class="logo"
                width="48"
                height="48"
                viewBox="0 0 64 64"
                aria-hidden="true"
            >
                <rect width="64" height="64" rx="16" fill="#1f1f1f" />
                <g
                    fill="none"
                    stroke="#ffffff"
                    stroke-width="8"
                    stroke-linecap="round"
                >
                    <rect x="4" y="21" width="26" height="22" rx="11" transform="rotate(-45 17 32)" />
                    <rect x="34" y="21" width="26" height="22" rx="11" transform="rotate(-45 47 32)" />
                </g>
            </svg>

            <h1>LinkBox</h1>

            <p class="tagline">
                Все ваши ссылки — на одной странице
            </p>

            <p class="description">
                LinkBox — сервис для сборки персональной страницы со ссылками:
                соцсети, проекты, магазины. Группируйте ссылки по блокам,
                добавляйте обложки и настраивайте фон — без кода.
            </p>

            <ul class="features">
                <li>Группировка ссылок по блокам</li>
                <li>Обложки и favicon для каждой ссылки</li>
                <li>Свой фон профиля</li>
            </ul>
        </div>

        <div class="login-card">
            <template v-if="step === 'email'">
                <p class="card-label">
                    Введите email для входа
                </p>

                <form @submit.prevent="requestCode">
                    <input
                        v-model="email"
                        type="email"
                        placeholder="you@example.com"
                        required
                    >

                    <button
                        type="submit"
                        :disabled="loading"
                    >
                        {{ loading ? 'Отправка...' : 'Получить код' }}
                    </button>
                </form>
            </template>

            <template v-else>
                <p>
                    Код отправлен на
                    <strong>{{ email }}</strong>
                </p>

                <form @submit.prevent="verifyCode">
                    <input
                        v-model="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="000000"
                        required
                    >

                    <button
                        type="submit"
                        :disabled="loading"
                    >
                        {{ loading ? 'Проверка...' : 'Войти' }}
                    </button>
                </form>

                <button
                    class="back"
                    @click="step = 'email'"
                >
                    Изменить email
                </button>
            </template>

            <div
                v-if="error"
                class="error"
            >
                {{ error }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.login {
    min-height: 100vh;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 40px;

    padding: 40px 20px;

    background: #fafafa;
}

.intro {
    width: 100%;
    max-width: 360px;

    text-align: center;
}

.logo {
    margin-bottom: 16px;
}

.intro h1 {
    margin: 0 0 6px;

    font-size: 22px;
    font-weight: 600;
    letter-spacing: -.01em;
}

.tagline {
    margin: 0 0 14px;

    font-size: 15px;
    font-weight: 500;
    color: #1f1f1f;
}

.description {
    margin: 0 0 20px;

    color: #777;
    font-size: 13px;
    line-height: 1.6;
}

.features {
    display: flex;
    flex-direction: column;
    gap: 6px;

    margin: 0;
    padding: 0;

    list-style: none;
    border-top: 1px solid #e5e5e5;
    padding-top: 16px;
}

.features li {
    color: #555;
    font-size: 13px;
}

.login-card {
    width: 100%;
    max-width: 360px;

    padding: 30px;

    background: white;
    border: 1px solid #eee;
    border-radius: 12px;
}

.login-card .card-label {
    margin: 0 0 16px;

    color: #666;
    font-size: 14px;
}

.login-card p {
    color: #666;
    font-size: 14px;
}

form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

input {
    box-sizing: border-box;

    width: 100%;
    padding: 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    font-size: 16px;
}

button {
    padding: 12px;

    border: 0;
    border-radius: 6px;

    background: #222;
    color: white;

    cursor: pointer;
}

button:disabled {
    opacity: .5;
}

.back {
    width: 100%;
    margin-top: 10px;

    background: transparent;
    color: #666;
}

.error {
    margin-top: 15px;

    color: #d00;
    font-size: 13px;
}
</style>
