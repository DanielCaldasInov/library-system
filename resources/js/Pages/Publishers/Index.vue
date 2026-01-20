<script setup>
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineProps({
    publishers: Object,
    filters: Object,
    sort: String,
    direction: String,
})

const searchOptions = [
    { value: 'name', label: 'Name' },
    { value: 'book', label: 'Books' },
]
</script>

<template>
    <public-layout title="Publishers">
        <div class="p-6 flex flex-col items-center">

            <h1 class="text-3xl font-bold mb-6 bg-gray-800 px-6 py-4 rounded-lg">
                Publishers
            </h1>

            <div class="w-full max-w-6xl mx-auto">

                <!-- Search -->
                <SearchForm
                    action="/publishers"
                    :filters="filters"
                    :options="searchOptions"
                />

                <!-- Table -->
                <DataTable
                    :data="publishers"
                    :sort="sort"
                    :direction="direction"
                    route="/publishers"
                >
                    <template #head="{ sortBy, sort, direction }">
                        <tr>
                            <th>Logo</th>

                            <th @click="sortBy('name')" class="cursor-pointer">
                                Name
                                {{ sort === 'name' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th @click="sortBy('books')" class="cursor-pointer">
                                Books
                                {{ sort === 'books' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="publisher in rows" :key="publisher.id">

                            <td>
                                <img
                                    :src="publisher.logo || '/images/publisher-placeholder.png'"
                                    alt="Publisher logo"
                                    class="w-24 h-24 object-contain"
                                />
                            </td>

                            <td>{{ publisher.name }}</td>

                            <td class="max-w-md truncate"
                                :title="publisher.books.map(b => b.name).join(', ')">
                                {{ publisher.books.map(b => b.name).join(', ') }}
                            </td>

                        </tr>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <Pagination
                    :meta="publishers"
                    route="/publishers"
                    :filters="{ ...filters, sort, direction }"
                />

            </div>
        </div>
    </public-layout>
</template>
