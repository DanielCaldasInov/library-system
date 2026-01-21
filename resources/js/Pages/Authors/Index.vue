<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"

defineProps({
    authors: Object,
    filters: Object,
    sort: String,
    direction: String,
})

const searchOptions = [
    { value: 'name', label: 'Name' },
    { value: 'book', label: 'Books' },
]

const modalRefs = ref({})

const setModalRef = (id) => (el) => {
    if (el) modalRefs.value[id] = el
}

const openDelete = (authorId) => {
    modalRefs.value[authorId]?.open()
}

const closeDelete = (authorId) => {
    modalRefs.value[authorId]?.close()
}

const destroyAuthor = (authorId) => {
    router.delete(route('authors.destroy', authorId), {
        onFinish: () => closeDelete(authorId),
    })
}

const deleteMessage = (authorName) => {
    return `This action cannot be undone. "${authorName}" will be permanently deleted.`
}
</script>

<template>
    <public-layout title="Authors">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Authors
                </h1>

                <Link
                    v-if="$page.props.auth?.user?.is_admin"
                    href="/authors/create"
                    class="btn btn-primary"
                >
                    Create Author
                </Link>
            </div>

            <div class="w-full max-w-6xl mx-auto">
                <SearchForm
                    action="/authors"
                    :filters="filters"
                    :options="searchOptions"
                />

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

                            <th>Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="author in rows" :key="author.id">
                            <td>
                                <img
                                    :src="author.photo ? author.photo : '/images/author-placeholder.png'"
                                    alt="Author photo"
                                    class="w-24 h-24 object-cover rounded"
                                />
                            </td>

                            <td>{{ author.name }}</td>

                            <td
                                class="max-w-md truncate"
                                :title="author.books.map(b => b.name).join(', ')"
                            >
                                {{ author.books.map(b => b.name).join(', ') }}
                            </td>

                            <td class="flex gap-2">
                                <Link
                                    :href="route('authors.show', author.id)"
                                    class="btn hover:bg-[#5754E8]"
                                >
                                    Details
                                </Link>

                                <button
                                    v-if="$page.props.auth?.user?.is_admin"
                                    type="button"
                                    class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                                    @click="openDelete(author.id)"
                                >
                                    Delete
                                </button>

                                <ConfirmModal
                                    v-if="$page.props.auth?.user?.is_admin"
                                    :ref="setModalRef(author.id)"
                                    title="Delete author?"
                                    :message="deleteMessage(author.name)"
                                    confirm-text="Yes, delete"
                                    cancel-text="Cancel"
                                    danger
                                    :confirm-disabled="(author.books?.length ?? 0) > 0"
                                    @confirm="destroyAuthor(author.id)"
                                    @cancel="closeDelete(author.id)"
                                >
                                    <div
                                        v-if="(author.books?.length ?? 0) > 0"
                                        class="alert alert-warning mt-3"
                                    >
                    <span>
                      This author has {{ author.books.length }} book(s) associated.
                      Remove or reassign them before deleting.
                    </span>
                                    </div>
                                </ConfirmModal>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    :meta="authors"
                    route="/authors"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>
    </public-layout>
</template>
