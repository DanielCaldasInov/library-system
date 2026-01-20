<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: { type: String, default: 'Confirm action' },
    message: { type: String, default: 'Are you sure?' },

    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },

    danger: { type: Boolean, default: false },

    confirmDisabled: { type: Boolean, default: false },

    processing: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])

const modal = ref(null)

const open = () => modal.value?.showModal()
const close = () => modal.value?.close()

const onConfirm = () => emit('confirm')
const onCancel = () => {
    emit('cancel')
    close()
}

defineExpose({ open, close })
</script>

<template>
    <dialog ref="modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ title }}</h3>

            <p class="py-2 opacity-80">{{ message }}</p>

            <slot />

            <div class="modal-action">
                <button class="btn py-2 px-2 rounded-md hover:bg-black" type="button" @click="onCancel">
                    {{ cancelText }}
                </button>

                <button
                    type="button"
                    :class="['btn py-2 px-2 rounded-md hover:bg-black', danger ? 'bg-red-800' : 'bg-blue-950']"
                    :disabled="confirmDisabled || processing"
                    @click="onConfirm"
                >
                    <span v-if="processing">Processing...</span>
                    <span v-else>{{ confirmText }}</span>
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="onCancel">close</button>
        </form>
    </dialog>
</template>
