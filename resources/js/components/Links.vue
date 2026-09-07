<script setup>
import { onMounted, ref, watch } from 'vue'

const url = ref('')
const search = ref('')

const links = ref([])

const loading = ref(false)
const searchLoading = ref(false)
const editLoading = ref(false)
const deleteLoading = ref(false)

const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

const editingLink = ref(null)

const editUrl = ref('')
const editTitle = ref('')
const editTags = ref([])

const newTag = ref('')

let searchTimeout = null


/**
 * Загрузка ссылок
 */
const loadLinks = async (page = 1) => {
    try {
        const params = new URLSearchParams({
            page: page.toString(),
        })

        if (search.value.trim()) {
            params.append(
                'search',
                search.value.trim()
            )
        }

        const response = await fetch(
            `/api/links?${params.toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось загрузить ссылки'
            )
        }

        const result = await response.json()

        links.value = result.data

        currentPage.value = result.current_page
        lastPage.value = result.last_page
        total.value = result.total
    } catch (error) {
        console.error(error)
    }
}


/**
 * Добавление ссылки
 */
const addLink = async () => {
    if (!url.value.trim()) {
        return
    }

    loading.value = true

    try {
        const response = await fetch(
            '/api/links',
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    url: url.value,
                }),
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось добавить ссылку'
            )
        }

        url.value = ''

        await loadLinks(1)
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}


/**
 * Поиск
 */
const searchLinks = () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(async () => {
        searchLoading.value = true

        try {
            await loadLinks(1)
        } finally {
            searchLoading.value = false
        }
    }, 300)
}

watch(search, searchLinks)


/**
 * Пагинация
 */
const changePage = async (page) => {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return
    }

    await loadLinks(page)
}


/**
 * Открыть редактирование
 */
const openEdit = (link) => {
    editingLink.value = link

    editUrl.value = link.url
    editTitle.value = link.title || ''

    editTags.value = (link.tags || []).map(
        tag => tag.name
    )

    newTag.value = ''
}


/**
 * Закрыть модальное окно
 */
const closeEdit = () => {
    editingLink.value = null

    editUrl.value = ''
    editTitle.value = ''
    editTags.value = []
    newTag.value = ''
}


/**
 * Добавить тег
 */
const addTag = () => {
    const tag = newTag.value.trim()

    if (!tag) {
        return
    }

    // Не добавляем одинаковые теги
    if (
        !editTags.value.some(
            item =>
                item.toLowerCase() ===
                tag.toLowerCase()
        )
    ) {
        editTags.value.push(tag)
    }

    newTag.value = ''
}


/**
 * Удалить тег
 */
const removeTag = (index) => {
    editTags.value.splice(index, 1)
}


/**
 * Сохранение ссылки
 */
const updateLink = async () => {
    if (!editUrl.value.trim()) {
        return
    }

    editLoading.value = true

    try {
        const response = await fetch(
            `/api/links/${editingLink.value.id}`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type':
                        'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    url: editUrl.value,
                    title: editTitle.value,
                    tags: editTags.value,
                }),
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось изменить ссылку'
            )
        }

        const updatedLink =
            await response.json()

        const index =
            links.value.findIndex(
                link =>
                    link.id ===
                    updatedLink.id
            )

        if (index !== -1) {
            links.value[index] =
                updatedLink
        }

        closeEdit()
    } catch (error) {
        console.error(error)
    } finally {
        editLoading.value = false
    }
}


/**
 * Удаление
 */
const deleteLink = async () => {
    if (!editingLink.value) {
        return
    }

    const link = editingLink.value

    if (
        !confirm(
            `Удалить ссылку «${
                link.title || link.url
            }»?`
        )
    ) {
        return
    }

    deleteLoading.value = true

    try {
        const response = await fetch(
            `/api/links/${link.id}`,
            {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                },
            }
        )

        if (!response.ok) {
            throw new Error(
                'Не удалось удалить ссылку'
            )
        }

        links.value =
            links.value.filter(
                item =>
                    item.id !== link.id
            )

        total.value--

        closeEdit()

        /*
         * Если удалили последнюю
         * ссылку на странице —
         * переходим назад.
         */
        if (
            links.value.length === 0 &&
            currentPage.value > 1
        ) {
            await loadLinks(
                currentPage.value - 1
            )
        }
    } catch (error) {
        console.error(error)
    } finally {
        deleteLoading.value = false
    }
}


onMounted(() => {
    loadLinks()
})
</script>


<template>
    <div class="links">

        <!-- Добавление -->
        <form
            @submit.prevent="addLink"
            class="add-form"
        >
            <input
                v-model="url"
                type="url"
                placeholder="Вставьте ссылку"
                required
            >

            <button
                type="submit"
                :disabled="loading"
            >
                {{
                    loading
                        ? 'Добавление...'
                        : 'Добавить'
                }}
            </button>
        </form>


        <!-- Поиск -->
        <div class="search-form">
            <input
                v-model="search"
                type="search"
                placeholder="Поиск по названию..."
            >

            <span
                v-if="searchLoading"
                class="search-loading"
            >
                Поиск...
            </span>
        </div>


        <!-- Количество -->
        <div
            v-if="total"
            class="result-info"
        >
            Найдено: {{ total }}
        </div>


        <!-- Список -->
        <div class="link-list">

            <article
                v-for="link in links"
                :key="link.id"
                class="link-item"
            >

                <a
                    :href="link.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="link-title"
                >
                    {{ link.title || link.url }}
                </a>


                <!-- Теги -->
                <div
                    v-if="link.tags?.length"
                    class="tags"
                >
                    <span
                        v-for="tag in link.tags"
                        :key="tag.id"
                        class="tag"
                    >
                        {{ tag.name }}
                    </span>
                </div>


                <div class="link-url">
                    {{ link.url }}
                </div>


                <!-- Три точки -->
                <button
                    type="button"
                    class="more-button"
                    @click="openEdit(link)"
                >
                    ⋮
                </button>

            </article>


            <div
                v-if="!links.length"
                class="empty"
            >
                {{
                    search
                        ? 'Ничего не найдено'
                        : 'Ссылок пока нет'
                }}
            </div>

        </div>


        <!-- Пагинация -->
        <div
            v-if="lastPage > 1"
            class="pagination"
        >

            <button
                :disabled="currentPage === 1"
                @click="
                    changePage(
                        currentPage - 1
                    )
                "
            >
                ←
            </button>

            <button
                v-for="page in lastPage"
                :key="page"
                :class="{
                    active:
                        page === currentPage
                }"
                @click="changePage(page)"
            >
                {{ page }}
            </button>

            <button
                :disabled="
                    currentPage === lastPage
                "
                @click="
                    changePage(
                        currentPage + 1
                    )
                "
            >
                →
            </button>

        </div>


        <!-- Модальное окно -->
        <div
            v-if="editingLink"
            class="modal-overlay"
            @click.self="closeEdit"
        >

            <div class="modal">

                <div class="modal-header">

                    <h3>
                        Редактирование
                    </h3>

                    <button
                        type="button"
                        class="modal-close"
                        @click="closeEdit"
                    >
                        ×
                    </button>

                </div>


                <form
                    @submit.prevent="updateLink"
                >

                    <!-- URL -->
                    <label>
                        Ссылка
                    </label>

                    <input
                        v-model="editUrl"
                        type="url"
                        required
                    >


                    <!-- Название -->
                    <label>
                        Название
                    </label>

                    <input
                        v-model="editTitle"
                        type="text"
                        placeholder="Название ссылки"
                    >


                    <!-- Теги -->
                    <label>
                        Теги
                    </label>

                    <div class="edit-tags">

                        <span
                            v-for="(
                                tag,
                                index
                            ) in editTags"
                            :key="`${tag}-${index}`"
                            class="edit-tag"
                        >

                            {{ tag }}

                            <button
                                type="button"
                                @click="
                                    removeTag(
                                        index
                                    )
                                "
                            >
                                ×
                            </button>

                        </span>

                    </div>


                    <!-- Добавление тега -->
                    <div
                        class="add-tag"
                    >

                        <input
                            v-model="newTag"
                            type="text"
                            placeholder="Новый тег"
                            @keydown.enter.prevent="
                                addTag
                            "
                        >

                        <button
                            type="button"
                            @click="addTag"
                        >
                            Добавить
                        </button>

                    </div>


                    <!-- Кнопки -->
                    <div
                        class="modal-actions"
                    >

                        <button
                            type="button"
                            class="delete-button"
                            :disabled="
                                deleteLoading
                            "
                            @click="
                                deleteLink
                            "
                        >
                            {{
                                deleteLoading
                                    ? 'Удаление...'
                                    : 'Удалить'
                            }}
                        </button>


                        <div
                            class="modal-actions-right"
                        >

                            <button
                                type="button"
                                @click="
                                    closeEdit
                                "
                            >
                                Отмена
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    editLoading
                                "
                            >
                                {{
                                    editLoading
                                        ? 'Сохранение...'
                                        : 'Сохранить'
                                }}
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>
</template>


<style scoped>

.links {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}


/* Добавление */

.add-form {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.add-form input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
}

.add-form button {
    padding: 10px 20px;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
}


/* Поиск */

.search-form {
    position: relative;
    margin-bottom: 10px;
}

.search-form input {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
}

.search-loading {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #999;
}


/* Информация */

.result-info {
    margin-bottom: 15px;
    font-size: 13px;
    color: #999;
}


/* Список */

.link-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.link-item {
    position: relative;

    padding: 14px 45px 14px 16px;

    border: 1px solid #e5e5e5;
    border-radius: 8px;

    transition: background 0.15s;
}

.link-item:hover {
    background: #fafafa;
}


/* Название */

.link-title {
    display: block;
    margin-bottom: 5px;

    color: #222;

    font-size: 16px;
    font-weight: 500;

    text-decoration: none;
}

.link-title:hover {
    text-decoration: underline;
}


/* Теги */

.tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 5px;
}

.tag {
    padding: 2px 6px;

    background: #f3f3f3;
    border-radius: 4px;

    color: #777;
    font-size: 11px;
}


/* URL */

.link-url {
    overflow: hidden;

    color: #999;
    font-size: 12px;

    text-overflow: ellipsis;
    white-space: nowrap;
}


/* Три точки */

.more-button {
    position: absolute;

    top: 50%;
    right: 10px;

    width: 30px;
    height: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    transform: translateY(-50%);

    border: 0;
    border-radius: 6px;

    background: transparent;

    color: #777;

    font-size: 22px;
    line-height: 1;

    cursor: pointer;

    opacity: 0;
    transition:
        opacity 0.15s,
        background 0.15s;
}

.link-item:hover .more-button {
    opacity: 1;
}

.more-button:hover {
    background: #eee;
}


/* Пагинация */

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;

    margin-top: 25px;
}

.pagination button {
    min-width: 36px;
    height: 36px;

    padding: 0 10px;

    border: 1px solid #ddd;
    border-radius: 6px;

    background: #fff;

    cursor: pointer;
}

.pagination button.active {
    background: #222;
    color: #fff;
    border-color: #222;
}

.pagination button:disabled {
    opacity: 0.4;
    cursor: default;
}


/* Пусто */

.empty {
    padding: 40px;

    text-align: center;

    color: #999;
}


/* Modal */

.modal-overlay {
    position: fixed;
    inset: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(0, 0, 0, 0.4);

    z-index: 1000;
}

.modal {
    width: min(
        550px,
        calc(100% - 40px)
    );

    padding: 22px;

    background: #fff;

    border-radius: 10px;

    box-shadow:
        0 10px 40px
        rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 20px;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    border: 0;
    background: none;

    font-size: 24px;

    cursor: pointer;
}


/* Поля */

.modal form > label {
    display: block;

    margin-bottom: 6px;
    margin-top: 14px;

    font-size: 13px;
    font-weight: 500;
}

.modal form > label:first-of-type {
    margin-top: 0;
}

.modal form > input {
    width: 100%;
    box-sizing: border-box;

    padding: 10px 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    font-size: 14px;
}


/* Теги */

.edit-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    min-height: 20px;
    margin-bottom: 8px;
}

.edit-tag {
    display: inline-flex;
    align-items: center;

    gap: 5px;

    padding: 4px 7px;

    background: #f1f1f1;
    border-radius: 5px;

    color: #555;
    font-size: 12px;
}

.edit-tag button {
    padding: 0;

    border: 0;
    background: none;

    color: #888;

    cursor: pointer;
}


/* Новый тег */

.add-tag {
    display: flex;
    gap: 7px;
}

.add-tag input {
    flex: 1;

    padding: 8px 10px;

    border: 1px solid #ddd;
    border-radius: 6px;
}

.add-tag button {
    padding: 8px 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    background: #fff;

    cursor: pointer;
}


/* Кнопки modal */

.modal-actions {
    display: flex;
    justify-content: space-between;

    margin-top: 25px;
}

.modal-actions-right {
    display: flex;
    gap: 8px;
}

.modal-actions button {
    padding: 8px 14px;

    border: 1px solid #ddd;
    border-radius: 6px;

    background: #fff;

    cursor: pointer;
}

.modal-actions-right button:last-child {
    background: #222;
    color: #fff;
    border-color: #222;
}

.modal-actions .delete-button {
    color: #c00;
    border-color: #e5b5b5;
}

.modal-actions button:disabled {
    opacity: 0.5;
    cursor: default;
}

</style>
