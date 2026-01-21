<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { Link, router } from "@inertiajs/vue3"
import { ref, computed } from "vue"

const props = defineProps({
    author: Object,
})

const confirmDelete = ref(null)

const hasBooks = computed(() => (props.author.books?.length ?? 0) > 0)

const deleteMessage = computed(() => {
    return `This action cannot be undone. "${props.author.name}" will be permanently deleted.`
})

const openDelete = () => confirmDelete.value?.open()
const closeDelete = () => confirmDelete.value?.close()

const destroyAuthor = () => {
    router.delete(route("authors.destroy", props.author.id), {
        onFinish: () => closeDelete(),
    })
}

const photoUrl = computed(() =>
    props.author.photo ? props.author.photo : "/images/author-placeholder.png"
)

const coverUrl = (book) =>
    book.cover ? book.cover : "/images/book-placeholder.png"
</script>

<template>
    <public-layout :title="author.name">
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="flex items-center gap-6 bg-gray-800 p-6 rounded-lg">
                <img
                    :src="photoUrl"
                    class="w-32 h-32 object-cover bg-white rounded"
                    alt="Author photo"
                />

                <div class="flex-1">
                    <h1 class="text-3xl font-bold">
                        {{ author.name }}
                    </h1>

                    <p class="opacity-70 mt-1">
                        {{ author.books.length }} books
                    </p>
                </div>

                <div v-if="$page.props.auth?.user?.is_admin" class="flex gap-2">
                    <Link
                        :href="route('authors.edit', author.id)"
                        class="btn bg-gray-700 hover:bg-gray-900"
                    >
                        Edit
                    </Link>

                    <button
                        class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                        @click="openDelete"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">
                    Books by this author
                </h2>

                <div v-if="author.books.length === 0" class="opacity-60">
                    No books registered for this author.
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                >
                    <div
                        v-for="book in author.books"
                        :key="book.id"
                        class="card bg-base-100 shadow"
                    >
                        <figure>
                            <img
                                :src="coverUrl(book)"
                                class="h-40 w-auto object-contain bg-white"
                                alt="Book cover"
                            />
                        </figure>

                        <div class="card-body p-4">
                            <h3 class="font-semibold truncate">
                                {{ book.name }}
                            </h3>

                            <p class="text-sm opacity-70 truncate">
                                {{ book.publisher?.name ?? '—' }}
                            </p>

                            <div class="card-actions justify-end mt-2">
                                <Link
                                    :href="route('books.show', book.id)"
                                    class="btn btn-xs btn-outline"
                                >
                                    Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmModal
                ref="confirmDelete"
                title="Delete author?"
                :message="deleteMessage"
                confirm-text="Yes, delete"
                cancel-text="Cancel"
                danger
                :confirm-disabled="hasBooks"
                @confirm="destroyAuthor"
                @cancel="closeDelete"
            >
                <div v-if="hasBooks" class="alert alert-warning mt-3">
          <span>
            This author has {{ author.books.length }} book(s) associated.
            Remove or reassign them before deleting.
          </span>
                </div>
            </ConfirmModal>
        </div>
    </public-layout>
</template>
