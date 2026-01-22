<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { Link, useForm } from "@inertiajs/vue3"
import { computed } from "vue"

const props = defineProps({
    availableBooks: Array,
    activeCount: Number,
    maxActive: Number,
})

const form = useForm({
    book_id: "",
})

const limitReached = computed(() => (props.activeCount ?? 0) >= (props.maxActive ?? 3))

const selectedBook = computed(() => {
    const id = Number(form.book_id)
    return props.availableBooks?.find(b => b.id === id) ?? null
})

const coverUrl = (book) => book?.cover ? book.cover : "/images/book-placeholder.png"

const submit = () => {
    form.post(route("requests.store"))
}
</script>

<template>
    <public-layout title="New Request">
        <div class="p-6 flex flex-col items-center">

            <div class="w-full max-w-4xl mx-auto bg-gray-800 rounded-lg p-6 space-y-6">

                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold">New Request</h1>

                    <Link href="/requests" class="btn btn-ghost">
                        Back
                    </Link>
                </div>

                <!-- Limit info -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-900/70 rounded-lg px-4 py-3">
                    <div class="text-sm opacity-80">
                        Active requests:
                        <span class="font-semibold">{{ activeCount }}</span>
                        /
                        <span class="font-semibold">{{ maxActive }}</span>
                    </div>

                    <div v-if="limitReached" class="alert alert-warning py-2">
                        <span>
                            You already have {{ activeCount }} active request(s).
                            Return at least one book to create a new request.
                        </span>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Book select -->
                    <div class="bg-gray-900/80 rounded px-3 py-3">
                        <label class="label">
                            <span class="label-text text-white font-semibold">
                                Select an available book
                            </span>
                        </label>

                        <select
                            v-model="form.book_id"
                            class="select select-bordered w-full text-black"
                            :disabled="limitReached"
                        >
                            <option value="" disabled>
                                Choose a book
                            </option>

                            <option
                                v-for="book in availableBooks"
                                :key="book.id"
                                :value="book.id"
                            >
                                {{ book.name }} — {{ book.publisher?.name ?? 'Unknown publisher' }}
                            </option>
                        </select>

                        <p v-if="form.errors.book_id" class="text-red-400 text-sm mt-1">
                            {{ form.errors.book_id }}
                        </p>

                        <p v-if="!availableBooks?.length" class="text-sm opacity-70 mt-2">
                            No available books right now.
                        </p>
                    </div>

                    <!-- Preview selected book -->
                    <div
                        v-if="selectedBook"
                        class="bg-gray-900/80 rounded-lg p-4 flex flex-col sm:flex-row gap-4"
                    >
                        <img
                            :src="coverUrl(selectedBook)"
                            alt="Book cover"
                            class="w-28 h-28 object-contain bg-white rounded"
                        />

                        <div class="flex-1">
                            <h2 class="text-lg font-semibold">
                                {{ selectedBook.name }}
                            </h2>

                            <p class="text-sm opacity-70 mt-1">
                                Publisher: <span class="font-medium">{{ selectedBook.publisher?.name ?? '-' }}</span>
                            </p>

                            <p class="text-sm opacity-70 mt-1">
                                Authors:
                                <span class="font-medium">
                                    {{ (selectedBook.authors ?? []).map(a => a.name).join(', ') || '-' }}
                                </span>
                            </p>

                            <div class="mt-3 text-xs opacity-60">
                                Due date will be set automatically to <span class="font-semibold">5 days</span> after the request.
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                            @click="form.reset()"
                            :disabled="form.processing"
                        >
                            Clear
                        </button>

                        <button
                            type="submit"
                            class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                            :disabled="form.processing || limitReached || !availableBooks?.length"
                        >
                            {{ form.processing ? "Submitting..." : "Submit Request" }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </public-layout>
</template>
