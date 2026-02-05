<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import ConfirmModal from "@/Components/ConfirmModal.vue"
import { Link, useForm, usePage } from "@inertiajs/vue3"
import { computed, ref, watch } from "vue"

const props = defineProps({
    review: Object,
})

const page = usePage()
const isAdmin = computed(() => !!page.props.auth?.user?.is_admin)

const statusLabel = (st) => {
    if (st === "pending") return "Pending"
    if (st === "active") return "Active"
    if (st === "rejected") return "Rejected"
    return st ?? "-"
}

const statusBadge = (st) => {
    if (st === "pending") return "badge badge-warning"
    if (st === "active") return "badge badge-success"
    if (st === "rejected") return "badge badge-error"
    return "badge"
}

const fmtDate = (v) => (v ? new Date(v).toLocaleString() : "—")

const coverUrl = computed(() => {
    const c = props.review?.book?.cover
    return c ? c : "/images/book-placeholder.png"
})

const canEdit = computed(() => isAdmin.value)

const form = useForm({
    status: props.review?.status === "rejected" ? "rejected" : "active",
    rejection_reason: props.review?.rejection_reason ?? "",
})

watch(
    () => props.review,
    (r) => {
        if (!r) return
        form.status = r.status === "rejected" ? "rejected" : "active"
        form.rejection_reason = r.rejection_reason ?? ""
        form.clearErrors()
    }
)

watch(
    () => form.status,
    (st) => {
        if (st !== "rejected") {
            form.rejection_reason = ""
            form.clearErrors("rejection_reason")
        }
    }
)

const editModal = ref(null)

const openEdit = () => {
    form.clearErrors()
    form.status = props.review?.status === "rejected" ? "rejected" : "active"
    form.rejection_reason = props.review?.rejection_reason ?? ""
    editModal.value?.open()
}

const closeEdit = () => editModal.value?.close()

const submitEdit = () => {
    form.clearErrors()

    if (form.status === "rejected" && !String(form.rejection_reason || "").trim()) {
        form.setError("rejection_reason", "Rejection reason is required when rejecting a review.")
        return
    }

    if (form.status !== "rejected") {
        form.rejection_reason = ""
    }

    form.put(route("reviews.update", props.review.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEdit()
            form.clearErrors()
        },
    })
}

</script>

<template>
    <PublicLayout :title="`Review #${review.id}`">
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            <div class="flex items-start justify-between bg-gray-800 p-6 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">
                        Review #{{ review.id }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
            <span :class="statusBadge(review.status)">
              {{ statusLabel(review.status) }}
            </span>

                        <span class="text-sm opacity-70">
              Created: <span class="font-semibold">{{ fmtDate(review.created_at) }}</span>
            </span>

                        <span class="text-sm opacity-70">
              Updated: <span class="font-semibold">{{ fmtDate(review.updated_at) }}</span>
            </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('reviews.index')" class="btn bg-black/40 hover:bg-black">
                        Back
                    </Link>

                    <button
                        v-if="canEdit"
                        type="button"
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] py-2 px-3"
                        @click="openEdit"
                    >
                        Edit
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">
                        Review details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-900/70 rounded p-4">
                            <p class="text-sm opacity-70">Rating</p>
                            <p class="font-semibold">
                                {{ review.rating ?? "—" }}/5
                            </p>
                        </div>

                        <div class="bg-gray-900/70 rounded p-4">
                            <p class="text-sm opacity-70">Status</p>
                            <p class="font-semibold">
                <span :class="statusBadge(review.status)">
                  {{ statusLabel(review.status) }}
                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 bg-gray-900/70 rounded p-4">
                        <p class="text-sm opacity-70 mb-2">Comment</p>
                        <p class="whitespace-pre-line">
                            {{ review.comment || "—" }}
                        </p>
                    </div>

                    <div
                        v-if="review.status === 'rejected' && review.rejection_reason"
                        class="alert alert-warning mt-4"
                    >
            <span>
              Rejection reason: {{ review.rejection_reason }}
            </span>
                    </div>

                    <div
                        v-if="review.status === 'pending'"
                        class="mt-4 text-sm opacity-70"
                    >
                        This review is pending approval by an admin.
                    </div>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg space-y-4">
                    <div>
                        <h2 class="text-xl font-semibold mb-3">
                            Citizen
                        </h2>

                        <div class="bg-gray-900/70 rounded p-4">
                            <p class="font-semibold">
                                {{ review.user?.name ?? "—" }}
                            </p>
                            <p class="text-sm opacity-70">
                                {{ review.user?.email ?? "—" }}
                            </p>

                            <div class="mt-3">
                                <Link
                                    v-if="review.user?.id"
                                    :href="route('users.show', review.user.id)"
                                    class="btn btn-sm btn-outline"
                                >
                                    View user
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold mb-3">
                            Request
                        </h2>

                        <div class="bg-gray-900/70 rounded p-4">
                            <p class="font-semibold">
                                #{{ review.request?.number ?? "—" }}
                            </p>
                            <p class="text-sm opacity-70">
                                Status: {{ review.request?.status ?? "—" }}
                            </p>

                            <div class="mt-3">
                                <Link
                                    v-if="review.request?.id"
                                    :href="route('requests.show', review.request.id)"
                                    class="btn btn-sm btn-outline"
                                >
                                    View request
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg">
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
                        <p class="text-lg font-semibold">
                            {{ review.book?.name ?? "—" }}
                        </p>

                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-gray-900/70 rounded p-3">
                                <p class="text-sm opacity-70">ISBN</p>
                                <p class="font-mono">{{ review.book?.ISBN ?? "—" }}</p>
                            </div>

                            <div class="bg-gray-900/70 rounded p-3">
                                <p class="text-sm opacity-70">Price</p>
                                <p class="font-semibold">€{{ Number(review.book?.price ?? 0).toFixed(2) }}</p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <Link
                                v-if="review.book?.id"
                                :href="route('books.show', review.book.id)"
                                class="btn btn-sm btn-outline"
                            >
                                View book
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmModal
                ref="editModal"
                title="Edit review status"
                :message="`Update the status for review #${review.id}.`"
                confirmText="Save"
                cancelText="Back"
                :danger="form.status === 'rejected'"
                :confirmDisabled="form.processing"
                @confirm="submitEdit"
                @cancel="closeEdit"
            >
                <div class="mt-3 space-y-3">
                    <div class="flex flex-col gap-2">
                        <label class="label">
                            <span class="label-text text-white font-bold">Status</span>
                        </label>

                        <select v-model="form.status" class="select select-bordered bg-white text-black w-full">
                            <option value="active">Active</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <div v-if="form.errors.status" class="text-sm text-red-300">
                            {{ form.errors.status }}
                        </div>
                    </div>

                    <div v-if="form.status === 'rejected'" class="flex flex-col gap-2">
                        <label class="label">
                            <span class="label-text text-white font-bold">Rejection reason</span>
                        </label>

                        <textarea
                            v-model="form.rejection_reason"
                            class="textarea textarea-bordered bg-white text-black w-full min-h-[110px]"
                            placeholder="Write the reason for rejection..."
                        />

                        <div v-if="form.errors.rejection_reason" class="text-sm text-red-300">
                            {{ form.errors.rejection_reason }}
                        </div>
                    </div>

                    <div class="text-sm opacity-70">
                        Status can be updated any time by an admin.
                    </div>
                </div>
            </ConfirmModal>
        </div>
    </PublicLayout>
</template>
