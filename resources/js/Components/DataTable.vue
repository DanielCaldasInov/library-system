<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    data: Object,
    sort: String,
    direction: String,
    filters: Object,
    route: String,
})

const sortBy = (column) => {
    router.get(props.route, {
        ...props.filters,
        sort: column,
        direction:
            props.sort === column && props.direction === 'asc'
                ? 'desc'
                : 'asc',
    }, {
        preserveState: true,
        replace: true,
    })
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table bg-gray-800 w-full">
            <thead>
            <slot name="head" :sortBy="sortBy" :sort="sort" :direction="direction" />
            </thead>

            <tbody>
            <slot name="body" :rows="data.data" />
            </tbody>
        </table>
    </div>
</template>
