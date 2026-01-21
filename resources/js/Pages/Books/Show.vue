<script setup>
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { ref } from "vue"

const props = defineProps({
    book: Object,
})

const imgSrc = (path) => path || null

const confirmModal = ref(null)

const openDeleteModal = () => {
    confirmModal.value?.open()
}

const confirmDelete = () => {
    router.delete(route('books.destroy', props.book.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmModal.value?.close()
        },
    })
}
</script>

<template>
    <PublicLayout title="Book Details">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-3xl mx-auto bg-gray-800 rounded-lg p-6">

                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">
                        Book Details
                    </h1>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('books.index')"
                            class="btn btn-ghost"
                        >
                            Back
                        </Link>

                        <button
                            v-if="$page.props.auth?.user?.is_admin"
                            type="button"
                            class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                            @click="openDeleteModal"
                        >
                            Delete
                        </button>

                        <Link
                            v-if="$page.props.auth?.user?.is_admin"
                            :href="route('books.edit', book.id)"
                            class="btn bg-gray-700 hover:bg-black"
                        >
                            Edit
                        </Link>
                    </div>
                </div>

                <div class="flex justify-center mb-6">
                    <div class="w-56 h-56 rounded-lg bg-gray-900/80 overflow-hidden flex items-center justify-center">
                        <img
                            v-if="book.cover"
                            :src="imgSrc(book.cover)"
                            :alt="`${book.name} cover`"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-xs opacity-60">
                            No cover
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Name</span>
                        <span class="text-white font-bold text-lg">
                            {{ book.name }}
                        </span>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">ISBN</span>
                        <span class="text-white font-mono">
                            {{ book.isbn }}
                        </span>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Price</span>
                        <span class="text-white">
                            ${{ Number(book.price ?? 0).toFixed(2) }}
                        </span>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Bibliography</span>
                        <span class="text-white whitespace-pre-line">
                            {{ book.bibliography || '—' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="bg-gray-900/80 rounded-lg p-4">
                        <h2 class="text-lg font-bold mb-3">Publisher</h2>

                        <div v-if="book.publisher" class="bg-gray-800 rounded-lg p-4 hover:bg-gray-700 transition">
                            <Link
                                :href="route('publishers.show', book.publisher.id)"
                                class="flex items-center gap-4"
                            >
                                <div class="w-14 h-14 rounded-lg bg-gray-900/80 overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="book.publisher.logo"
                                        :src="imgSrc(book.publisher.logo)"
                                        :alt="`${book.publisher.name} logo`"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-xs opacity-60">
                                        No logo
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-white font-bold text-base truncate">
                                        {{ book.publisher.name }}
                                    </p>
                                    <p class="text-xs opacity-60 mt-1">
                                        View publisher details
                                    </p>
                                </div>
                            </Link>
                        </div>

                        <p v-else class="opacity-60">
                            No publisher assigned
                        </p>
                    </div>

                    <div class="bg-gray-900/80 rounded-lg p-4">
                        <h2 class="text-lg font-bold mb-3">Authors</h2>

                        <div v-if="book.authors?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Link
                                v-for="author in book.authors"
                                :key="author.id"
                                :href="route('authors.show', author.id)"
                                class="bg-gray-800 rounded-lg p-4 hover:bg-gray-700 transition flex items-center gap-4"
                            >
                                <div class="w-14 h-14 rounded-full bg-gray-900/80 overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="author.photo"
                                        :src="author.photo"
                                        :alt="`${author.name} photo`"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-xs opacity-60">
                                        No photo
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-white font-bold text-base truncate">
                                        {{ author.name }}
                                    </p>
                                    <p class="text-xs opacity-60 mt-1">
                                        View author details
                                    </p>
                                </div>
                            </Link>
                        </div>

                        <p v-else class="opacity-60">
                            No authors assigned
                        </p>
                    </div>

                </div>

            </div>
        </div>

        <ConfirmModal
            ref="confirmModal"
            title="Delete book"
            :message="`Are you sure you want to delete '${book.name}'? This action cannot be undone.`"
            confirmText="Yes, delete"
            cancelText="Cancel"
            :danger="true"
            @confirm="confirmDelete"
        />
    </PublicLayout>
</template>
