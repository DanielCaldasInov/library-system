<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import DataTable from "@/Components/DataTable.vue"
import Pagination from "@/Components/Pagination.vue"
import SearchForm from "@/Components/SearchForm.vue"
import { router, Link, usePage } from "@inertiajs/vue3"
import { computed } from "vue"

const props = defineProps({
    orders: Object,
    filters: Object,
    sort: String,
    direction: String,
    searchOptions: Array,
    statusOptions: Array,
})

const page = usePage()
const isAdmin = computed(() => !!page.props.auth?.user?.is_admin)

const formatMoney = (cents, currency = "EUR") =>
    new Intl.NumberFormat("pt-PT", {
        style: "currency",
        currency: (currency ?? "EUR").toUpperCase(),
    }).format((cents ?? 0) / 100)

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

const sortBy = (field) => {
    const same = props.sort === field
    const nextDirection = same ? (props.direction === "asc" ? "desc" : "asc") : "asc"

    router.get(route("orders.index"), {
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
    <PublicLayout title="Orders">
        <div class="p-6 flex flex-col items-center">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold bg-gray-800 px-6 py-4 rounded-lg">
                    Orders
                </h1>
            </div>

            <div class="w-full max-w-6xl mx-auto space-y-4">
                <SearchForm
                    action="/orders"
                    :filters="{ ...filters, sort, direction }"
                    :options="searchOptions"
                    :status-options="statusOptions"
                />

                <div
                    v-if="!orders?.data?.length"
                    class="bg-gray-800 rounded-lg p-8 text-center"
                >
                    <h2 class="text-xl font-semibold mb-2">
                        No orders yet
                    </h2>
                    <p class="opacity-70">
                        There are no orders to display right now.
                    </p>
                </div>

                <DataTable
                    v-else
                    :data="orders"
                    :filters="{ ...filters, sort, direction }"
                    route="/orders"
                >
                    <template #head>
                        <tr>
                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('id')">
                                ID {{ arrow('id') }}
                            </th>

                            <th v-if="isAdmin">
                                Citizen
                            </th>

                            <th>Status</th>

                            <th class="cursor-pointer text-right hover:bg-gray-700 transition-colors" @click="sortBy('total_amount')">
                                Total {{ arrow('total_amount') }}
                            </th>

                            <th class="cursor-pointer hover:bg-gray-700 transition-colors" @click="sortBy('created_at')">
                                Created {{ arrow('created_at') }}
                            </th>

                            <th class="text-right">Actions</th>
                        </tr>
                    </template>

                    <template #body="{ rows }">
                        <tr v-for="o in rows" :key="o.id">
                            <td class="font-mono">
                                #{{ o.id }}
                            </td>

                            <td v-if="isAdmin" class="truncate max-w-xs">
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">
                                        {{ o.user?.name ?? 'Deleted user' }}
                                    </div>
                                    <div class="text-xs opacity-70 truncate">
                                        {{ o.user?.email ?? '—' }}
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span :class="statusBadge(o.status)">
                                    {{ statusLabel(o.status) }}
                                </span>
                            </td>

                            <td class="text-right font-semibold whitespace-nowrap">
                                {{ formatMoney(o.total_amount, o.currency) }}
                            </td>

                            <td class="whitespace-nowrap">
                                {{ fmtDateTime(o.created_at) }}
                            </td>

                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link
                                        :href="route('orders.show', o.id)"
                                        class="btn hover:bg-[#5754E8]"
                                    >
                                        Details
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </template>
                </DataTable>

                <Pagination
                    v-if="orders?.data?.length"
                    :meta="orders"
                    route="/orders"
                    :filters="{ ...filters, sort, direction }"
                />
            </div>
        </div>
    </PublicLayout>
</template>
