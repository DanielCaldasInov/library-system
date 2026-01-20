<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue";

const form = useForm({
    name: '',
    logo: null,
})

const submit = () => {
    form.post('/publishers', {
        forceFormData: true,
    })
}
</script>

<template>
    <PublicLayout title="Create Publisher">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">Create Publisher</h1>

                    <Link href="/publishers" class="btn btn-ghost">
                        Back
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Name -->
                    <div class="flex justify-between flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 py-2 font-bold text-base">Name</span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="Publisher name..."
                        />

                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Logo -->
                    <div class="flex justify-between flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 py-2 font-bold text-base">Logo</span>
                        </label>

                        <input
                            type="file"
                            class="file-input file-input-bordered w-full text-white rounded-lg"
                            accept="image/*"
                            @change="form.logo = $event.target.files[0]"
                        />

                        <p v-if="form.errors.logo" class="text-red-400 text-sm mt-1">
                            {{ form.errors.logo }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-ghost py-2 px-2 hover:bg-black" @click="form.reset()">
                            Clear
                        </button>

                        <button
                            type="submit"
                            class="hover:bg-black py-2 px-2"
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
