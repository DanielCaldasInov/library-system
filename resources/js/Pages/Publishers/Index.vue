<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"

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

const modalRefs = ref({})

const setModalRef = (id) => (el) => {
    if (el) modalRefs.value[id] = el
}

const openDelete = (publisherId) => {
    modalRefs.value[publisherId]?.open()
}

const closeDelete = (publisherId) => {
    modalRefs.value[publisherId]?.close()
}

const destroyPublisher = (publisherId) => {
    router.delete(route('publishers.destroy', publisherId), {
        onFinish: () => closeDelete(publisherId),
    })
}

const deleteMessage = (publisherName) => {
    return `This action cannot be undone. "${publisherName}" will be permanently deleted.`
}
</script>

<template>
    <public-layout title="Publishers">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Publishers
                </h1>

                <Link
                    v-if="$page.props.auth?.user?.is_admin"
                    href="/publishers/create"
                    class="btn btn-primary"
                >
                    Create Publisher
                </Link>
            </div>

            <div class="w-full max-w-6xl mx-auto">
                <SearchForm
                    action="/publishers"
                    :filters="filters"
                    :options="searchOptions"
                />

                <DataTable
                    :data="publishers"
                    :sort="sort"
                    :direction="direction"
                    route="/publishers"
                >
                    <template #head="{ sortBy, sort, direction }">
                        <tr>
                            <th>Logo</th>

                            <th @click="sortBy('name')" class="cursor-pointer hover:bg-gray-700 transition-colors">
                                Name
                                {{ sort === 'name' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th @click="sortBy('books')" class="cursor-pointer hover:bg-gray-700 transition-colors">
                                Books
                                {{ sort === 'books' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>

                            <th>Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="publisher in rows" :key="publisher.id">
                            <td>
                                <img
                                    :src="publisher.logo ? publisher.logo : '/images/publisher-placeholder.png'"
                                    alt="Publisher logo"
                                    class="w-24 h-24 object-contain"
                                />
                            </td>

                            <td>{{ publisher.name }}</td>

                            <td
                                class="max-w-md truncate"
                                :title="publisher.books.map(b => b.name).join(', ')"
                            >
                                {{ publisher.books.map(b => b.name).join(', ') }}
                            </td>

                            <td class="flex gap-2">
                                <Link
                                    :href="route('publishers.show', publisher.id)"
                                    class="btn hover:bg-[#5754E8]"
                                >
                                    Details
                                </Link>

                                <button
                                    v-if="$page.props.auth?.user?.is_admin"
                                    type="button"
                                    class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                                    @click="openDelete(publisher.id)"
                                >
                                    Delete
                                </button>

                                <ConfirmModal
                                    v-if="$page.props.auth?.user?.is_admin"
                                    :ref="setModalRef(publisher.id)"
                                    title="Delete publisher?"
                                    :message="deleteMessage(publisher.name)"
                                    confirm-text="Yes, delete"
                                    cancel-text="Cancel"
                                    danger
                                    :confirm-disabled="(publisher.books?.length ?? 0) > 0"
                                    @confirm="destroyPublisher(publisher.id)"
                                    @cancel="closeDelete(publisher.id)"
                                >
                                    <div
                                        v-if="(publisher.books?.length ?? 0) > 0"
                                        class="alert alert-warning mt-3"
                                    >
                    <span>
                      This publisher has {{ publisher.books.length }} book(s) associated.
                      Remove or reassign them before deleting.
                    </span>
                                    </div>
                                </ConfirmModal>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    :meta="publishers"
                    route="/publishers"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>
    </public-layout>
</template>
