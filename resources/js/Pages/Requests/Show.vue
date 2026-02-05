<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { Link, router, usePage, useForm } from "@inertiajs/vue3"
import { computed, ref } from "vue"

const props = defineProps({
    request: Object,
    canLeaveReview: Boolean,
})

const page = usePage()

const isAdmin = computed(() => !!page.props.auth?.user?.is_admin)
const authUserId = computed(() => page.props.auth?.user?.id ?? null)

const isOwner = computed(() =>
    authUserId.value && props.request?.user_id === authUserId.value
)

const canMarkReturned = computed(() =>
    isOwner.value && props.request?.status === "active"
)

const canConfirm = computed(() =>
    isAdmin.value && props.request?.status === "awaiting_confirmation"
)

const canCancel = computed(() =>
    !["completed", "canceled"].includes(props.request?.status)
)

const review = computed(() => props.request?.review ?? null)

const reviewPending = computed(() => review.value?.status === "pending")

const coverUrl = computed(() =>
    props.request?.book?.cover ?? "/images/book-placeholder.png"
)

const bookTitle = computed(() =>
    props.request?.book_name ?? props.request?.book?.name ?? "Deleted book"
)

const citizenName = computed(() =>
    props.request?.citizen_name ?? props.request?.citizen?.name ?? "-"
)

const citizenEmail = computed(() =>
    props.request?.citizen_email ?? props.request?.citizen?.email ?? "-"
)

const requestedDate = computed(() =>
    props.request?.requested_at
        ? new Date(props.request.requested_at).toLocaleDateString()
        : "-"
)

const dueDate = computed(() =>
    props.request?.due_at
        ? new Date(props.request.due_at).toLocaleDateString()
        : "-"
)

const receivedDate = computed(() =>
    props.request?.received_at
        ? new Date(props.request.received_at).toLocaleDateString()
        : "-"
)

const bookColSpanClass = computed(() => {
    return isAdmin.value ? "lg:col-span-2" : "lg:col-span-3"
})

const statusLabel = (st) => {
    if (st === "active") return "Active"
    if (st === "awaiting_confirmation") return "Awaiting confirmation"
    if (st === "completed") return "Completed"
    if (st === "canceled") return "Canceled"
    return st
}

const statusBadge = (st) => {
    if (st === "active") return "badge badge-success"
    if (st === "awaiting_confirmation") return "badge badge-warning"
    if (st === "completed") return "badge badge-neutral"
    if (st === "canceled") return "badge badge-error"
    return "badge"
}

const reviewBadge = (st) => {
    if (st === "pending") return "badge badge-warning"
    if (st === "active") return "badge badge-success"
    if (st === "rejected") return "badge badge-error"
    return "badge"
}

const reviewLabel = (st) => {
    if (st === "pending") return "Pending"
    if (st === "active") return "Approved"
    if (st === "rejected") return "Rejected"
    return st ?? "-"
}

const cancelModal = ref(null)
const returnedModal = ref(null)
const confirmModal = ref(null)

const showReviewForm = ref(false)

const reviewForm = useForm({
    rating: 5,
    comment: "",
})

const confirmForm = useForm({
    review_action: "approve",
    rejection_reason: "",
})

const cancelRequest = () => {
    router.patch(route("requests.cancel", props.request.id), {}, {
        preserveScroll: true,
        onSuccess: () => cancelModal.value?.close(),
    })
}

const markReturned = () => {
    router.patch(route("requests.returned", props.request.id), {}, {
        preserveScroll: true,
        onSuccess: () => returnedModal.value?.close(),
    })
}

const submitReview = () => {
    reviewForm.post(route("requests.review.store", props.request.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReviewForm.value = false
            reviewForm.reset()
        },
    })
}

const confirmReception = () => {
    confirmForm.patch(route("requests.confirmReceived", props.request.id), {
        preserveScroll: true,
        onSuccess: () => confirmModal.value?.close(),
    })
}
</script>

<template>
    <public-layout :title="`Request #${request.number}`">
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="flex items-center justify-between bg-gray-800 p-6 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">
                        Request #{{ request.number }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <span :class="statusBadge(request.status)">
                            {{ statusLabel(request.status) }}
                        </span>

                        <span class="text-sm opacity-70">
                            Requested: <b>{{ requestedDate }}</b>
                        </span>

                        <span class="text-sm opacity-70">
                            Due: <b>{{ dueDate }}</b>
                        </span>

                        <span v-if="request.received_at" class="text-sm opacity-70">
                            Received: <b>{{ receivedDate }}</b>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/requests" class="btn btn-ghost">
                        Back
                    </Link>

                    <button
                        v-if="canMarkReturned"
                        class="btn bg-[#5754E8]"
                        @click="returnedModal.open()"
                    >
                        Mark as Returned
                    </button>

                    <button
                        v-if="canConfirm"
                        class="btn bg-[#5754E8]"
                        @click="confirmModal.open()"
                    >
                        Confirm Reception
                    </button>

                    <button
                        v-if="isAdmin && canCancel"
                        class="btn bg-red-600"
                        @click="cancelModal.open()"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div :class="[bookColSpanClass, 'bg-gray-800 p-6 rounded-lg']">
                <h2 class="text-xl font-semibold mb-4">Book</h2>

                    <div class="flex gap-4">
                        <img :src="coverUrl" class="w-32 h-32 object-contain bg-white rounded"  alt="cover"/>

                        <div>
                            <h3 class="text-lg font-semibold">{{ bookTitle }}</h3>

                            <p class="opacity-70">
                                Publisher: {{ request.book?.publisher?.name ?? "-" }}
                            </p>

                            <Link
                                v-if="request.book?.id"
                                :href="route('books.show', request.book.id)"
                                class="btn btn-sm btn-outline mt-3"
                            >
                                View book
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="isAdmin" class="bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Citizen</h2>

                    <p><b>Name:</b> {{ citizenName }}</p>
                    <p><b>Email:</b> {{ citizenEmail }}</p>
                </div>
            </div>

            <div v-if="isOwner || isAdmin" class="bg-gray-800 p-6 rounded-lg space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Review</h2>

                    <button
                        v-if="isOwner && canLeaveReview && !showReviewForm"
                        class="btn bg-[#5754E8] py-2 px-2"
                        @click="showReviewForm = true"
                    >
                        Leave a review
                    </button>
                </div>

                <div v-if="review">
                    <div class="flex items-center gap-3 mb-2">
                        <span><b>Rating:</b> {{ review.rating }}/5</span>
                        <span :class="reviewBadge(review.status)">
                            {{ reviewLabel(review.status) }}
                        </span>
                    </div>

                    <div v-if="review.comment" class="bg-gray-900 p-4 rounded whitespace-pre-line">
                        {{ review.comment }}
                    </div>

                    <div
                        v-if="review.status === 'rejected' && review.rejection_reason"
                        class="alert alert-warning mt-3"
                    >
                        Rejection reason: {{ review.rejection_reason }}
                    </div>

                    <p
                        v-if="review.status === 'pending' && isOwner"
                        class="text-sm opacity-70"
                    >
                        Your review is pending approval by an admin.
                    </p>
                </div>

                <div v-else-if="showReviewForm" class="space-y-4">
                    <div>
                        <select
                            v-model="reviewForm.rating"
                            class="select bg-white text-black px-2 py-2 rounded-lg w-32"
                        >
                            <option v-for="n in 5" :key="n" :value="6 - n">
                                {{ 6 - n }}
                            </option>
                        </select>
                    </div>

                    <div>
        <textarea
            v-model="reviewForm.comment"
            class="textarea bg-white text-black w-full min-h-[120px]"
            placeholder="Optional comment"
        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="btn btn-ghost px-2 py-2"
                            @click="showReviewForm = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="btn bg-[#5754E8] hover:bg-[#3c39e3] px-2 py-2"
                            @click="submitReview"
                        >
                            Submit review
                        </button>
                    </div>
                </div>


                <p v-else class="opacity-70">
                    No review for this request yet.
                </p>
            </div>

            <ConfirmModal
                ref="confirmModal"
                title="Confirm reception"
                confirmText="Confirm"
                @confirm="confirmReception"
            >
                <div class="space-y-4">
                    <p>This will complete the request.</p>

                    <div v-if="reviewPending" class="bg-gray-900 p-4 rounded space-y-3">
                        <p><b>Pending review</b></p>

                        <p class="whitespace-pre-line">
                            {{ review.comment }}
                        </p>

                        <label class="flex gap-2">
                            <input type="radio" value="approve" v-model="confirmForm.review_action" />
                            Approve
                        </label>

                        <label class="flex gap-2">
                            <input type="radio" value="reject" v-model="confirmForm.review_action" />
                            Reject
                        </label>

                        <textarea
                            v-if="confirmForm.review_action === 'reject'"
                            v-model="confirmForm.rejection_reason"
                            class="textarea bg-white text-black"
                            placeholder="Rejection reason"
                        />
                    </div>
                </div>
            </ConfirmModal>

            <ConfirmModal
                ref="returnedModal"
                title="Mark as returned"
                confirmText="Confirm"
                @confirm="markReturned"
            />

            <ConfirmModal
                ref="cancelModal"
                title="Cancel request"
                confirmText="Cancel"
                danger
                @confirm="cancelRequest"
            />
        </div>
    </public-layout>
</template>
