<script setup>
import { ref } from 'vue'
import PublicLayout from "@/Layouts/PublicLayout.vue"
import SearchForm from "@/Components/SearchForm.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"

const props = defineProps({
    logs: Object,
    filters: Object,
    sort: String,
    direction: String,
    moduleOptions: Array,
    actionOptions: Array,
})

const detailsModal = ref(null)
const selectedLog = ref(null)

const openDetails = (log) => {
    selectedLog.value = log
    detailsModal.value?.showModal()
}

const closeDetails = () => {
    selectedLog.value = null
    detailsModal.value?.close()
}

const actionBadgeClass = (action) => {
    switch (action) {
        case 'created': return "badge badge-success"
        case 'updated': return "badge badge-warning"
        case 'deleted': return "badge badge-error"
        default: return "badge badge-ghost"
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleString()
}
</script>

<template>
    <PublicLayout title="System Logs">
        <div class="p-6 flex flex-col items-center">

            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg text-white">
                    System Logs
                </h1>
            </div>

            <div class="w-full max-w-6xl mx-auto">

                <SearchForm
                    action="/system-logs"
                    :filters="filters"
                    :options="moduleOptions"
                    :statusOptions="actionOptions"
                />

                <DataTable
                    :data="logs"
                    :sort="sort"
                    :direction="direction"
                    :filters="filters"
                    route="/system-logs"
                >
                    <template #head="{ sortBy, sort, direction }">
                        <tr>
                            <th @click="sortBy('created_at')" class="cursor-pointer hover:bg-gray-700 transition-colors">
                                Date {{ sort === 'created_at' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                            <th @click="sortBy('user')" class="cursor-pointer hover:bg-gray-700 transition-colors">
                                User {{ sort === 'user' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                            <th @click="sortBy('module')" class="cursor-pointer hover:bg-gray-700 transition-colors">
                                Module {{ sort === 'module' ? (direction === 'asc' ? '▲' : '▼') : '' }}
                            </th>
                            <th>Record ID</th>
                            <th>Action</th>
                            <th>IP / Browser</th>
                            <th class="text-right">Details</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="log in rows" :key="log.id">
                            <td class="whitespace-nowrap">{{ formatDate(log.created_at) }}</td>

                            <td>
                                <div v-if="log.user" class="font-medium">
                                    {{ log.user.name }}
                                    <span class="block text-xs opacity-60 font-normal">{{ log.user.email }}</span>
                                </div>
                                <span v-else class="opacity-60 italic">System</span>
                            </td>

                            <td class="font-bold">{{ log.module }}</td>
                            <td class="font-mono">{{ log.record_id }}</td>

                            <td>
                                <span :class="actionBadgeClass(log.action)">
                                    {{ log.action.toUpperCase() }}
                                </span>
                            </td>

                            <td class="max-w-[200px] truncate" :title="log.user_agent">
                                {{ log.ip_address }}
                                <span class="block text-xs opacity-60 truncate">{{ log.user_agent }}</span>
                            </td>

                            <td class="text-right">
                                <button
                                    type="button"
                                    class="btn btn-sm bg-gray-700 hover:bg-black text-white border-none py-2 px-2"
                                    @click="openDetails(log)"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    :meta="logs"
                    route="/system-logs"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>

        <dialog ref="detailsModal" class="modal">
            <div class="modal-box bg-[#191E24] text-white w-11/12 max-w-3xl border border-gray-700">

                <h3 class="font-bold text-lg mb-4 flex items-center justify-between border-b border-gray-700 pb-2">
                    <span>
                        Log Details
                        <span v-if="selectedLog" class="ml-2" :class="actionBadgeClass(selectedLog.action)">
                            {{ selectedLog.action.toUpperCase() }}
                        </span>
                    </span>
                    <button class="btn btn-sm btn-circle btn-ghost hover:bg-black" @click="closeDetails">✕</button>
                </h3>

                <div v-if="selectedLog">
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm opacity-80">
                        <div><strong>Module:</strong> {{ selectedLog.module }}</div>
                        <div><strong>Record ID:</strong> {{ selectedLog.record_id }}</div>
                        <div><strong>User:</strong> {{ selectedLog.user?.name ?? 'System' }}</div>
                        <div><strong>Date:</strong> {{ formatDate(selectedLog.created_at) }}</div>
                    </div>

                    <h4 class="font-semibold mb-2 mt-6">Changes Data:</h4>

                    <div v-if="selectedLog.changes" class="bg-black rounded-lg p-4 overflow-x-auto max-h-[50vh] overflow-y-auto border border-gray-800">
                        <pre class="text-xs text-green-400 font-mono m-0 whitespace-pre-wrap break-all">{{ JSON.stringify(selectedLog.changes, null, 2) }}</pre>
                    </div>

                    <div v-else class="opacity-60 italic p-4 bg-black rounded-lg border border-gray-800">
                        No additional data recorded.
                    </div>

                    <div class="modal-action mt-6">
                        <button class="btn bg-gray-700 hover:bg-black text-white border-none" @click="closeDetails">Close</button>
                    </div>
                </div>
            </div>

            <form method="dialog" class="modal-backdrop" @click="closeDetails">
                <button>close</button>
            </form>
        </dialog>
    </PublicLayout>
</template>
