<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: String,
    filters: Object,
    options: Array,
    statusOptions: Array,
})

const form = useForm({
    filter: props.filters?.filter ?? props.options?.[0]?.value,
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? (props.statusOptions?.[0]?.value ?? 'all'),
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
    <form @submit.prevent="submit" class="bg-gray-800 p-4 mb-6 rounded-lg">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col md:flex-row gap-2 md:items-center flex-1">
                <select v-model="form.filter" class="select w-full md:w-48 text-black">
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
                    class="input w-full text-black md:flex-1"
                />

                <button type="submit" class="btn btn-neutral hover:bg-black px-4">
                    Search
                </button>
            </div>

            <div v-if="statusOptions?.length" class="flex items-center gap-3">
                <span class="text-white font-medium whitespace-nowrap">Status</span>

                <select
                    v-model="form.status"
                    class="select select-bordered bg-white text-black min-w-[220px]"
                    @change="submit"
                >
                    <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>
        </div>
    </form>
</template>
