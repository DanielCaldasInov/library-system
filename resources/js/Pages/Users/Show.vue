<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import Pagination from "@/Components/Pagination.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { Link, router, usePage } from "@inertiajs/vue3"
import { ref, computed } from "vue"

const props = defineProps({
    user: Object,
    requestsCount: Number,
    requests: Object,
    hasBlockingRequests: Boolean,
})

const page = usePage()

const confirmDeleteModal = ref(null)

const deleteBlockedMessage = computed(() => {
    if (!props.hasBlockingRequests) return ""
    return "This user cannot be deleted because they have active or awaiting confirmation requests."
})

const openDeleteModal = () => {
    if (props.hasBlockingRequests) return
    confirmDeleteModal.value?.open()
}

const confirmDelete = () => {
    if (props.hasBlockingRequests) return

    router.delete(route("users.destroy", props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDeleteModal.value?.close()
        },
    })
}

const statusBadge = (status) => {
    switch (status) {
        case "active":
            return "badge-success"
        case "awaiting_confirmation":
            return "badge-warning"
        case "completed":
            return "badge-neutral"
        case "canceled":
            return "badge-error"
        default:
            return "badge-ghost"
    }
}

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
            return status ?? "-"
    }
}

const fmtDate = (v) => (v ? new Date(v).toLocaleDateString() : "-")
</script>

<template>
    <PublicLayout title="User details">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto space-y-4">
                <div class="bg-gray-800 rounded-lg p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="avatar" v-if="user.profile_photo_url">
                                <div class="w-14 rounded-full">
                                    <img :src="user.profile_photo_url" alt="User photo" />
                                </div>
                            </div>

                            <div>
                                <h1 class="text-2xl font-bold text-white">
                                    {{ user.name }}
                                </h1>

                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="text-white/80">{{ user.email }}</span>

                                    <span
                                        class="badge"
                                        :class="user.role?.name === 'admin' ? 'badge-warning' : 'badge-neutral'"
                                    >
                    {{ user.role?.name ?? '-' }}
                  </span>

                                    <span class="text-white/60">
                    • Registered: {{ fmtDate(user.created_at) }}
                  </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Link href="/users" class="btn btn-ghost">
                                Back
                            </Link>

                            <button
                                type="button"
                                class="btn bg-red-600 py-2 px-2 hover:bg-red-900 text-white"
                                :class="hasBlockingRequests ? 'opacity-60 cursor-not-allowed' : ''"
                                :disabled="hasBlockingRequests"
                                @click="openDeleteModal"
                            >
                                Delete
                            </button>

                            <Link
                                :href="route('users.edit', user.id)"
                                class="btn bg-gray-700 hover:bg-black text-white"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>

                    <div class="divider my-4"></div>

                    <div class="flex flex-wrap gap-3">
                        <div class="bg-gray-900/80 rounded-lg px-4 py-3">
                            <p class="text-white/70 text-sm">Total requests</p>
                            <p class="text-white text-2xl font-bold">{{ requestsCount }}</p>
                        </div>
                    </div>

                    <div v-if="hasBlockingRequests" class="alert alert-warning mt-4">
                        <span>{{ deleteBlockedMessage }}</span>
                    </div>

                    <div v-if="$page.props.flash?.error" class="alert alert-error mt-4">
                        <span>{{ $page.props.flash.error }}</span>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Requests</h2>
                        <span class="text-white/70 text-sm">
              Showing {{ requests?.data?.length ?? 0 }} of {{ requests?.total ?? requestsCount }}
            </span>
                    </div>

                    <div v-if="!requests?.data?.length" class="bg-gray-900/60 rounded-lg p-6 text-center">
                        <h3 class="text-white font-semibold">No requests</h3>
                        <p class="text-white/70">This user has not made any book requests yet.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Book</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Due</th>
                                <th>Returned</th>
                                <th>Received by</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr v-for="r in requests.data" :key="r.id">
                                <td class="font-mono">
                                    {{ r.number }}
                                </td>

                                <td class="max-w-[360px]">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar" v-if="r.book?.cover || r.book_cover">
                                            <div class="w-10 rounded">
                                                <img :src="r.book?.cover ?? r.book_cover" alt="Book cover" />
                                            </div>
                                        </div>

                                        <div class="truncate">
                                            <div class="font-medium">
                                                {{ r.book?.name ?? r.book_name ?? '-' }}
                                            </div>
                                            <div class="text-sm text-white/60">
                                                Citizen: {{ r.citizen_name ?? user.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="flex items-center gap-2">
                      <span class="badge" :class="statusBadge(r.status)">
                        {{ statusLabel(r.status) }}
                      </span>

                                        <span v-if="r.is_overdue" class="badge badge-error">
                        Overdue
                      </span>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ fmtDate(r.requested_at) }}
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ fmtDate(r.due_at) }}
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ fmtDate(r.returned_at) }}
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ r.received_by_admin?.name ?? '-' }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <Pagination
                            v-if="requests?.data?.length"
                            :meta="requests"
                            :filters="{}"
                            route=""
                        />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            ref="confirmDeleteModal"
            title="Delete user"
            :message="`Are you sure you want to delete '${user.name}'? This action cannot be undone.`"
            confirmText="Yes, delete"
            cancelText="Cancel"
            :danger="true"
            :confirmDisabled="hasBlockingRequests"
            @confirm="confirmDelete"
        >
            <div v-if="hasBlockingRequests" class="alert alert-warning mt-3">
                <span>{{ deleteBlockedMessage }}</span>
            </div>
        </ConfirmModal>
    </PublicLayout>
</template>
