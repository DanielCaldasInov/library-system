<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import { Link, router } from "@inertiajs/vue3"

const props = defineProps({
    users: Object,
    filters: Object,
    sort: String,
    direction: String,
    roleOptions: Array,
    searchOptions: Array,
})

const sortBy = (field) => {
    const same = props.sort === field
    const nextDirection = same ? (props.direction === "asc" ? "desc" : "asc") : "asc"

    router.get("/users", {
        ...props.filters,
        sort: field,
        direction: nextDirection,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    })
}

const arrow = (field) => {
    if (props.sort !== field) return ""
    return props.direction === "asc" ? "▲" : "▼"
}
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
                <SearchForm
                    action="/users"
                    :filters="{ ...filters, status: filters.role }"
                    :options="searchOptions"
                    :status-options="roleOptions"
                />

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
                    :filters="{ ...filters, sort, direction }"
                    route="/users"
                >
                    <template #head>
                        <tr>
                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('name')">
                                Name {{ arrow('name') }}
                            </th>

                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('email')">
                                Email {{ arrow('email') }}
                            </th>

                            <th>Role</th>

                            <th class="text-right cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('created_at')">
                                Registered {{ arrow('created_at') }}
                            </th>

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
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>
    </PublicLayout>
</template>
