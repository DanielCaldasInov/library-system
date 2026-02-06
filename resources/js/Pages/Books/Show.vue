<script setup>
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import Pagination from "@/Components/Pagination.vue"
import { ref, computed } from "vue"

const props = defineProps({
    book: Object,
    isAvailable: Boolean,
    bookRequests: Object,
    bookRequestsCount: Number,
    reviews: Object,
    relatedBooks: Array,
    isSubscribedAvailability: Boolean,
})

const page = usePage()

const imgSrc = (path) => path || null

const confirmDeleteModal = ref(null)
const confirmRequestModal = ref(null)

const openDeleteModal = () => {
    confirmDeleteModal.value?.open()
}

const confirmDelete = () => {
    router.delete(route('books.destroy', props.book.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDeleteModal.value?.close()
        },
    })
}

const isLoggedIn = computed(() => !!page.props.auth?.user)
const isAdmin = computed(() => !!page.props.auth?.user?.is_admin)
const canRequest = computed(() => isLoggedIn.value && props.isAvailable)
const notifyWhenAvailable = () => {
    router.post(route("books.availability-alert.store", props.book.id), {}, {
        preserveScroll: true,
    })
}

const openRequestModal = () => {
    requestForm.clearErrors()
    confirmRequestModal.value?.open()
}

const requestForm = useForm({
    book_id: props.book.id,
})

const requestLimitMessage = computed(() => requestForm.errors?.book_id || "")

const requestBook = () => {
    requestForm.post(route('requests.store'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmRequestModal.value?.close()
            requestForm.reset()
            requestForm.clearErrors()
        },
    })
}

const isbnValue = computed(() => {
    const fmt = props.book ?? {}
    return fmt.ISBN ?? '—'
})

const renderStars = (rating) => {
    const stars = []
    for (let i = 1; i <= 5; i++) {
        stars.push(i <= rating)
    }
    return stars
}


const fmtDate = (v) => (v ? new Date(v).toLocaleDateString() : "—")

const statusBadge = (status) => {
    switch (status) {
        case "active":
            return "badge-success"
        case "awaiting_confirmation":
            return "badge-warning"
        case "completed":
            return "badge-neutral"
        case "canceled":
            return "badge-error"
        default:
            return "badge-ghost"
    }
}

const statusLabel = (status) => {
    switch (status) {
        case "active":
            return "Active"
        case "awaiting_confirmation":
            return "Awaiting confirmation"
        case "completed":
            return "Completed"
        case "canceled":
            return "Canceled"
        default:
            return status ?? "—"
    }
}

const totalRequests = computed(() => {
    return props.bookRequestsCount ?? props.bookRequests?.total ?? 0
})
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
                            v-if="isLoggedIn && !canRequest"
                            type="button"
                            class="btn py-2 px-2"
                            :class="props.isSubscribedAvailability
                                ? 'bg-gray-600 cursor-not-allowed'
                                : 'bg-[#5754E8] hover:bg-[#3c39e3]'"
                            :disabled="props.isSubscribedAvailability"
                            @click="!props.isSubscribedAvailability && notifyWhenAvailable()"
                        >
                            {{ props.isSubscribedAvailability
                            ? "We'll notify you"
                            : "Notify me when available" }}
                        </button>

                        <button
                            v-if="isLoggedIn"
                            type="button"
                            class="btn py-2 px-2"
                            :class="props.isAvailable
                                ? 'bg-[#5754E8] hover:bg-[#3c39e3]'
                                : 'bg-gray-600 cursor-not-allowed'"
                            :disabled="!props.isAvailable"
                            @click="props.isAvailable && openRequestModal()"
                        >
                            {{ props.isAvailable ? 'Request this book' : 'Book unavailable' }}
                        </button>

                        <button
                            v-if="isAdmin"
                            type="button"
                            class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                            @click="openDeleteModal"
                        >
                            Delete
                        </button>

                        <Link
                            v-if="isAdmin"
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Name</span>
                        <span class="text-white font-bold text-lg">
                            {{ book.name }}
                        </span>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">ISBN</span>
                        <span class="text-white font-mono">
                            {{ isbnValue }}
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Price</span>
                        <span class="text-white">
                            ${{ Number(book.price ?? 0).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-3 py-3">
                        <span class="text-sm opacity-60">Bibliography</span>
                        <span class="text-white whitespace-pre-line">
                            {{ book.bibliography || '—' }}
                        </span>
                    </div>
                </div>

                <div class="bg-gray-900/80 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold">Requests history</h2>
                        <span class="text-sm opacity-70">
                            Total: {{ totalRequests }}
                        </span>
                    </div>

                    <div v-if="!(bookRequests?.data?.length)" class="opacity-60">
                        No requests for this book
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Status</th>
                                <th>Withdrawn</th>
                                <th>Returned</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr v-for="r in bookRequests.data" :key="r.id">
                                <td class="font-mono">
                                    {{ r.number }}
                                </td>

                                <td>
                                    <span class="badge" :class="statusBadge(r.status)">
                                        {{ statusLabel(r.status) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ fmtDate(r.requested_at) }}
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ fmtDate(r.returned_at) }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        v-if="bookRequests?.data?.length && (bookRequests.last_page ?? 1) > 1"
                        :meta="bookRequests"
                        :filters="{ reviews_page: reviews?.current_page ?? 1 }"
                        :route="route('books.show', book.id)"
                    />
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

                    <div class="bg-gray-900/80 rounded-lg p-4 mt-6 col-span-2">
                        <h2 class="text-lg font-bold mb-4">
                            Reviews
                        </h2>

                        <div v-if="!reviews?.data?.length" class="opacity-70">
                            There are no reviews for this book yet.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="review in reviews.data"
                                :key="review.id"
                                class="bg-gray-800 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="review.user?.profile_photo_url"
                                            class="w-8 h-8 rounded-full object-cover bg-gray-700"
                                         alt="Profile Photo"/>
                                        <span class="font-semibold text-white">
                                            {{ review.user?.name ?? 'Anonymous' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <span
                                            v-for="(filled, i) in renderStars(review.rating)"
                                            :key="i"
                                            class="text-lg"
                                            :class="filled ? 'text-yellow-400' : 'text-gray-500'"
                                        >
                                            ★
                                        </span>
                                    </div>
                                </div>

                                <p class="whitespace-pre-line opacity-80">
                                    {{ review.comment || '—' }}
                                </p>
                            </div>
                            <Pagination
                                v-if="reviews?.data?.length && (reviews.last_page ?? 1) > 1"
                                :meta="reviews"
                                :filters="{ requests_page: bookRequests?.current_page ?? 1 }"
                                :route="route('books.show', book.id)"
                            />
                        </div>
                    </div>
                    <div class="bg-gray-900/80 rounded-lg p-4 mt-6 col-span-2">
                        <h2 class="text-lg font-bold mb-4">Related books</h2>

                        <div v-if="!relatedBooks?.length" class="opacity-70">
                            No related books found.
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Link
                                v-for="item in relatedBooks"
                                :key="item.book.id"
                                :href="route('books.show', item.book.id)"
                                class="bg-gray-800 rounded-lg p-4 hover:bg-gray-700 transition"
                            >
                                <div class="flex gap-3">
                                    <div class="w-14 h-14 bg-gray-900/80 rounded overflow-hidden flex items-center justify-center">
                                        <img v-if="item.book.cover" :src="item.book.cover" class="w-full h-full object-cover"  alt="Book Cover"/>
                                        <span v-else class="text-xs opacity-60">No cover</span>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="font-bold truncate">{{ item.book.name }}</p>
                                        <p class="text-xs opacity-70 truncate">
                                            {{ (item.book.authors ?? []).map(a => a.name).join(', ') || '—' }}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <ConfirmModal
            ref="confirmRequestModal"
            title="Request this book"
            :message="`Create a new request for '${book.name}'? Due date will be set automatically.`"
            confirmText="Yes, request"
            cancelText="Cancel"
            :danger="false"
            :confirmDisabled="requestForm.processing"
            @confirm="requestBook"
            @cancel="requestForm.clearErrors()"
        >
            <div v-if="requestLimitMessage" class="alert alert-warning mt-3">
                <span>
                    {{ requestLimitMessage }}
                </span>
            </div>
        </ConfirmModal>

        <ConfirmModal
            ref="confirmDeleteModal"
            title="Delete book"
            :message="`Are you sure you want to delete '${book.name}'? This action cannot be undone.`"
            confirmText="Yes, delete"
            cancelText="Cancel"
            :danger="true"
            @confirm="confirmDelete"
        />
    </PublicLayout>
</template>
