<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    meta: Object,
    route: String,
    filters: Object,
})
</script>

<template>
    <div class="mt-4 flex justify-center space-x-2 bg-gray-800 px-4 py-2 rounded-lg">
        <button
            :disabled="meta.current_page === 1"
            @click="router.get(route, { ...filters, page: meta.current_page - 1 })"
            class="btn-sm hover:bg-black rounded-md px-3"
        >
            Previous
        </button>

        <button
            v-for="page in meta.last_page"
            :key="page"
            @click="router.get(route, { ...filters, page })"
            class="btn-sm hover:bg-black rounded-md px-1"
            :class="{ 'btn-primary': page === meta.current_page }"
        >
            {{ page }}
        </button>

        <button
            :disabled="meta.current_page === meta.last_page"
            @click="router.get(route, { ...filters, page: meta.current_page + 1 })"
            class="btn-sm hover:bg-black rounded-md px-3"
        >
            Next
        </button>
    </div>
</template>
