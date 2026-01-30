<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { Link } from "@inertiajs/vue3"

const props = defineProps({
    stats: Object,
})

const fmtDate = (v) => (v ? new Date(v).toLocaleDateString() : "—")

const statusLabel = (status) => {
    switch (status) {
        case "active":
            return "Active"
        case "awaiting_confirmation":
            return "Awaiting confirmation"
        case "completed":
            return "Completed"
        case "canceled":
            return "Canceled"
        default:
            return status ?? "—"
    }
}

const statusBadgeClass = (status) => {
    switch (status) {
        case "active":
            return "badge badge-success"
        case "awaiting_confirmation":
            return "badge badge-warning"
        case "completed":
            return "badge badge-neutral"
        case "canceled":
            return "badge badge-error"
        default:
            return "badge"
    }
}
</script>

<template>
    <PublicLayout title="Dashboard">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                        Dashboard
                    </h1>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-800 rounded-lg p-4">
                        <p class="text-sm opacity-70">Books</p>
                        <p class="text-3xl font-bold">{{ stats?.booksCount ?? 0 }}</p>
                        <p class="text-xs opacity-60 mt-1">Total in catalog</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4">
                        <p class="text-sm opacity-70">Authors</p>
                        <p class="text-3xl font-bold">{{ stats?.authorsCount ?? 0 }}</p>
                        <p class="text-xs opacity-60 mt-1">Total registered</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4">
                        <p class="text-sm opacity-70">Publishers</p>
                        <p class="text-3xl font-bold">{{ stats?.publishersCount ?? 0 }}</p>
                        <p class="text-xs opacity-60 mt-1">Total registered</p>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4">
                        <p class="text-sm opacity-70">Users</p>
                        <p class="text-3xl font-bold">{{ stats?.usersCount ?? 0 }}</p>
                        <p class="text-xs opacity-60 mt-1">Total accounts</p>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold">Requests status</h2>
                        <div class="text-sm opacity-70">
                            Total: {{ stats?.requestsCount ?? 0 }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gray-900/80 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Active</span>
                                <span class="badge badge-success">●</span>
                            </div>
                            <div class="text-3xl font-bold mt-2">
                                {{ stats?.requestsByStatus?.active ?? 0 }}
                            </div>
                        </div>

                        <div class="bg-gray-900/80 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Awaiting</span>
                                <span class="badge badge-warning">●</span>
                            </div>
                            <div class="text-3xl font-bold mt-2">
                                {{ stats?.requestsByStatus?.awaiting_confirmation ?? 0 }}
                            </div>
                        </div>

                        <div class="bg-gray-900/80 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Completed</span>
                                <span class="badge badge-neutral">●</span>
                            </div>
                            <div class="text-3xl font-bold mt-2">
                                {{ stats?.requestsByStatus?.completed ?? 0 }}
                            </div>
                        </div>

                        <div class="bg-gray-900/80 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">Canceled</span>
                                <span class="badge badge-error">●</span>
                            </div>
                            <div class="text-3xl font-bold mt-2">
                                {{ stats?.requestsByStatus?.canceled ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-800 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold">Recent users</h2>
                            <Link v-if="$page.props.auth?.user?.is_admin" :href="route('users.index')" class="btn btn-ghost">
                                View all
                            </Link>
                        </div>

                        <div v-if="!(stats?.recentUsers?.length)" class="opacity-60">
                            No users yet
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="u in stats.recentUsers"
                                :key="u.id"
                                :href="route('users.show', u.id)"
                                class="block bg-gray-900/80 rounded-lg p-4 hover:bg-gray-700 transition"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold truncate">{{ u.name }}</p>
                                        <p class="text-xs opacity-70 truncate">{{ u.email }}</p>
                                    </div>
                                    <div class="text-xs opacity-70 whitespace-nowrap">
                                        {{ fmtDate(u.created_at) }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold">Recent requests</h2>
                            <Link :href="route('requests.index')" class="btn btn-ghost">
                                View all
                            </Link>
                        </div>

                        <div v-if="!(stats?.recentRequests?.length)" class="opacity-60">
                            No requests yet
                        </div>

                        <div v-else class="space-y-2">
                            <Link
                                v-for="r in stats.recentRequests"
                                :key="r.id"
                                :href="route('requests.show', r.id)"
                                class="block bg-gray-900/80 rounded-lg p-4 hover:bg-gray-700 transition"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold truncate">
                                            #{{ r.number }} — {{ r.book_name ?? '—' }}
                                        </p>
                                        <p class="text-xs opacity-70 truncate">
                                            Citizen: {{ r.citizen_name ?? '—' }}
                                        </p>
                                        <p class="text-xs opacity-70 mt-1">
                                            Requested: {{ fmtDate(r.requested_at) }} · Due: {{ fmtDate(r.due_at) }}
                                        </p>
                                    </div>

                                    <span :class="statusBadgeClass(r.status)">
                                        {{ statusLabel(r.status) }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
