<script setup>
const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
})

const formatDate = (iso) => {
    if (!iso) return ''
    const d = new Date(iso)
    return d.toLocaleDateString()
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Books -->
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Books</h2>
                    <div class="badge badge-primary badge-lg">{{ stats.booksCount }}</div>
                </div>

                <p class="text-sm opacity-70">Last 5 added</p>

                <ul class="mt-2 space-y-2">
                    <li v-for="b in stats.recentBooks" :key="b.id" class="flex justify-between gap-3">
                        <span class="truncate">{{ b.name }}</span>
                        <span class="text-xs opacity-60 whitespace-nowrap">{{ formatDate(b.created_at) }}</span>
                    </li>
                    <li v-if="!stats.recentBooks?.length" class="text-sm opacity-60">
                        No books yet.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Authors -->
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Authors</h2>
                    <div class="badge badge-secondary badge-lg">{{ stats.authorsCount }}</div>
                </div>

                <p class="text-sm opacity-70">Last 5 added</p>

                <ul class="mt-2 space-y-2">
                    <li v-for="a in stats.recentAuthors" :key="a.id" class="flex justify-between gap-3">
                        <span class="truncate">{{ a.name }}</span>
                        <span class="text-xs opacity-60 whitespace-nowrap">{{ formatDate(a.created_at) }}</span>
                    </li>
                    <li v-if="!stats.recentAuthors?.length" class="text-sm opacity-60">
                        No authors yet.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Publishers -->
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Publishers</h2>
                    <div class="badge badge-accent badge-lg">{{ stats.publishersCount }}</div>
                </div>

                <p class="text-sm opacity-70">Last 5 added</p>

                <ul class="mt-2 space-y-2">
                    <li v-for="p in stats.recentPublishers" :key="p.id" class="flex justify-between gap-3">
                        <span class="truncate">{{ p.name }}</span>
                        <span class="text-xs opacity-60 whitespace-nowrap">{{ formatDate(p.created_at) }}</span>
                    </li>
                    <li v-if="!stats.recentPublishers?.length" class="text-sm opacity-60">
                        No publishers yet.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
