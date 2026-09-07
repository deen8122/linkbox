<script setup>
import { ref } from 'vue'

const email = ref('')
const code = ref('')

const step = ref('email')
const loading = ref(false)
const error = ref('')

const requestCode = async () => {
    error.value = ''
    loading.value = true

    try {
        const response = await fetch('/auth/request-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
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
            },
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
        <div class="login-card">
            <h1>LinkBox</h1>

            <template v-if="step === 'email'">
                <p>
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
    align-items: center;
    justify-content: center;

    background: #f5f5f5;
}

.login-card {
    width: 100%;
    max-width: 360px;

    padding: 30px;

    background: white;
    border-radius: 12px;

    box-shadow: 0 10px 40px rgba(0, 0, 0, .08);
}

.login-card h1 {
    margin: 0 0 10px;

    text-align: center;
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
