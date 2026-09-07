<script setup>
import {
    onMounted,
    onBeforeUnmount,
    ref
} from 'vue'
import LinkBlockModal from './LinkBlockModal.vue'

const blocks = ref([])

const loading = ref(true)
const error = ref('')
const draggedBlock = ref(null)
const dragOverBlock = ref(null)
const showModal = ref(false)
const editingBlock = ref(null)
const openedMenu = ref(null)
const closeMenuOnOutsideClick = (event) => {
    if (
        !event.target.closest('.link-block-menu')
    ) {
        openedMenu.value = null
    }
}
const dragStart = (block) => {
    draggedBlock.value = block
}
const toggleMenu = (blockId) => {
    openedMenu.value =
        openedMenu.value === blockId
            ? null
            : blockId
}
const editBlock = (block) => {
    openedMenu.value = null

    editingBlock.value = { ...block }
    showModal.value = true
}
const dragOver = (event, block) => {
    event.preventDefault()

    if (
        !draggedBlock.value ||
        draggedBlock.value.id === block.id
    ) {
        return
    }

    dragOverBlock.value = block
}

const drop = async (event, targetBlock) => {
    event.preventDefault()

    if (
        !draggedBlock.value ||
        draggedBlock.value.id === targetBlock.id
    ) {
        return
    }

    const fromIndex = blocks.value.findIndex(
        block => block.id === draggedBlock.value.id
    )

    const toIndex = blocks.value.findIndex(
        block => block.id === targetBlock.id
    )

    if (fromIndex === -1 || toIndex === -1) {
        return
    }

    const [movedBlock] = blocks.value.splice(
        fromIndex,
        1
    )

    blocks.value.splice(
        toIndex,
        0,
        movedBlock
    )

    dragOverBlock.value = null
    draggedBlock.value = null

    await saveOrder()
}

const dragEnd = () => {
    draggedBlock.value = null
    dragOverBlock.value = null
}
const saveOrder = async () => {
    const data = blocks.value.map(
        (block, index) => ({
            id: block.id,
            position: index,
        })
    )

    try {
        const response = await fetch(
            '/api/link-blocks/reorder',
            {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    blocks: data,
                }),
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось сохранить порядок'
            )
        }
    } catch (e) {
        console.error(e)

        // На всякий случай возвращаем актуальный порядок
        // с сервера.
        await loadBlocks()
    }
}
const getCsrfToken = () => {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
}

const loadBlocks = async () => {
    loading.value = true
    error.value = ''

    try {
        const response = await fetch('/api/link-blocks', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })

        if (response.status === 401) {
            error.value = 'Необходимо авторизоваться'
            return
        }

        if (!response.ok) {
            throw new Error('Не удалось загрузить ссылки')
        }

        blocks.value = await response.json()
    } catch (e) {
        console.error(e)

        error.value = e.message || 'Произошла ошибка'
    } finally {
        loading.value = false
    }
}

const createBlock = () => {
    editingBlock.value = null
    showModal.value = true
}

const editBlock1 = (block) => {
    editingBlock.value = { ...block }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingBlock.value = null
}

const saveBlock = (block) => {
    const index = blocks.value.findIndex(
        item => item.id === block.id
    )

    if (index === -1) {
        blocks.value.push(block)
    } else {
        blocks.value[index] = block
    }

    closeModal()
}

const deleteBlock = async (block) => {
    if (!confirm(`Удалить «${block.title}»?`)) {
        return
    }

    try {
        const response = await fetch(
            `/api/link-blocks/${block.id}`,
            {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            }
        )

        if (!response.ok) {
            const data = await response.json().catch(() => ({}))

            throw new Error(
                data.message || 'Не удалось удалить ссылку'
            )
        }

        blocks.value = blocks.value.filter(
            item => item.id !== block.id
        )
    } catch (e) {
        console.error(e)

        alert(
            e.message || 'Не удалось удалить ссылку'
        )
    }
}

onMounted(() => {
    loadBlocks()

    document.addEventListener(
        'click',
        closeMenuOnOutsideClick
    )
})

onBeforeUnmount(() => {
    document.removeEventListener(
        'click',
        closeMenuOnOutsideClick
    )
})
</script>

<template>
    <section class="link-blocks">

        <!-- Загрузка -->
        <div
            v-if="loading"
            class="link-blocks-loading"
        >
            Загрузка...
        </div>

        <!-- Ошибка -->
        <div
            v-else-if="error"
            class="link-blocks-error"
        >
            {{ error }}
        </div>

        <!-- Сетка -->
        <div
            v-else
            class="link-blocks-grid"
        >

            <!-- Существующие блоки -->
            <div
                v-for="block in blocks"
                :key="block.id"
                class="link-block"
                :class="{
        'is-dragging': draggedBlock?.id === block.id,
        'is-drag-over': dragOverBlock?.id === block.id,
    }"
                draggable="true"
                @dragstart="dragStart(block)"
                @dragover="dragOver($event, block)"
                @drop="drop($event, block)"
                @dragend="dragEnd"
            >

                <!-- Ссылка -->
                <a
                    :href="block.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="link-block-content"
                >

                    <!-- Картинка -->
                    <div class="link-block-image">
                        <img
                            v-if="block.image_url"
                            :src="block.image_url"
                            :alt="block.title"
                        >

                        <div
                            v-else
                            class="link-block-no-image"
                        >
                            🔗
                        </div>
                    </div>

                    <!-- Заголовок -->
                    <div class="link-block-title">
                        <img
                            v-if="block.favicon_url"
                            :src="block.favicon_url"
                            :alt="''"
                            class="link-block-favicon"
                        >

                        <span>
        {{ block.title }}
    </span>
                    </div>

                </a>

                <!-- Действия -->
                <!-- Меню -->
                <div class="link-block-menu">

                    <!-- Три точки -->
                    <button
                        type="button"
                        class="link-block-menu-button"
                        title="Действия"
                        @click.stop.prevent="toggleMenu(block.id)"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <!-- Выпадающее меню -->
                    <div
                        v-if="openedMenu === block.id"
                        class="link-block-menu-dropdown"
                        @click.stop
                    >
                        <button
                            type="button"
                            @click="editBlock(block)"
                        >
                            Редактировать
                        </button>

                        <button
                            type="button"
                            class="delete"
                            @click="deleteBlock(block)"
                        >
                            Удалить
                        </button>
                    </div>

                </div>

            </div>

            <!-- Единственный пустой блок -->
            <button
                type="button"
                class="link-block link-block-empty"
                @click="createBlock"
            >
                <span>+</span>
            </button>

        </div>

        <!-- Модальное окно -->
        <LinkBlockModal
            v-if="showModal"
            :block="editingBlock"
            @close="closeModal"
            @saved="saveBlock"
        />

    </section>
</template>
