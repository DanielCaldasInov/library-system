<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import { Link } from "@inertiajs/vue3"

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})
</script>

<template>
    <PublicLayout title="Users">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Users
                </h1>

                <Link
                    :href="route('users.create')"
                    class="btn bg-[#5754E8] hover:bg-[#3c39e3] border-0 text-white"
                >
                    Create User
                </Link>
            </div>

            <div class="w-full max-w-6xl mx-auto space-y-4">
                <div class="bg-gray-800 p-4 rounded-lg">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center">
                            <SearchForm
                                action="/users"
                                :filters="filters"
                                :options="[
                                  { value: 'name', label: 'Name' }
                                ]"
                                class="w-full"
                            />
                        </div>
                        <form method="get" action="/users" class="flex items-center gap-3">
                            <input type="hidden" name="search" :value="filters.search ?? ''" />

                            <span class="text-white font-medium">
                                Role
                            </span>

                            <select
                                name="role_id"
                                class="select select-bordered bg-white text-black min-w-[140px]"
                                @change="$event.target.form.submit()"
                            >
                                <option value="">All</option>

                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                    :selected="String(filters.role_id) === String(role.id)"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                        </form>
                    </div>
                </div>

                <div
                    v-if="!users?.data?.length"
                    class="bg-gray-800 rounded-lg p-8 text-center"
                >
                    <h2 class="text-xl font-semibold mb-2 text-white">
                        No users found
                    </h2>
                    <p class="opacity-70 text-white">
                        There are no users matching your filters.
                    </p>
                </div>

                <DataTable
                    v-else
                    :data="users"
                    route="/users"
                >
                    <template #head>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-right">Registered</th>
                            <th>Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="user in rows" :key="user.id">
                            <td class="font-medium">
                                {{ user.name }}
                            </td>

                            <td class="truncate max-w-sm">
                                {{ user.email }}
                            </td>

                            <td>
                                <span
                                    class="badge"
                                    :class="user.role?.name === 'admin'
                                    ? 'badge-warning'
                                    : 'badge-neutral'"
                                >
                                  {{ user.role?.name ?? '-' }}
                                </span>
                            </td>

                            <td class="text-right whitespace-nowrap">
                                {{ new Date(user.created_at).toLocaleDateString() }}
                            </td>
                            <td>
                                <a
                                    :href="route('users.show', user.id)"
                                    class="btn hover:bg-[#5754E8]"
                                >
                                    Details
                                </a>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    v-if="users?.data?.length"
                    :meta="users"
                    route="/users"
                    :filters="filters"
                />
            </div>
        </div>
    </PublicLayout>
</template>
