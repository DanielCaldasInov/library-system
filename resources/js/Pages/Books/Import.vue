<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import Pagination from "@/Components/Pagination.vue"
import { Link, router, useForm, useRemember } from "@inertiajs/vue3"
import { computed } from "vue"

const props = defineProps({
    apiOk: Boolean,
    filters: Object,
    results: Array,
    totalItems: Number,
    meta: Object,
})

const form = useForm({
    title: props.filters?.title ?? '',
    author: props.filters?.author ?? '',
    publisher: props.filters?.publisher ?? '',
    isbn: props.filters?.isbn ?? '',
    per_page: props.filters?.per_page ?? props.meta?.per_page ?? 20,
})

const selected = useRemember([], 'books-import-selected')

const hasResults = computed(() => (props.results?.length ?? 0) > 0)

const currentPageIds = computed(() =>
    (props.results ?? []).map(r => r.google_volume_id).filter(Boolean)
)

const allCheckedOnThisPage = computed(() => {
    const pageIds = currentPageIds.value
    if (!pageIds.length) return false
    const sel = new Set(selected.value)
    return pageIds.every(id => sel.has(id))
})

const search = (page = 1) => {
    router.get(route('books.import.index'), {
        title: form.title,
        author: form.author,
        publisher: form.publisher,
        isbn: form.isbn,
        per_page: form.per_page,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const clearFilters = () => {
    form.title = ''
    form.author = ''
    form.publisher = ''
    form.isbn = ''
    form.per_page = 20

    selected.value = []
    search(1)
}

const toggleAll = (e) => {
    if (!hasResults.value) return

    const pageIds = new Set(currentPageIds.value)
    const allSelected = new Set(selected.value)

    if (e.target.checked) {
        pageIds.forEach(id => allSelected.add(id))
    } else {
        pageIds.forEach(id => allSelected.delete(id))
    }

    selected.value = Array.from(allSelected)
}

const submitImport = () => {
    router.post(route('books.import.store'), {
        volume_ids: selected.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = []
        },
    })
}

const normalizeCover = (cover) => {
    if (!cover) return null
    return cover.startsWith('http') ? cover : `${window.location.origin}${cover}`
}

const normalizeIsbnInput = () => {
    form.isbn = (form.isbn || '').replace(/\D+/g, '').slice(0, 13)
}
</script>

<template>
    <PublicLayout title="Import Books">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto bg-gray-800 rounded-lg p-6">

                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">Import Books (Google Books)</h1>

                    <div class="flex items-center gap-2">
                        <Link :href="route('books.index')" class="btn btn-ghost">
                            Back
                        </Link>
                    </div>
                </div>

                <div v-if="!apiOk" class="alert alert-warning mb-6">
                    <span>Google Books API is unavailable right now. Please try again later.</span>
                </div>

                <div class="bg-gray-900/80 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="flex flex-col">
                            <label class="label">
                                <span class="label-text text-white font-bold">Book name</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="input input-bordered text-black"
                                placeholder="e.g. Clean Code"
                                @keyup.enter="search(1)"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="label">
                                <span class="label-text text-white font-bold">Author</span>
                            </label>
                            <input
                                v-model="form.author"
                                type="text"
                                class="input input-bordered text-black"
                                placeholder="e.g. Martin"
                                @keyup.enter="search(1)"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="label">
                                <span class="label-text text-white font-bold">Publisher</span>
                            </label>
                            <input
                                v-model="form.publisher"
                                type="text"
                                class="input input-bordered text-black"
                                placeholder="e.g. O'Reilly"
                                @keyup.enter="search(1)"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="label">
                                <span class="label-text text-white font-bold">ISBN</span>
                            </label>
                            <input
                                v-model="form.isbn"
                                type="text"
                                inputmode="numeric"
                                minlength="10"
                                maxlength="13"
                                pattern="\d{10}(\d{3})?"
                                class="input input-bordered text-black"
                                placeholder="10 or 13 digits"
                                @input="normalizeIsbnInput"
                                @keyup.enter="search(1)"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-4">
                        <div class="flex items-center gap-2">
                            <label class="label m-0 p-0">
                                <span class="label-text text-white font-bold">Per page</span>
                            </label>

                            <select
                                v-model.number="form.per_page"
                                class="select select-bordered text-black"
                                @change="search(1)"
                            >
                                <option :value="10">10</option>
                                <option :value="20">20</option>
                                <option :value="40">40</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                class="btn btn-ghost py-2 px-2 hover:bg-black"
                                @click="clearFilters"
                            >
                                Clear
                            </button>

                            <button
                                type="button"
                                class="btn py-2 px-2 bg-[#5754E8] hover:bg-[#3c39e3]"
                                :disabled="!apiOk"
                                @click="search(1)"
                            >
                                Search
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="apiOk && !hasResults && (form.title || form.author || form.publisher || form.isbn)"
                    class="bg-gray-900/80 rounded-lg p-8 text-center"
                >
                    <h2 class="text-xl font-semibold mb-2">There were no results for your search</h2>
                    <p class="opacity-70">Try to change the values in the filters (name, author, publisher ou ISBN).</p>
                </div>

                <div v-if="hasResults" class="bg-gray-900/80 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="opacity-70 text-sm">
                            Showing {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} of {{ meta?.total ?? 0 }}
                            (Page {{ meta?.current_page ?? 1 }} / {{ meta?.last_page ?? 1 }})
                        </div>

                        <button
                            type="button"
                            class="btn py-2 px-2 bg-green-700 hover:bg-green-900"
                            :disabled="selected.length === 0"
                            @click="submitImport"
                        >
                            Import selected ({{ selected.length }})
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>
                                    <input
                                        type="checkbox"
                                        class="checkbox"
                                        :checked="allCheckedOnThisPage"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th>Cover</th>
                                <th>Book</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Authors</th>
                                <th class="text-right">Price</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr v-for="r in results" :key="r.google_volume_id">
                                <td>
                                    <input
                                        class="checkbox"
                                        type="checkbox"
                                        :value="r.google_volume_id"
                                        v-model="selected"
                                        :disabled="!r.google_volume_id"
                                    />
                                </td>

                                <td>
                                    <div class="w-12 h-12 rounded-lg bg-gray-800 overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="r.cover"
                                            :src="normalizeCover(r.cover)"
                                            alt="cover"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-xs opacity-60">—</span>
                                    </div>
                                </td>

                                <td class="font-medium">
                                    {{ r.name }}
                                </td>

                                <td class="font-mono">
                                    {{ r.ISBN ?? '—' }}
                                </td>

                                <td>
                                    {{ r.publisher_name ?? '—' }}
                                </td>

                                <td class="max-w-md">
                                    <span v-if="r.authors?.length">
                                        {{ r.authors.join(', ') }}
                                    </span>
                                    <span v-else class="opacity-60">—</span>
                                </td>

                                <td class="text-right whitespace-nowrap">
                                    €{{ Number(r.price ?? 0).toFixed(2) }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        v-if="meta?.last_page > 1"
                        :meta="meta"
                        :route="route('books.import.index')"
                        :filters="{
                            title: form.title,
                            author: form.author,
                            publisher: form.publisher,
                            isbn: form.isbn,
                            per_page: form.per_page,
                        }"
                    />
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
