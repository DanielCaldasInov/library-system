<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue"

const props = defineProps({
    roles: Array,
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
})

const submit = () => {
    form.post('/users')
}
</script>

<template>
    <PublicLayout title="Create User">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-white">
                        Create User
                    </h1>

                    <Link href="/users" class="btn btn-ghost">
                        Back
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 font-bold text-base">
                                Name
                            </span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="User name..."
                        />

                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 font-bold text-base">
                                Email
                            </span>
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="user@email.com"
                        />

                        <p v-if="form.errors.email" class="text-red-400 text-sm mt-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 font-bold text-base">
                                Password
                            </span>
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="********"
                        />

                        <p v-if="form.errors.password" class="text-red-400 text-sm mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 font-bold text-base">
                                Confirm Password
                            </span>
                        </label>

                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="input input-bordered w-full text-black rounded-lg"
                            placeholder="********"
                        />
                    </div>

                    <div class="flex flex-col rounded-md bg-gray-900/80 px-2 py-2">
                        <label class="label mb-2">
                            <span class="label-text text-white px-2 font-bold text-base">
                                Role
                            </span>
                        </label>

                        <select
                            v-model="form.role_id"
                            class="select select-bordered w-full text-black rounded-lg"
                        >
                            <option value="" disabled>
                                Select a role
                            </option>

                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.name }}
                            </option>
                        </select>

                        <p v-if="form.errors.role_id" class="text-red-400 text-sm mt-1">
                            {{ form.errors.role_id }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="btn btn-ghost py-2 px-2 bg-gray-600/40 hover:bg-black"
                            @click="form.reset()"
                        >
                            Clear
                        </button>

                        <button
                            type="submit"
                            class="btn btn-ghost py-2 px-2 bg-black/30 hover:bg-black"
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
