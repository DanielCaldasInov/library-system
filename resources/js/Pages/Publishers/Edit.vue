<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    publisher: Object,
});

const form = useForm({
    name: props.publisher.name,
    logo: null,
    remove_logo: false,
});

const submit = () => {
    form.put(route("publishers.update", props.publisher.id), {
        forceFormData: true,
    });
};

const currentLogoUrl = props.publisher.logo
    ? `${props.publisher.logo}`
    : "/images/publisher-placeholder.png";
</script>

<template>
    <PublicLayout title="Edit Publisher">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">

                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">Edit Publisher</h1>

                    <div class="flex gap-2">
                        <Link :href="route('publishers.show', publisher.id)" class="btn btn-ghost bg-gray-700  rounded-md hover:bg-black">
                            Back
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Current logo -->
                    <div class="flex items-center gap-4 bg-gray-900/80 rounded px-2 py-2">
                        <img
                            :src="currentLogoUrl"
                            alt="Publisher logo"
                            class="w-24 h-24 object-contain bg-white rounded"
                        />

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                class="checkbox"
                                v-model="form.remove_logo"
                            />
                            <span class="text-sm opacity-80">Remove logo</span>
                        </label>
                    </div>

                    <!-- Name -->
                    <div class="bg-gray-900/80 rounded px-2 py-2 gap-2">
                        <label class="label">
                            <span class="label-text text-white mb-2">Name</span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full text-black rounded-md"
                            placeholder="Publisher name..."
                        />

                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- New logo -->
                    <div class="bg-gray-900/80 rounded px-2 py-2 gap-2">
                        <label class="label">
                            <span class="label-text text-white mb-2">New Logo (optional)</span>
                        </label>

                        <input
                            type="file"
                            class="file-input file-input-bordered w-full text-white rounded-md"
                            accept="image/*"
                            @change="form.logo = $event.target.files[0]"
                        />

                        <p v-if="form.errors.logo" class="text-red-400 text-sm mt-1">
                            {{ form.errors.logo }}
                        </p>

                        <p class="text-xs opacity-60 mt-1">
                            If you upload a new logo, it will replace the current one.
                        </p>
                    </div>

                    <!-- Actions -->
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
