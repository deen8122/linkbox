<script setup>
import { computed, onMounted, ref } from 'vue'
import ImageCropper from './ImageCropper.vue'

const props = defineProps({
    block: {
        type: Object,
        default: null,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    defaultGroupId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits([
    'close',
    'saved',
])

const url = ref('')
const title = ref('')
const groupId = ref('')
const image = ref(null)
const imagePreview = ref(null)
const removeImage = ref(false)

const loading = ref(false)
const error = ref('')

const cropperFile = ref(null)
const showCropper = ref(false)

const isEdit = computed(() => !!props.block)

const MAX_IMAGE_SIZE = 15 * 1024 * 1024

onMounted(() => {
    if (props.block) {
        url.value = props.block.url
        title.value = props.block.title
        imagePreview.value = props.block.image_url
        groupId.value = props.block.link_block_group_id ?? ''
    } else if (props.defaultGroupId) {
        groupId.value = props.defaultGroupId
    }
})

const selectImage = (file) => {
    if (!file) {
        return
    }

    if (!file.type.startsWith('image/')) {
        error.value = 'Можно загрузить только изображение'
        return
    }

    if (file.size > MAX_IMAGE_SIZE) {
        error.value = 'Файл слишком большой. Максимальный размер — 15 МБ'
        return
    }

    error.value = ''
    cropperFile.value = file
    showCropper.value = true
}

const onCropped = (blob) => {
    const croppedFile = new File(
        [blob],
        'cover.jpg',
        { type: 'image/jpeg' }
    )

    image.value = croppedFile
    removeImage.value = false

    imagePreview.value = URL.createObjectURL(blob)

    showCropper.value = false
    cropperFile.value = null
}

const onCropCancel = () => {
    showCropper.value = false
    cropperFile.value = null
}

const onFileChange = (event) => {
    selectImage(event.target.files[0])
}

const onPaste = (event) => {
    const items = event.clipboardData?.items

    if (!items) {
        return
    }

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile()

            if (file) {
                selectImage(file)
                event.preventDefault()
                return
            }
        }
    }
}

const removeCurrentImage = () => {
    image.value = null
    imagePreview.value = null
    removeImage.value = true
}

const submit = async () => {
    error.value = ''

    if (!url.value.trim()) {
        error.value = 'Введите ссылку'
        return
    }

    loading.value = true

    try {
        const formData = new FormData()

        formData.append('url', url.value.trim())
        formData.append('title', title.value.trim())

        if (groupId.value) {
            formData.append('link_block_group_id', groupId.value)
        }

        if (image.value) {
            formData.append('image', image.value)
        }

        if (removeImage.value) {
            formData.append('remove_image', '1')
        }

        let requestUrl = '/api/link-blocks'
        let method = 'POST'

        if (isEdit.value) {
            requestUrl = `/api/link-blocks/${props.block.id}`

            // Laravel принимает multipart PUT не всегда удобно,
            // поэтому используем POST + _method.
            formData.append('_method', 'PUT')
        }

        const response = await fetch(requestUrl, {
            method,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            body: formData,
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
        @paste="onPaste"
    >
        <div class="modal">

            <div class="modal-header">
                <h2>
                    {{ isEdit ? 'Редактировать ссылку' : 'Добавить ссылку' }}
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
                        Ссылка
                    </label>

                    <input
                        v-model="url"
                        type="url"
                        placeholder="https://example.com"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label>
                        Заголовок
                    </label>

                    <input
                        v-model="title"
                        type="text"
                        placeholder="Название"
                    >
                </div>

                <div class="form-group">
                    <label>
                        Категория
                    </label>

                    <select v-model="groupId">
                        <option value="">
                            Без группы
                        </option>

                        <option
                            v-for="group in groups"
                            :key="group.id"
                            :value="group.id"
                        >
                            {{ group.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        Изображение
                    </label>

                    <div
                        class="image-dropzone"
                        tabindex="0"
                    >
                        <template v-if="imagePreview">
                            <img
                                :src="imagePreview"
                                alt=""
                                class="preview"
                            >

                            <button
                                type="button"
                                class="remove-image"
                                @click="removeCurrentImage"
                            >
                                Удалить изображение
                            </button>
                        </template>

                        <template v-else>
                            <div class="upload-text">
                                <strong>
                                    Вставьте изображение
                                </strong>

                                <span>
                                    Ctrl + V / Print Screen
                                </span>

                                <span>
                                    или выберите файл
                                </span>
                            </div>

                            <label class="file-button">
                                Выбрать файл

                                <input
                                    type="file"
                                    accept="image/*"
                                    hidden
                                    @change="onFileChange"
                                >
                            </label>
                        </template>
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

        <ImageCropper
            v-if="showCropper"
            :file="cropperFile"
            @cropped="onCropped"
            @cancel="onCropCancel"
        />
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
    max-width: 450px;

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

.form-group input,
.form-group select {
    width: 100%;
    box-sizing: border-box;

    padding: 10px 12px;

    border: 1px solid #ddd;
    border-radius: 6px;

    font-size: 14px;
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
.image-dropzone {
    min-height: 180px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 12px;

    padding: 15px;

    border: 2px dashed #ddd;
    border-radius: 8px;

    text-align: center;
}

.upload-text {
    display: flex;
    flex-direction: column;
    gap: 5px;

    color: #777;
}

.upload-text strong {
    color: #333;
}

.preview {
    max-width: 100%;
    max-height: 180px;

    border-radius: 8px;

    object-fit: contain;
}

.file-button {
    padding: 8px 14px;

    border-radius: 6px;

    background: #222;
    color: white;

    cursor: pointer;
}

.remove-image {
    border: 0;
    background: transparent;

    color: #d00;

    cursor: pointer;
}
</style>
