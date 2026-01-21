<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { ref, computed } from "vue"

const props = defineProps({
    authors: Array,
    publishers: Array,
})

const coverPreview = ref(null)
const authorSearch = ref("")

const form = useForm({
    name: '',
    isbn: '',
    price: '',
    bibliography: '',
    publisher_id: '',
    authors: [],
    cover: null,
})

const filteredAuthors = computed(() => {
    const term = authorSearch.value.trim().toLowerCase()
    if (!term) return props.authors

    return props.authors.filter(a =>
        (a.name ?? "").toLowerCase().includes(term)
    )
})

const toggleAuthor = (authorId) => {
    const i = form.authors.indexOf(authorId)
    if (i === -1) form.authors.push(authorId)
    else form.authors.splice(i, 1)
}

const onCoverChange = (e) => {
    const file = e.target.files?.[0] ?? null
    form.cover = file
    coverPreview.value = file ? URL.createObjectURL(file) : null
}

const submit = () => {
    form.post(route('books.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <PublicLayout title="Create Book">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">

                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">
                        Create Book
                    </h1>

                    <Link
                        :href="route('books.index')"
                        class="btn btn-ghost"
                    >
                        Back
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-4">

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Name
                            </span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="Book name..."
                        />

                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                ISBN
                            </span>
                        </label>

                        <input
                            v-model="form.isbn"
                            type="text"
                            inputmode="numeric"
                            pattern="\d{13}"
                            maxlength="13"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="13 numerics characters..."
                        />

                        <p v-if="form.errors.isbn" class="text-red-400 text-sm mt-1">
                            {{ form.errors.isbn }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Price
                            </span>
                        </label>

                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="0.00"
                        />

                        <p v-if="form.errors.price" class="text-red-400 text-sm mt-1">
                            {{ form.errors.price }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Publisher
                            </span>
                        </label>

                        <select
                            v-model="form.publisher_id"
                            class="select select-bordered w-full text-black rounded-lg"
                        >
                            <option value="">Select a publisher...</option>
                            <option
                                v-for="publisher in publishers"
                                :key="publisher.id"
                                :value="publisher.id"
                            >
                                {{ publisher.name }}
                            </option>
                        </select>

                        <p v-if="form.errors.publisher_id" class="text-red-400 text-sm mt-1">
                            {{ form.errors.publisher_id }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Authors
                            </span>
                        </label>

                        <input
                            v-model="authorSearch"
                            type="text"
                            class="input input-bordered w-full text-black rounded-lg mb-3"
                            placeholder="Search author by name..."
                        />

                        <p class="text-xs opacity-70 mb-2">
                            Selected: <span class="font-bold">{{ form.authors.length }}</span>
                        </p>

                        <div class="max-h-52 overflow-y-auto rounded-lg border border-gray-700 p-2 bg-gray-950/40">
                            <div
                                v-if="filteredAuthors.length === 0"
                                class="text-sm opacity-60 p-2"
                            >
                                No authors found.
                            </div>

                            <label
                                v-for="author in filteredAuthors"
                                :key="author.id"
                                class="flex items-center gap-3 px-2 py-2 rounded hover:bg-gray-800 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="form.authors.includes(author.id)"
                                    @change="toggleAuthor(author.id)"
                                />
                                <span class="text-white">
                                    {{ author.name }}
                                </span>
                            </label>
                        </div>

                        <p v-if="form.errors.authors" class="text-red-400 text-sm mt-1">
                            {{ form.errors.authors }}
                        </p>

                        <p class="text-xs opacity-60 mt-1">
                            Select at least one author.
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Bibliography
                            </span>
                        </label>

                        <textarea
                            v-model="form.bibliography"
                            class="textarea textarea-bordered w-full text-black rounded-lg"
                            rows="4"
                            placeholder="Short description..."
                        />

                        <p v-if="form.errors.bibliography" class="text-red-400 text-sm mt-1">
                            {{ form.errors.bibliography }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Cover
                            </span>
                        </label>

                        <input
                            type="file"
                            class="file-input file-input-bordered w-full text-white rounded-lg"
                            accept="image/*"
                            @change="onCoverChange"
                        />

                        <p v-if="form.errors.cover" class="text-red-400 text-sm mt-1">
                            {{ form.errors.cover }}
                        </p>

                        <p class="text-xs opacity-60 mt-1">
                            Optional. Upload a cover image (jpg, png, webp).
                        </p>

                        <div v-if="coverPreview" class="mt-3">
                            <img
                                :src="coverPreview"
                                alt="Cover preview"
                                class="w-32 h-32 object-cover rounded"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="btn btn-ghost hover:bg-black py-2 px-2"
                            @click="form.reset(); coverPreview = null; authorSearch = ''"
                        >
                            Clear
                        </button>

                        <button
                            type="submit"
                            class="btn bg-gray-700 hover:bg-black py-2 px-2"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </PublicLayout>
</template>
