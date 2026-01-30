<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: String,
    filters: Object,
    options: Array,

    // ✅ novo: quando for usado dentro de outro card (ex.: Users index)
    // mantém compatibilidade: default = false (continua igual em outras telas)
    embedded: { type: Boolean, default: false },
})

const form = useForm({
    filter: props.filters?.filter ?? props.options?.[0]?.value ?? '',
    search: props.filters?.search ?? '',
})

const submit = () => {
    form.get(props.action, {
        preserveState: true,
        replace: true,
    })
}

defineExpose({ form })
</script>

<template>
    <form
        @submit.prevent="submit"
        :class="embedded
            ? 'w-full'
            : 'bg-gray-800 p-4 mb-6 rounded-lg'"
    >
        <div
            :class="embedded
                ? 'flex flex-col md:flex-row gap-2 items-center'
                : 'flex flex-col md:flex-row gap-2'"
        >
            <select v-model="form.filter" class="select w-48 text-black">
                <option
                    v-for="opt in options"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </select>

            <input
                v-model="form.search"
                type="text"
                placeholder="Type to search..."
                class="input flex-1 text-black"
            />

            <button type="submit" class="btn btn-neutral hover:bg-black px-4">
                Search
            </button>
        </div>
    </form>
</template>
