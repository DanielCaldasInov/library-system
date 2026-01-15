<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"

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
</script>

<template>
    <app-layout title="Books">
        <div class="p-6 bg-[#1b1b18] flex flex-col items-center">

            <h1 class="text-3xl font-bold mb-6 bg-gray-800 px-6 py-4 rounded-lg">
                Books
            </h1>

            <div class="w-full max-w-6xl mx-auto">

                <SearchForm
                    action="/books"
                    :filters="filters"
                    :options="searchOptions"
                />

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
                            <th @click="sortBy('publisher')" class="cursor-pointer">
                                Publisher {{ sort === 'publisher' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                            <th @click="sortBy('author')" class="cursor-pointer">
                                Authors {{ sort === 'author' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                            <th @click="sortBy('price')" class="cursor-pointer text-right">
                                Price {{ sort === 'price' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="book in rows" :key="book.id">
                            <td>
                                <img class='w-24 h-24' :src="book.cover" alt="Book Cover">
                            </td>
                            <td>{{ book.name }}</td>
                            <td>{{ book.publisher.name }}</td>
                            <td class="truncate max-w-sm">
                                {{ book.authors.map(a => a.name).join(', ') }}
                            </td>
                            <td class="text-right">
                                ${{ Number(book.price).toFixed(2) }}
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
    </app-layout>
</template>
