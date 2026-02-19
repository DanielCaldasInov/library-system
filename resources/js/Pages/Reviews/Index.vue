<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    reviews: Object,
    filters: Object,
    searchOptions: Array,
    statusOptions: Array,
    sort: String,
    direction: String,
})

const statusLabel = (st) => {
    if (st === "pending") return "Pending"
    if (st === "active") return "Active"
    if (st === "rejected") return "Rejected"
    return st
}

const statusBadge = (st) => {
    if (st === "pending") return "badge badge-warning"
    if (st === "active") return "badge badge-success"
    if (st === "rejected") return "badge badge-error"
    return "badge"
}

const sortBy = (field) => {
    const same = props.sort === field
    const nextDirection = same ? (props.direction === "asc" ? "desc" : "asc") : "asc"

    router.get("/reviews", {
        ...props.filters,
        sort: field,
        direction: nextDirection,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    })
}

const arrow = (field) => {
    if (props.sort !== field) return ""
    return props.direction === "asc" ? "▲" : "▼"
}
</script>

<template>
    <PublicLayout title="Reviews">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Reviews
                </h1>
            </div>

            <div class="w-full max-w-6xl mx-auto space-y-4">
                <SearchForm
                    action="/reviews"
                    :filters="filters"
                    :options="searchOptions"
                    :status-options="statusOptions"
                />

                <div
                    v-if="!reviews?.data?.length"
                    class="bg-gray-800 rounded-lg p-8 text-center"
                >
                    <h2 class="text-xl font-semibold mb-2">
                        No reviews found
                    </h2>
                    <p class="opacity-70">
                        There are no reviews matching your filters.
                    </p>
                </div>

                <DataTable
                    v-else
                    :data="reviews"
                    route="/reviews"
                    :filters="{ ...filters, sort, direction }"
                >
                    <template #head>
                        <tr>
                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('citizen')">
                                Citizen {{ arrow('citizen') }}
                            </th>

                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('book')">
                                Book {{ arrow('book') }}
                            </th>

                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('rating')">
                                Rating {{ arrow('rating') }}
                            </th>

                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('status')">
                                Status {{ arrow('status') }}
                            </th>

                            <th class="text-right cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('created_at')">
                                Created {{ arrow('created_at') }}
                            </th>

                            <th class="text-right">Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="review in rows" :key="review.id">
                            <td class="truncate max-w-xs">
                                {{ review.user?.name ?? '-' }}
                            </td>

                            <td class="truncate max-w-xs">
                                {{ review.book?.name ?? '-' }}
                            </td>

                            <td>
                                {{ review.rating ?? '-' }}/5
                            </td>

                            <td>
                                <span :class="statusBadge(review.status)">
                                    {{ statusLabel(review.status) }}
                                </span>
                            </td>

                            <td class="text-right whitespace-nowrap">
                                {{ new Date(review.created_at).toLocaleDateString() }}
                            </td>

                            <td class="text-right">
                                <a
                                    :href="route('reviews.show', review.id)"
                                    class="btn btn-sm hover:bg-[#5754E8]"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    v-if="reviews?.data?.length"
                    :meta="reviews"
                    route="/reviews"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>
    </PublicLayout>
</template>
