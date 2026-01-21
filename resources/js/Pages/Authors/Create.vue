<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue"

const form = useForm({
    name: '',
    photo: null,
})

const submit = () => {
    form.post(route('authors.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <PublicLayout title="Create Author">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">
                        Create Author
                    </h1>

                    <Link
                        :href="route('authors.index')"
                        class="btn btn-ghost"
                    >
                        Back
                    </Link>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-4">

                    <!-- Name -->
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
                            placeholder="Author name..."
                        />

                        <p
                            v-if="form.errors.name"
                            class="text-red-400 text-sm mt-1"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Photo -->
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white font-bold text-base">
                                Photo
                            </span>
                        </label>

                        <input
                            type="file"
                            class="file-input file-input-bordered w-full text-white rounded-lg"
                            accept="image/*"
                            @change="form.photo = $event.target.files[0]"
                        />

                        <p
                            v-if="form.errors.photo"
                            class="text-red-400 text-sm mt-1"
                        >
                            {{ form.errors.photo }}
                        </p>

                        <p class="text-xs opacity-60 mt-1">
                            Optional. Upload an author photo (jpg, png, webp).
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="btn btn-ghost hover:bg-black py-2 px-2"
                            @click="form.reset()"
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
