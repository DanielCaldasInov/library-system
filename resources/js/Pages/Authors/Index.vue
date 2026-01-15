<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"

defineProps({
    authors: Object,
    filters: Object,
    sort: String,
    direction: String,
})

const searchOptions = [
    { value: 'name', label: 'Name' },
    { value: 'book', label: 'Book' },
]
</script>

<template>
    <app-layout title="Authors">
        <div class="p-6 bg-[#1b1b18] flex flex-col items-center">

            <h1 class="text-3xl font-bold mb-6 bg-gray-800 px-6 py-4 rounded-lg">
                Authors
            </h1>

            <div class="w-full max-w-6xl mx-auto">

                <!-- Search -->
                <SearchForm
                    action="/authors"
                    :filters="filters"
                    :options="searchOptions"
                />

                <!-- Table -->
                <DataTable
                    :data="authors"
                    :sort="sort"
                    :direction="direction"
                    route="/authors"
                >
                    <template #head="{ sortBy, sort, direction }">
                        <tr>
                            <th>Photo</th>

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
                        <tr v-for="author in rows" :key="author.id">

                            <td>
                                <img
                                    :src="author.photo || '/images/avatar-placeholder.png'"
                                    alt="Author photo"
                                    class="w-24 h-24 rounded-full object-cover"
                                />
                            </td>

                            <td>{{ author.name }}</td>

                            <td class="max-w-md truncate"
                                :title="author.books.map(b => b.name).join(', ')">
                                {{ author.books.map(b => b.name).join(', ') }}
                            </td>

                        </tr>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <Pagination
                    :meta="authors"
                    route="/authors"
                    :filters="{ ...filters, sort, direction }"
                />

            </div>
        </div>
    </app-layout>
</template>
