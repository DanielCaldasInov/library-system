<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { Link, router, usePage } from "@inertiajs/vue3"
import { computed, ref } from "vue"

const props = defineProps({
    request: Object,
})

const page = usePage()

const isAdmin = computed(() => !!page.props.auth?.user?.is_admin)
const authUserId = computed(() => page.props.auth?.user?.id ?? null)

const statusLabel = (status) => {
    if (status === "active") return "Active"
    if (status === "awaiting_confirmation") return "Awaiting confirmation"
    if (status === "completed") return "Completed"
    if (status === "canceled") return "Canceled"
    return status
}

const statusBadgeClass = (status) => {
    if (status === "active") return "badge badge-success"
    if (status === "awaiting_confirmation") return "badge badge-warning"
    if (status === "completed") return "badge badge-neutral"
    if (status === "canceled") return "badge badge-error"
    return "badge"
}

const canCancel = computed(() => {
    const st = props.request?.status
    return st !== "completed" && st !== "canceled"
})

const canConfirm = computed(() => {
    const st = props.request?.status
    return isAdmin.value && st === "awaiting_confirmation"
})

// Citizen (owner) can mark returned only when active
const isOwner = computed(() => {
    return !!authUserId.value && props.request?.user_id === authUserId.value
})

const canMarkReturned = computed(() => {
    const st = props.request?.status
    return isOwner.value && st === "active"
})

const coverUrl = computed(() => {
    return props.request?.book?.cover ? props.request.book.cover : "/images/book-placeholder.png"
})

const bookTitle = computed(() => {
    return props.request?.book_name ?? props.request?.book?.name ?? "Deleted book"
})

const citizenName = computed(() => {
    return props.request?.citizen_name ?? props.request?.citizen?.name ?? "Deleted user"
})

const citizenEmail = computed(() => {
    return props.request?.citizen_email ?? props.request?.citizen?.email ?? "-"
})

const requestedDate = computed(() =>
    props.request?.requested_at ? new Date(props.request.requested_at).toLocaleDateString() : "-"
)

const dueDate = computed(() =>
    props.request?.due_at ? new Date(props.request.due_at).toLocaleDateString() : "-"
)

const receivedDate = computed(() =>
    props.request?.received_at ? new Date(props.request.received_at).toLocaleDateString() : "-"
)

const cancelModal = ref(null)
const confirmModal = ref(null)
const returnedModal = ref(null)

const openCancel = () => cancelModal.value?.open()
const closeCancel = () => cancelModal.value?.close()

const openConfirm = () => confirmModal.value?.open()
const closeConfirm = () => confirmModal.value?.close()

const openReturned = () => returnedModal.value?.open()
const closeReturned = () => returnedModal.value?.close()

const cancelRequest = () => {
    router.patch(route("requests.cancel", props.request.id), {}, {
        preserveScroll: true,
        onFinish: () => closeCancel(),
    })
}

const confirmReception = () => {
    router.patch(route("requests.confirmReceived", props.request.id), {}, {
        preserveScroll: true,
        onFinish: () => closeConfirm(),
    })
}

const markReturned = () => {
    router.patch(route("requests.returned", props.request.id), {}, {
        preserveScroll: true,
        onFinish: () => closeReturned(),
    })
}
</script>

<template>
    <public-layout :title="`Request #${request.number}`">
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between bg-gray-800 p-6 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">
                        Request #{{ request.number }}
                    </h1>

                    <div class="mt-2 flex items-center gap-3">
            <span :class="statusBadgeClass(request.status)">
              {{ statusLabel(request.status) }}
            </span>

                        <span class="text-sm opacity-70">
              Requested: <span class="font-semibold">{{ requestedDate }}</span>
            </span>

                        <span class="text-sm opacity-70">
              Due: <span class="font-semibold">{{ dueDate }}</span>
            </span>

                        <span v-if="request.received_at" class="text-sm opacity-70">
              Received: <span class="font-semibold">{{ receivedDate }}</span>
            </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/requests" class="btn bg-black/40 hover:bg-black">
                        Back
                    </Link>

                    <!-- Citizen owner action -->
                    <button
                        v-if="canMarkReturned"
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                        @click="openReturned"
                    >
                        Mark as Returned
                    </button>

                    <!-- Admin action -->
                    <button
                        v-if="canConfirm"
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-2"
                        @click="openConfirm"
                    >
                        Confirm Reception
                    </button>

                    <!-- Admin cancel -->
                    <button
                        v-if="isAdmin && canCancel"
                        type="button"
                        class="btn bg-red-600 hover:bg-red-900 py-2 px-2"
                        @click="openCancel"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Book card -->
                <div class="lg:col-span-2 bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">
                        Book
                    </h2>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <img
                            :src="coverUrl"
                            class="w-32 h-32 object-contain bg-white rounded"
                            alt="Book cover"
                        />

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold">
                                {{ bookTitle }}
                            </h3>

                            <p class="text-sm opacity-70 mt-1">
                                Publisher:
                                <span class="font-medium">
                  {{ request.book?.publisher?.name ?? "-" }}
                </span>
                            </p>

                            <div class="mt-3 flex gap-2">
                                <Link
                                    v-if="request.book?.id"
                                    :href="route('books.show', request.book.id)"
                                    class="btn btn-sm btn-outline"
                                >
                                    View book details
                                </Link>
                            </div>

                            <p v-if="!request.book?.id" class="text-sm opacity-60 mt-2">
                                This book was deleted, but the request record remains for history.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Citizen card (admin only) -->
                <div v-if="isAdmin" class="bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">
                        Citizen
                    </h2>

                    <div class="space-y-2">
                        <p class="opacity-80">
                            <span class="font-semibold">Name:</span>
                            {{ citizenName }}
                        </p>

                        <p class="opacity-80">
                            <span class="font-semibold">Email:</span>
                            {{ citizenEmail }}
                        </p>

                        <p class="text-sm opacity-60 pt-2">
                            Citizen data is stored as a snapshot so the request can persist even if the account is deleted.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Admin: receipt info -->
            <div v-if="isAdmin" class="bg-gray-800 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">
                    Reception details
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-gray-900/70 rounded p-4">
                        <p class="text-sm opacity-70">Received by</p>
                        <p class="font-semibold">
                            {{ request.received_by_admin?.name ?? "-" }}
                        </p>
                    </div>

                    <div class="bg-gray-900/70 rounded p-4">
                        <p class="text-sm opacity-70">Received at</p>
                        <p class="font-semibold">
                            {{ receivedDate }}
                        </p>
                    </div>

                    <div class="bg-gray-900/70 rounded p-4">
                        <p class="text-sm opacity-70">Days elapsed</p>
                        <p class="font-semibold">
                            {{ request.days_elapsed ?? "-" }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Mark returned modal (citizen owner) -->
            <ConfirmModal
                ref="returnedModal"
                title="Mark as returned"
                :message="`Mark request #${request.number} as returned? This will set it to 'awaiting confirmation' until an admin confirms reception.`"
                confirmText="Yes, mark returned"
                cancelText="Back"
                :danger="false"
                @confirm="markReturned"
                @cancel="closeReturned"
            />

            <!-- Cancel modal -->
            <ConfirmModal
                ref="cancelModal"
                title="Cancel request"
                :message="`Are you sure you want to cancel request #${request.number}? This action cannot be undone.`"
                confirmText="Yes, cancel"
                cancelText="Back"
                :danger="true"
                @confirm="cancelRequest"
                @cancel="closeCancel"
            />

            <!-- Confirm reception modal (admin) -->
            <ConfirmModal
                ref="confirmModal"
                title="Confirm reception"
                :message="`Confirm reception for request #${request.number}? This will mark it as completed.`"
                confirmText="Yes, confirm"
                cancelText="Back"
                :danger="false"
                @confirm="confirmReception"
                @cancel="closeConfirm"
            />
        </div>
    </public-layout>
</template>
