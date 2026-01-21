<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { Link, useForm } from "@inertiajs/vue3"

const props = defineProps({
    author: Object,
})

const form = useForm({
    name: props.author.name,
    photo: null,
    remove_photo: false,
})

const submit = () => {
    form.put(route("authors.update", props.author.id), {
        forceFormData: true,
    })
}

const currentPhotoUrl = props.author.photo
    ? props.author.photo
    : "/images/author-placeholder.png"
</script>

<template>
    <PublicLayout title="Edit Author">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">Edit Author</h1>

                    <div class="flex gap-2">
                        <Link
                            :href="route('authors.show', author.id)"
                            class="btn btn-ghost bg-gray-700 rounded-md hover:bg-black"
                        >
                            Back
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="flex items-center gap-4 bg-gray-900/80 rounded px-2 py-2">
                        <img
                            :src="currentPhotoUrl"
                            alt="Author photo"
                            class="w-24 h-24 object-cover bg-white rounded"
                        />

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                class="checkbox"
                                v-model="form.remove_photo"
                            />
                            <span class="text-sm opacity-80">Remove photo</span>
                        </label>
                    </div>

                    <div class="bg-gray-900/80 rounded px-2 py-2 gap-2">
                        <label class="label">
                            <span class="label-text text-white mb-2">Name</span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full text-black rounded-md"
                            placeholder="Author name..."
                        />

                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="bg-gray-900/80 rounded px-2 py-2 gap-2">
                        <label class="label">
                            <span class="label-text text-white mb-2">New Photo (optional)</span>
                        </label>

                        <input
                            type="file"
                            class="file-input file-input-bordered w-full text-white rounded-md"
                            accept="image/*"
                            @change="form.photo = $event.target.files[0]"
                        />

                        <p v-if="form.errors.photo" class="text-red-400 text-sm mt-1">
                            {{ form.errors.photo }}
                        </p>

                        <p class="text-xs opacity-60 mt-1">
                            If you upload a new photo, it will replace the current one.
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="submit"
                            class="hover:bg-black py-2 px-2 bg-gray-700 rounded-md"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? "Saving..." : "Save changes" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
