<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    file: {
        type: [File, Blob],
        required: true,
    },
    aspectWidth: {
        type: Number,
        default: 300,
    },
    aspectHeight: {
        type: Number,
        default: 260,
    },
})

const emit = defineEmits([
    'cropped',
    'cancel',
])

// Размер рамки обрезки на экране (сохраняет то же
// соотношение сторон, что и итоговая картинка блока).
const FRAME_WIDTH = 360
const FRAME_HEIGHT = Math.round(FRAME_WIDTH * (props.aspectHeight / props.aspectWidth))

const objectUrl = ref('')
const imageEl = ref(null)

const naturalWidth = ref(0)
const naturalHeight = ref(0)
const imageLoaded = ref(false)

const minScale = ref(1)
const zoom = ref(1)
const offset = ref({ x: 0, y: 0 })

const dragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const offsetStart = ref({ x: 0, y: 0 })

const displayScale = computed(() => minScale.value * zoom.value)
const displayWidth = computed(() => naturalWidth.value * displayScale.value)
const displayHeight = computed(() => naturalHeight.value * displayScale.value)

const clampOffset = (x, y) => {
    const minX = Math.min(0, FRAME_WIDTH - displayWidth.value)
    const minY = Math.min(0, FRAME_HEIGHT - displayHeight.value)

    return {
        x: Math.min(0, Math.max(minX, x)),
        y: Math.min(0, Math.max(minY, y)),
    }
}

const onImageLoad = () => {
    naturalWidth.value = imageEl.value.naturalWidth
    naturalHeight.value = imageEl.value.naturalHeight

    minScale.value = Math.max(
        FRAME_WIDTH / naturalWidth.value,
        FRAME_HEIGHT / naturalHeight.value
    )
    zoom.value = 1

    offset.value = clampOffset(
        (FRAME_WIDTH - naturalWidth.value * minScale.value) / 2,
        (FRAME_HEIGHT - naturalHeight.value * minScale.value) / 2
    )

    imageLoaded.value = true
}

const onZoomInput = () => {
    offset.value = clampOffset(offset.value.x, offset.value.y)
}

const startDrag = (event) => {
    dragging.value = true
    dragStart.value = { x: event.clientX, y: event.clientY }
    offsetStart.value = { ...offset.value }
}

const onDrag = (event) => {
    if (!dragging.value) {
        return
    }

    offset.value = clampOffset(
        offsetStart.value.x + (event.clientX - dragStart.value.x),
        offsetStart.value.y + (event.clientY - dragStart.value.y)
    )
}

const stopDrag = () => {
    dragging.value = false
}

const apply = () => {
    const outputWidth = props.aspectWidth * 3
    const outputHeight = props.aspectHeight * 3

    const sourceX = -offset.value.x / displayScale.value
    const sourceY = -offset.value.y / displayScale.value
    const sourceWidth = FRAME_WIDTH / displayScale.value
    const sourceHeight = FRAME_HEIGHT / displayScale.value

    const canvas = document.createElement('canvas')
    canvas.width = outputWidth
    canvas.height = outputHeight

    const ctx = canvas.getContext('2d')
    ctx.drawImage(
        imageEl.value,
        sourceX,
        sourceY,
        sourceWidth,
        sourceHeight,
        0,
        0,
        outputWidth,
        outputHeight
    )

    canvas.toBlob(
        (blob) => emit('cropped', blob),
        'image/jpeg',
        0.92
    )
}

onMounted(() => {
    objectUrl.value = URL.createObjectURL(props.file)
})

onBeforeUnmount(() => {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value)
    }
})
</script>

<template>
    <div class="cropper-overlay">
        <div class="cropper-box">
            <h3>Обрежьте картинку</h3>

            <div
                class="cropper-frame"
                :style="{ width: FRAME_WIDTH + 'px', height: FRAME_HEIGHT + 'px' }"
                @pointerdown="startDrag"
                @pointermove="onDrag"
                @pointerup="stopDrag"
                @pointerleave="stopDrag"
            >
                <img
                    ref="imageEl"
                    :src="objectUrl"
                    alt=""
                    draggable="false"
                    class="cropper-image"
                    :style="{
                        width: displayWidth + 'px',
                        height: displayHeight + 'px',
                        transform: `translate(${offset.x}px, ${offset.y}px)`,
                    }"
                    @load="onImageLoad"
                >
            </div>

            <div
                v-if="imageLoaded"
                class="cropper-zoom"
            >
                <span>−</span>

                <input
                    v-model.number="zoom"
                    type="range"
                    min="1"
                    max="3"
                    step="0.01"
                    @input="onZoomInput"
                >

                <span>+</span>
            </div>

            <div class="cropper-footer">
                <button
                    type="button"
                    @click="emit('cancel')"
                >
                    Отмена
                </button>

                <button
                    type="button"
                    class="primary"
                    :disabled="!imageLoaded"
                    @click="apply"
                >
                    Применить
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cropper-overlay {
    position: fixed;
    inset: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(0, 0, 0, .6);

    z-index: 1100;
}

.cropper-box {
    padding: 20px;

    background: white;
    border-radius: 12px;

    box-shadow: 0 20px 60px rgba(0, 0, 0, .25);

    text-align: center;
}

.cropper-box h3 {
    margin: 0 0 14px;
    font-size: 16px;
}

.cropper-frame {
    position: relative;
    overflow: hidden;

    border-radius: 8px;
    background: #eee;

    cursor: grab;
    touch-action: none;

    user-select: none;
}

.cropper-frame:active {
    cursor: grabbing;
}

.cropper-image {
    position: absolute;
    top: 0;
    left: 0;

    max-width: none;

    pointer-events: none;
}

.cropper-zoom {
    display: flex;
    align-items: center;
    gap: 8px;

    margin-top: 14px;

    color: #777;
    font-size: 16px;
}

.cropper-zoom input {
    flex: 1;
}

.cropper-footer {
    display: flex;
    justify-content: flex-end;

    gap: 10px;

    margin-top: 18px;
}

.cropper-footer button {
    padding: 9px 16px;

    border: 0;
    border-radius: 6px;

    cursor: pointer;
}

.cropper-footer button.primary {
    background: #222;
    color: white;
}

.cropper-footer button.primary:disabled {
    opacity: .5;
    cursor: default;
}
</style>
