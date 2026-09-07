<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    group: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits([
    'close',
    'saved',
])

const name = ref('')
const color = ref(null)
const backgroundColor = ref(null)
const loading = ref(false)
const error = ref('')

const isEdit = computed(() => !!props.group)

const DEFAULT_COLOR = '#94a3b8'
const DEFAULT_BACKGROUND_COLOR = '#e2e8f0'

const colorModel = computed({
    get: () => color.value || DEFAULT_COLOR,
    set: (value) => { color.value = value },
})

const backgroundColorModel = computed({
    get: () => backgroundColor.value || DEFAULT_BACKGROUND_COLOR,
    set: (value) => { backgroundColor.value = value },
})

onMounted(() => {
    if (props.group) {
        name.value = props.group.name
        color.value = props.group.color || null
        backgroundColor.value = props.group.background_color || null
    }
})

const getCsrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
}

const submit = async () => {
    error.value = ''

    if (!name.value.trim()) {
        error.value = 'Введите название'
        return
    }

    loading.value = true

    try {
        let requestUrl = '/api/link-block-groups'
        let method = 'POST'

        if (isEdit.value) {
            requestUrl = `/api/link-block-groups/${props.group.id}`
            method = 'PUT'
        }

        const response = await fetch(requestUrl, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: name.value.trim(),
                color: color.value,
                background_color: backgroundColor.value,
            }),
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(
                data.message || 'Не удалось сохранить'
            )
        }

        emit('saved', data)
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div
        class="modal-overlay"
        @click.self="emit('close')"
    >
        <div class="modal">

            <div class="modal-header">
                <h2>
                    {{ isEdit ? 'Редактировать категорию' : 'Новая категория' }}
                </h2>

                <button
                    class="close-button"
                    type="button"
                    @click="emit('close')"
                >
                    ×
                </button>
            </div>

            <form @submit.prevent="submit">

                <div class="form-group">
                    <label>
                        Название
                    </label>

                    <input
                        v-model="name"
                        type="text"
                        placeholder="Например, Работа"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label>
                        Цвет кружка
                    </label>

                    <div class="color-row">
                        <input
                            v-model="colorModel"
                            type="color"
                        >

                        <button
                            v-if="color"
                            type="button"
                            class="color-reset"
                            @click="color = null"
                        >
                            Сбросить
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        Фон категории
                    </label>

                    <div class="color-row">
                        <input
                            v-model="backgroundColorModel"
                            type="color"
                        >

                        <button
                            v-if="backgroundColor"
                            type="button"
                            class="color-reset"
                            @click="backgroundColor = null"
                        >
                            Сбросить
                        </button>
                    </div>
                </div>

                <div
                    v-if="error"
                    class="error"
                >
                    {{ error }}
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        @click="emit('close')"
                    >
                        Отмена
                    </button>

                    <button
                        type="submit"
                        :disabled="loading"
                    >
                        {{ loading ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(0, 0, 0, .45);

    z-index: 1000;
}

.modal {
    width: 100%;
    max-width: 380px;

    padding: 24px;

    background: white;
    border-radius: 12px;

    box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 20px;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.close-button {
    border: 0;
    background: none;

    font-size: 28px;
    line-height: 1;

    cursor: pointer;
    color: #777;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;

    margin-bottom: 6px;

    font-size: 14px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    box-sizing: border-box;

    padding: 10px 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    font-size: 14px;
}

.color-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.color-row input[type="color"] {
    width: 44px;
    height: 34px;
    padding: 2px;

    border: 1px solid #ddd;
    border-radius: 6px;

    cursor: pointer;
}

.color-reset {
    border: 0;
    background: transparent;

    color: #777;
    font-size: 13px;

    cursor: pointer;
}

.color-reset:hover {
    color: #333;
    text-decoration: underline;
}

.error {
    margin-bottom: 15px;

    color: #d00;
    font-size: 13px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;

    gap: 10px;

    margin-top: 20px;
}

.modal-footer button {
    padding: 9px 16px;

    border: 0;
    border-radius: 6px;

    cursor: pointer;
}

.modal-footer button[type="submit"] {
    background: #222;
    color: white;
}

.modal-footer button:disabled {
    opacity: .5;
}
</style>
