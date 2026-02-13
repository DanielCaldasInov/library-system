<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { Link } from "@inertiajs/vue3"
import { computed } from "vue"

const props = defineProps({
    order: Object,
})

const formatMoney = (cents, currency = "EUR") =>
    new Intl.NumberFormat("pt-PT", { style: "currency", currency: (currency ?? "EUR").toUpperCase() })
        .format((cents ?? 0) / 100)

const fmtDateTime = (v) => (v ? new Date(v).toLocaleString() : "—")

const statusLabel = (st) => {
    if (st === "pending_payment") return "Pending payment"
    if (st === "paid") return "Paid"
    if (st === "expired") return "Expired"
    if (st === "canceled") return "Canceled"
    return st ?? "—"
}

const statusBadge = (st) => {
    if (st === "pending_payment") return "badge badge-warning"
    if (st === "paid") return "badge badge-success"
    if (st === "expired") return "badge badge-error"
    if (st === "canceled") return "badge badge-neutral"
    return "badge"
}

const address = computed(() => {
    const o = props.order ?? {}
    const parts = [
        o.delivery_address_line1,
        o.delivery_address_line2,
        `${o.delivery_zip ?? ""} ${o.delivery_city ?? ""}`.trim(),
        o.delivery_country,
    ].filter(Boolean)
    return parts.join("\n")
})
</script>

<template>
    <PublicLayout :title="`Order #${order.id}`">
        <div class="p-6 max-w-5xl mx-auto space-y-6">
            <div class="flex items-start justify-between bg-gray-800 p-6 rounded-lg">
                <div>
                    <h1 class="text-3xl font-bold">Order #{{ order.id }}</h1>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
            <span :class="statusBadge(order.status)">
              {{ statusLabel(order.status) }}
            </span>

                        <span class="text-sm opacity-70">
              Created: <b>{{ fmtDateTime(order.created_at) }}</b>
            </span>

                        <span class="text-sm opacity-70">
              Updated: <b>{{ fmtDateTime(order.updated_at) }}</b>
            </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('orders.index')" class="btn btn-ghost">
                        Back
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Items</h2>

                    <div v-if="!order.items?.length" class="opacity-70">
                        No items
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="it in order.items"
                            :key="it.id"
                            class="bg-gray-900/70 rounded p-4 flex gap-4 items-center"
                        >
                            <img
                                :src="it.book?.cover ?? '/images/book-placeholder.png'"
                                class="w-16 h-20 object-contain bg-white rounded"
                                alt="cover"
                            />

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate">
                                    {{ it.book?.name ?? it.book_name ?? "Book" }}
                                </p>
                                <p class="text-sm opacity-70">
                                    Unit: {{ formatMoney(it.unit_price, order.currency) }} • Qty: {{ it.qty }}
                                </p>

                                <Link
                                    v-if="it.book?.id"
                                    :href="route('books.show', it.book.id)"
                                    class="btn btn-sm btn-outline mt-2"
                                >
                                    View book
                                </Link>
                            </div>

                            <div class="text-right font-semibold w-28">
                                {{ formatMoney(it.line_total, order.currency) }}
                            </div>
                        </div>

                        <div class="bg-gray-900 rounded-lg p-5 flex items-center justify-between">
                            <span class="text-lg opacity-70">Total</span>
                            <span class="text-xl font-bold">
                {{ formatMoney(order.total_amount, order.currency) }}
              </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg space-y-5">
                    <div>
                        <h2 class="text-xl font-semibold mb-2">Delivery</h2>
                        <div class="bg-gray-900/70 rounded p-4">
                            <p class="font-semibold">{{ order.delivery_name }}</p>
                            <pre class="whitespace-pre-line text-sm opacity-80 mt-2">{{ address }}</pre>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold mb-2">Payment</h2>

                        <!--
                        <div class="bg-gray-900/70 rounded p-4 space-y-2">
                            <p class="text-sm opacity-80">
                                <b>Status:</b> {{ statusLabel(order.status) }}
                            </p>
                            <p class="text-sm opacity-80">
                                <b>Checkout Session:</b> {{ order.stripe_checkout_session_id ?? "—" }}
                            </p>
                            <p class="text-sm opacity-80">
                                <b>Payment Intent:</b> {{ order.stripe_payment_intent_id ?? "—" }}
                            </p>
                        </div>
                        -->

                    </div>

                    <div class="text-sm opacity-70">
                        If the status is still pending after payment, wait a few seconds for the webhook to confirm it.
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
