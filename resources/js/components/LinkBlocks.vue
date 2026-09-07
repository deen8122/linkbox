<script setup>
import {
    computed,
    onMounted,
    onBeforeUnmount,
    ref
} from 'vue'
import LinkBlockModal from './LinkBlockModal.vue'
import LinkBlockGroupModal from './LinkBlockGroupModal.vue'

const blocks = ref([])
const groups = ref([])

const loading = ref(true)
const error = ref('')
const draggedBlock = ref(null)
const dragOverBlock = ref(null)
const showModal = ref(false)
const editingBlock = ref(null)
const openedMenu = ref(null)

const showGroupModal = ref(false)
const editingGroup = ref(null)
const openedGroupMenu = ref(null)
const draggedGroup = ref(null)
const dragOverGroupPill = ref(null)
const dragOverGroupSection = ref(null)

const closeMenuOnOutsideClick = (event) => {
    if (
        !event.target.closest('.link-block-menu')
    ) {
        openedMenu.value = null
    }

    if (
        !event.target.closest('.link-block-group-menu')
    ) {
        openedGroupMenu.value = null
    }
}

const groupedBlocks = computed(() => {
    return groups.value.map(group => ({
        group,
        items: blocks.value.filter(
            block => block.link_block_group_id === group.id
        ),
    }))
})

const ungroupedBlocks = computed(() => {
    return blocks.value.filter(
        block => !block.link_block_group_id
    )
})

const sections = computed(() => {
    return [
        ...groupedBlocks.value,
        {
            group: null,
            items: ungroupedBlocks.value,
        },
    ]
})
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

    movedBlock.link_block_group_id = targetBlock.link_block_group_id ?? null

    blocks.value.splice(
        toIndex,
        0,
        movedBlock
    )

    dragOverBlock.value = null
    draggedBlock.value = null

    await saveOrder()
}

const dropOnGroup = async (event, groupId) => {
    event.preventDefault()

    dragOverGroupSection.value = null

    if (!draggedBlock.value) {
        return
    }

    const fromIndex = blocks.value.findIndex(
        block => block.id === draggedBlock.value.id
    )

    if (fromIndex === -1) {
        return
    }

    const [movedBlock] = blocks.value.splice(
        fromIndex,
        1
    )

    movedBlock.link_block_group_id = groupId

    blocks.value.push(movedBlock)

    draggedBlock.value = null

    await saveOrder()
}

const dragEnd = () => {
    draggedBlock.value = null
    dragOverBlock.value = null
    dragOverGroupSection.value = null
}
const saveOrder = async () => {
    const data = blocks.value.map(
        (block, index) => ({
            id: block.id,
            position: index,
            link_block_group_id: block.link_block_group_id ?? null,
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

const loadGroups = async () => {
    try {
        const response = await fetch('/api/link-block-groups', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })

        if (!response.ok) {
            throw new Error('Не удалось загрузить категории')
        }

        groups.value = await response.json()
    } catch (e) {
        console.error(e)
    }
}

const toggleGroupMenu = (groupId) => {
    openedGroupMenu.value =
        openedGroupMenu.value === groupId
            ? null
            : groupId
}

const createGroup = () => {
    editingGroup.value = null
    showGroupModal.value = true
}

const editGroup = (group) => {
    openedGroupMenu.value = null

    editingGroup.value = { ...group }
    showGroupModal.value = true
}

const closeGroupModal = () => {
    showGroupModal.value = false
    editingGroup.value = null
}

const saveGroup = (group) => {
    const index = groups.value.findIndex(
        item => item.id === group.id
    )

    if (index === -1) {
        groups.value.push(group)
    } else {
        groups.value[index] = group
    }

    closeGroupModal()
}

const deleteGroup = async (group) => {
    openedGroupMenu.value = null

    if (!confirm(`Удалить категорию «${group.name}»?`)) {
        return
    }

    try {
        const response = await fetch(
            `/api/link-block-groups/${group.id}`,
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
                data.message || 'Не удалось удалить категорию'
            )
        }

        groups.value = groups.value.filter(
            item => item.id !== group.id
        )

        blocks.value = blocks.value.map(block => (
            block.link_block_group_id === group.id
                ? { ...block, link_block_group_id: null }
                : block
        ))
    } catch (e) {
        console.error(e)

        alert(
            e.message || 'Не удалось удалить категорию'
        )
    }
}

const groupDragStart = (group) => {
    draggedGroup.value = group
}

const groupDragOver = (event, group) => {
    event.preventDefault()

    if (draggedBlock.value) {
        dragOverGroupSection.value = group.id
        return
    }

    if (
        !draggedGroup.value ||
        draggedGroup.value.id === group.id
    ) {
        return
    }

    dragOverGroupPill.value = group
}

const groupDrop = async (event, targetGroup) => {
    event.preventDefault()

    if (draggedBlock.value) {
        await dropOnGroup(event, targetGroup.id)
        return
    }

    if (
        !draggedGroup.value ||
        draggedGroup.value.id === targetGroup.id
    ) {
        return
    }

    const fromIndex = groups.value.findIndex(
        group => group.id === draggedGroup.value.id
    )

    const toIndex = groups.value.findIndex(
        group => group.id === targetGroup.id
    )

    if (fromIndex === -1 || toIndex === -1) {
        return
    }

    const [movedGroup] = groups.value.splice(
        fromIndex,
        1
    )

    groups.value.splice(
        toIndex,
        0,
        movedGroup
    )

    dragOverGroupPill.value = null
    draggedGroup.value = null

    await saveGroupOrder()
}

const groupDragEnd = () => {
    draggedGroup.value = null
    dragOverGroupPill.value = null
}

const saveGroupOrder = async () => {
    const data = groups.value.map(
        (group, index) => ({
            id: group.id,
            position: index,
        })
    )

    try {
        const response = await fetch(
            '/api/link-block-groups/reorder',
            {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    groups: data,
                }),
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось сохранить порядок категорий'
            )
        }
    } catch (e) {
        console.error(e)

        await loadGroups()
    }
}

const dragOverSection = (event, groupId) => {
    event.preventDefault()

    if (!draggedBlock.value) {
        return
    }

    dragOverGroupSection.value = groupId
}

const createGroupId = ref(null)

const createBlock = (groupId = null) => {
    editingBlock.value = null
    createGroupId.value = groupId
    showModal.value = true
}

const editBlock1 = (block) => {
    editingBlock.value = { ...block }
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingBlock.value = null
    createGroupId.value = null
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
    loadGroups()
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

        <!-- Категории -->
        <div
            v-else
            class="link-block-groups-bar"
        >
            <div
                v-for="group in groups"
                :key="group.id"
                class="link-block-group-pill"
                :class="{
        'is-dragging': draggedGroup?.id === group.id,
        'is-drag-over': dragOverGroupPill?.id === group.id,
        'is-drop-target': dragOverGroupSection === group.id,
    }"
                draggable="true"
                @dragstart="groupDragStart(group)"
                @dragover="groupDragOver($event, group)"
                @drop="groupDrop($event, group)"
                @dragend="groupDragEnd"
            >
                <span
                    class="link-block-group-dot"
                    :style="{ backgroundColor: group.color || '#ccc' }"
                ></span>

                <span class="link-block-group-pill-name">
                    {{ group.name }}
                </span>

                <div class="link-block-group-menu">
                    <button
                        type="button"
                        class="link-block-group-menu-button"
                        title="Действия"
                        @click.stop.prevent="toggleGroupMenu(group.id)"
                    >
                        ⋮
                    </button>

                    <div
                        v-if="openedGroupMenu === group.id"
                        class="link-block-group-menu-dropdown"
                        @click.stop
                    >
                        <button
                            type="button"
                            @click="editGroup(group)"
                        >
                            Редактировать
                        </button>

                        <button
                            type="button"
                            class="delete"
                            @click="deleteGroup(group)"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="link-block-group-add"
                @click="createGroup"
            >
                + Категория
            </button>
        </div>

        <!-- Секции по категориям -->
        <div
            v-if="!loading && !error"
            class="link-blocks-sections"
        >
            <div
                v-for="section in sections"
                :key="section.group?.id ?? 'ungrouped'"
                class="link-block-section"
                :class="{ 'has-background': !!section.group?.background_color }"
                :style="section.group?.background_color ? { backgroundColor: section.group.background_color } : {}"
            >
                <h3
                    v-if="section.group || section.items.length"
                    class="link-block-section-title"
                >
                    <span
                        v-if="section.group"
                        class="link-block-group-dot"
                        :style="{ backgroundColor: section.group.color || '#ccc' }"
                    ></span>

                    {{ section.group ? section.group.name : 'Без группы' }}
                </h3>

                <div
                    class="link-blocks-grid"
                    :class="{ 'is-drop-target': dragOverGroupSection === (section.group?.id ?? null) }"
                    @dragover="dragOverSection($event, section.group?.id ?? null)"
                    @drop="dropOnGroup($event, section.group?.id ?? null)"
                >

                    <!-- Существующие блоки -->
                    <div
                        v-for="block in section.items"
                        :key="block.id"
                        class="link-block"
                        :class="{
        'is-dragging': draggedBlock?.id === block.id,
        'is-drag-over': dragOverBlock?.id === block.id,
    }"
                        draggable="true"
                        @dragstart="dragStart(block)"
                        @dragover.stop="dragOver($event, block)"
                        @drop.stop="drop($event, block)"
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

                    <!-- Добавить ссылку в этот раздел -->
                    <button
                        type="button"
                        class="link-block link-block-empty"
                        @click="createBlock(section.group?.id ?? null)"
                    >
                        <span>+</span>
                    </button>

                </div>
            </div>
        </div>

        <!-- Модальное окно категории -->
        <LinkBlockGroupModal
            v-if="showGroupModal"
            :group="editingGroup"
            @close="closeGroupModal"
            @saved="saveGroup"
        />

        <!-- Модальное окно -->
        <LinkBlockModal
            v-if="showModal"
            :block="editingBlock"
            :groups="groups"
            :default-group-id="createGroupId"
            @close="closeModal"
            @saved="saveBlock"
        />

    </section>
</template>
