<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    books: Object,
    filters: Object,
    sort: String,
    direction: String,
})

const form = useForm({
    filter: props.filters?.filter ?? 'name',
    search: props.filters?.search ?? '',
})

const sortBy = (column) => {
    router.get('/books', {
        ...form.data(),
        sort: column,
        direction: props.sort === column && props.direction === 'asc'
            ? 'desc'
            : 'asc',
    }, {
        preserveState: true,
        replace: true,
    })
}
</script>

<template>
    <app-layout title="Books">
        <div class="p-6 bg-[#1b1b18] flex flex-col items-center">

            <h1 class="text-3xl font-bold mb-6 bg-gray-800 px-6 py-4 rounded-lg">
                Books
            </h1>

            <div class="w-full max-w-6xl mx-auto">

                <form
                    @submit.prevent="form.get('/books', {
                    preserveState: true,
                    replace: true,
                    })"
                    class="bg-gray-800 p-4 mb-6 rounded-lg"
                >
                    <div class="flex flex-col md:flex-row gap-2">

                        <select v-model="form.filter" class="select w-48 text-black">
                            <option value="name" selected>Name</option>
                            <option value="author">Author</option>
                            <option value="publisher">Publisher</option>
                        </select>

                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Type to search..."
                            class="input flex-1 text-black"
                        />

                        <button type="submit" class="btn btn-neutral hover:bg-black px-2">
                            Search
                        </button>

                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="table bg-gray-800 w-full">
                        <thead>
                        <tr>
                            <th @click="sortBy('name')" class="cursor-pointer">
                                Name
                                <span v-if="sort === 'name'">
                                    {{ direction === 'asc' ? '▲' : '▼' }}
                                </span>
                            </th>

                            <th @click="sortBy('publisher')" class="cursor-pointer">
                                Publisher
                                <span v-if="sort === 'publisher'">
                                    {{ direction === 'asc' ? '▲' : '▼' }}
                                </span>
                            </th>

                            <th @click="sortBy('author')" class="cursor-pointer">
                                Authors
                                <span v-if="sort === 'author'">
                                    {{ direction === 'asc' ? '▲' : '▼' }}
                                </span>
                            </th>

                            <th @click="sortBy('price')" class="cursor-pointer text-right">
                                Price
                                <span v-if="sort === 'price'">
                                    {{ direction === 'asc' ? '▲' : '▼' }}
                                </span>
                            </th>

                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="book in books.data" :key="book.id">
                            <td>{{ book.name }}</td>
                            <td>{{ book.publisher.name }}</td>
                            <td class="max-w-sm truncate"
                                :title="book.authors.map(a => a.name).join(', ')">
                                {{ book.authors.map(a => a.name).join(', ') }}
                            </td>
                            <td class="text-right">
                                ${{ Number(book.price).toFixed(2) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-center  space-x-2 bg-gray-800 px-4 rounded-lg w-auto">
                    <button
                        :disabled="books.current_page === 1"
                        @click="$inertia.get(`${books.path}?page=${books.current_page - 1}`)"
                        class="btn-sm rounded-lg hover:bg-black px-2"
                    >
                        Previous
                    </button>

                    <button
                        v-for="page in Array.from({ length: books.last_page }, (_, i) => i + 1)"
                        :key="page"
                        :class="['btn-sm rounded-lg py-4 px-2 hover:bg-black']"
                        @click="$inertia.get(`${books.path}?page=${page}`)"
                    >
                        {{ page }}
                    </button>

                    <button
                        :disabled="books.current_page === books.last_page"
                        @click="$inertia.get(`${books.path}?page=${books.current_page + 1}`)"
                        class="btn-sm rounded-lg hover:bg-black px-2 border-0"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </app-layout>
</template>
