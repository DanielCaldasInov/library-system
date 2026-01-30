<script setup>
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { ref, computed } from "vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    books: Object,
    filters: Object,
    sort: String,
    direction: String,
})

const searchOptions = [
    { value: 'name', label: 'Name' },
    { value: 'author', label: 'Author' },
    { value: 'publisher', label: 'Publisher' },
]

const confirmModal = ref(null)
const bookToDelete = ref(null)

const openDeleteModal = (book) => {
    bookToDelete.value = book
    confirmModal.value?.open()
}

const onCancelDelete = () => {
    bookToDelete.value = null
}

const deleteBlockedByActiveRequest = computed(() => {
    return (bookToDelete.value?.active_requests_count ?? 0) > 0
})

const confirmDelete = () => {
    if (!bookToDelete.value) return
    if ((bookToDelete.value.active_requests_count ?? 0) > 0) return

    router.delete(route("books.destroy", bookToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmModal.value?.close()
            bookToDelete.value = null
        },
    })
}

const availabilityBadgeClass = (book) => {
    const busy = (book?.active_requests_count ?? 0) > 0
    return busy ? "badge badge-error" : "badge badge-success"
}

const availabilityLabel = (book) => {
    const busy = (book?.active_requests_count ?? 0) > 0
    return busy ? "Unavailable" : "Available"
}

const onAvailabilityChange = (e) => {
    router.get(
        "/books",
        {
            ...props.filters,
            availability: e.target.value,
            sort: props.sort,
            direction: props.direction,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}
</script>

<template>
    <PublicLayout title="Books">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Books
                </h1>

                <div class="flex items-center gap-2">
                    <a
                        v-if="$page.props.auth?.user?.is_admin"
                        :href="route('books.export', { ...filters, sort, direction })"
                        class="btn bg-blue-800 hover:bg-blue-950"
                    >
                        Export to Excel
                    </a>

                    <a
                        v-if="$page.props.auth?.user?.is_admin"
                        :href="route('books.import.index')"
                        class="btn bg-gray-700 hover:bg-black"
                    >
                        Import Books
                    </a>

                    <a
                        v-if="$page.props.auth?.user?.is_admin"
                        :href="route('books.create')"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3]"
                    >
                        Create Book
                    </a>
                </div>
            </div>

            <div class="w-full max-w-6xl mx-auto space-y-4">
                <div class="bg-gray-800 p-4 rounded-lg">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="w-full md:flex-1 md:pr-4">
                            <SearchForm
                                action="/books"
                                :filters="filters"
                                :options="searchOptions"
                            />
                        </div>

                        <form method="get" action="/books" class="flex items-center gap-3 md:pt-4">
                            <input type="hidden" name="search" :value="filters.search ?? ''" />
                            <input type="hidden" name="filter" :value="filters.filter ?? 'name'" />
                            <input type="hidden" name="sort" :value="sort" />
                            <input type="hidden" name="direction" :value="direction" />

                            <span class="text-white font-medium">
                                Availability
                            </span>

                            <select
                                name="availability"
                                class="select select-bordered bg-white text-black min-w-[160px]"
                                :value="filters.availability ?? ''"
                                @change="onAvailabilityChange"
                            >
                                <option value="">All</option>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </form>
                    </div>
                </div>

                <DataTable
                    :data="books"
                    :sort="sort"
                    :direction="direction"
                    :filters="filters"
                    route="/books"
                >
                    <template #head="{ sortBy, sort, direction }">
                        <tr>
                            <th>Cover</th>

                            <th @click="sortBy('name')" class="cursor-pointer">
                                Name {{ sort === 'name' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th>Availability</th>

                            <th @click="sortBy('publisher')" class="cursor-pointer">
                                Publisher {{ sort === 'publisher' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th @click="sortBy('author')" class="cursor-pointer">
                                Authors {{ sort === 'author' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th @click="sortBy('price')" class="cursor-pointer text-right">
                                Price {{ sort === 'price' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th class="text-right">Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="book in rows" :key="book.id">
                            <td>
                                <div class="w-24 h-24 rounded bg-base-200 overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="book.cover"
                                        class="w-24 h-24 object-cover"
                                        :src="book.cover"
                                        :alt="`${book.name} cover`"
                                    />
                                    <span v-else class="text-xs opacity-60 px-2 text-center">
                                        No cover
                                    </span>
                                </div>
                            </td>

                            <td>{{ book.name }}</td>

                            <td>
                                <span :class="availabilityBadgeClass(book)">
                                    {{ availabilityLabel(book) }}
                                </span>
                            </td>

                            <td>{{ book.publisher?.name ?? '-' }}</td>

                            <td class="truncate max-w-sm">
                                {{ (book.authors ?? []).map(a => a.name).join(', ') }}
                            </td>

                            <td class="text-right">
                                ${{ Number(book.price ?? 0).toFixed(2) }}
                            </td>

                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a
                                        :href="route('books.show', book.id)"
                                        class="btn hover:bg-[#5754E8]"
                                    >
                                        Details
                                    </a>

                                    <button
                                        v-if="$page.props.auth?.user?.is_admin"
                                        type="button"
                                        class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                                        @click="openDeleteModal(book)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    :meta="books"
                    route="/books"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>

        <ConfirmModal
            ref="confirmModal"
            title="Delete book"
            :message="`Are you sure you want to delete '${bookToDelete?.name ?? ''}'? This action cannot be undone.`"
            confirmText="Yes, delete"
            cancelText="Cancel"
            :danger="true"
            :confirm-disabled="deleteBlockedByActiveRequest"
            @confirm="confirmDelete"
            @cancel="onCancelDelete"
        >
            <div v-if="deleteBlockedByActiveRequest" class="alert alert-warning mt-3">
                <span>
                    This book cannot be deleted because it has an active request. Complete/cancel the request first.
                </span>
            </div>
        </ConfirmModal>
    </PublicLayout>
</template>
