<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import SearchForm from "@/Components/SearchForm.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { ref } from "vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    requests: Object,
    filters: Object,
    stats: Object,
    searchOptions: Array,
})

const confirmModal = ref(null)
const requestToCancel = ref(null)

const openCancelModal = (req) => {
    requestToCancel.value = req
    confirmModal.value?.open()
}

const onCancel = () => {
    requestToCancel.value = null
}

const confirmCancel = () => {
    if (!requestToCancel.value) return

    router.patch(route("requests.cancel", requestToCancel.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmModal.value?.close()
            requestToCancel.value = null
        },
    })
}

const setStatusFilter = (status) => {
    router.get('/requests', {
        ...props.filters,
        status,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    })
}

const statusLabel = (status) => {
    if (status === 'active') return 'Active'
    if (status === 'awaiting_confirmation') return 'Awaiting confirmation'
    if (status === 'completed') return 'Completed'
    if (status === 'canceled') return 'Canceled'
    return status
}

const statusBadgeClass = (status) => {
    if (status === 'active') return 'badge badge-success'
    if (status === 'awaiting_confirmation') return 'badge badge-warning'
    if (status === 'completed') return 'badge badge-neutral'
    if (status === 'canceled') return 'badge badge-error'
    return 'badge'
}
</script>

<template>
    <public-layout title="Requests">
        <div class="p-6 flex flex-col items-center">

            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Requests
                </h1>

                <div class="flex items-center gap-2">
                    <a
                        href="/requests/create"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3]"
                    >
                        New Request
                    </a>
                </div>
            </div>

            <div class="w-full max-w-6xl mx-auto space-y-4">

                <!-- Admin stats -->
                <div
                    v-if="$page.props.auth?.user?.is_admin && stats"
                    class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                >
                    <div class="bg-gray-800 p-4 rounded-lg">
                        <p class="text-sm opacity-70">Active Requests</p>
                        <p class="text-2xl font-bold">{{ stats.activeRequests }}</p>
                    </div>

                    <div class="bg-gray-800 p-4 rounded-lg">
                        <p class="text-sm opacity-70">Last 30 Days</p>
                        <p class="text-2xl font-bold">{{ stats.requestsLast30Days }}</p>
                    </div>

                    <div class="bg-gray-800 p-4 rounded-lg">
                        <p class="text-sm opacity-70">Delivered Today</p>
                        <p class="text-2xl font-bold">{{ stats.deliveredToday }}</p>
                    </div>
                </div>

                <!-- Search -->
                <SearchForm
                    action="/requests"
                    :filters="filters"
                    :options="searchOptions"
                />

                <!-- Status filter buttons -->
                <div class="flex flex-wrap gap-4 bg-gray-800 px-4 py-3 rounded-lg">
                    <button
                        v-if="$page.props.auth?.user?.is_admin"
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                        :class="filters?.status === 'all' ? 'btn-primary' : 'btn-outline'"
                        @click="setStatusFilter('all')"
                    >
                        All
                    </button>

                    <button
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                        :class="(filters?.status ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline'"
                        @click="setStatusFilter('active')"
                    >
                        Active
                    </button>

                    <button
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                        :class="filters?.status === 'completed' ? 'btn-primary' : 'btn-outline'"
                        @click="setStatusFilter('completed')"
                    >
                        Completed
                    </button>


                </div>

                <!-- Empty state -->
                <div
                    v-if="!requests?.data?.length"
                    class="bg-gray-800 rounded-lg p-8 text-center"
                >
                    <h2 class="text-xl font-semibold mb-2">
                        No requests yet
                    </h2>
                    <p class="opacity-70 mb-4">
                        There are no requests to display right now.
                    </p>

                    <a
                        href="/requests/create"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3]"
                    >
                        Create the first request
                    </a>
                </div>

                <!-- Table -->
                <DataTable
                    v-else
                    :data="requests"
                    :sort="filters?.sort"
                    :direction="filters?.direction"
                    :filters="filters"
                    route="/requests"
                >
                    <template #head>
                        <tr>
                            <th>#</th>
                            <th>Book</th>
                            <th v-if="$page.props.auth?.user?.is_admin">Citizen</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Due</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="req in rows" :key="req.id">
                            <td class="font-mono">
                                #{{ req.number }}
                            </td>

                            <td class="truncate max-w-xs">
                                {{ req.book_name ?? req.book?.name ?? 'Deleted book' }}
                            </td>

                            <td v-if="$page.props.auth?.user?.is_admin" class="truncate max-w-xs">
                                {{ req.citizen_name ?? req.citizen?.name ?? 'Deleted user' }}
                            </td>

                            <td>
                                <span :class="statusBadgeClass(req.status)">
                                    {{ statusLabel(req.status) }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap">
                                {{ req.requested_at ? new Date(req.requested_at).toLocaleDateString() : '-' }}
                            </td>

                            <td class="whitespace-nowrap">
                                {{ req.due_at ? new Date(req.due_at).toLocaleDateString() : '-' }}
                            </td>

                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a
                                        :href="route('requests.show', req.id)"
                                        class="btn hover:bg-[#5754E8]"
                                    >
                                        Details
                                    </a>

                                    <button
                                        v-if="$page.props.auth?.user?.is_admin && req.status !== 'completed' && req.status !== 'canceled'"
                                        type="button"
                                        class="btn bg-red-600 py-2 px-2 hover:bg-red-900"
                                        @click="openCancelModal(req)"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <!-- Pagination (only when there are rows) -->
                <Pagination
                    v-if="requests?.data?.length"
                    :meta="requests"
                    route="/requests"
                    :filters="filters"
                />
            </div>

            <ConfirmModal
                ref="confirmModal"
                title="Cancel request"
                :message="`Are you sure you want to cancel request #${requestToCancel?.number ?? ''}? This action cannot be undone.`"
                confirmText="Yes, cancel"
                cancelText="Back"
                :danger="true"
                @confirm="confirmCancel"
                @cancel="onCancel"
            />
        </div>
    </public-layout>
</template>
