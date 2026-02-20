<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    cart: Object,
})

const updateQty = (item, qty) => {
    // Não permite baixar de 1 nem ultrapassar o stock disponível
    if (qty < 1 || qty > item.book?.available_stock) return

    router.patch(
        route("cart.items.update", item.id),
        { qty },
        { preserveScroll: true }
    )
}

const removeItem = (item) => {
    router.delete(
        route("cart.items.destroy", item.id),
        { preserveScroll: true }
    )
}

const formatMoney = (cents, currency = "EUR") => {
    return new Intl.NumberFormat("pt-PT", {
        style: "currency",
        currency,
    }).format((cents ?? 0) / 100)
}
</script>

<template>
    <PublicLayout title="Shopping Cart">
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <h1 class="text-3xl font-bold">
                Shopping Cart
            </h1>

            <div v-if="$page.props.flash?.warning" class="alert alert-warning mb-4 shadow-lg rounded-lg">
                <span>{{ $page.props.flash.warning }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-error mb-4 shadow-lg rounded-lg">
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <div
                v-if="!cart.items.length"
                class="bg-gray-800 rounded-lg p-8 text-center opacity-70"
            >
                <p class="text-lg">
                    Your cart is empty.
                </p>

                <p class="mt-2">
                    Browse the catalog and add some books 📚
                </p>
            </div>

            <div
                v-else
                class="space-y-4"
            >
                <div
                    v-for="item in cart.items"
                    :key="item.id"
                    class="bg-gray-800 rounded-lg p-4 flex gap-4 items-center"
                >
                    <img
                        :src="item.book?.cover ?? '/images/book-placeholder.png'"
                        class="w-20 h-24 object-contain bg-white rounded"
                        alt="cover"
                    />

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold truncate">
                            {{ item.book?.name ?? 'Deleted book' }}
                        </p>

                        <p class="text-sm opacity-70">
                            {{ formatMoney(item.book?.price * 100) }}
                        </p>

                        <span v-if="item.book?.available_stock === 0" class="text-xs text-red-400 font-semibold mt-1 inline-block">
                            Out of stock
                        </span>
                        <span v-else-if="item.qty === item.book?.available_stock" class="text-xs text-yellow-500 font-semibold mt-1 inline-block">
                            Max stock reached
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            class="btn btn-sm py-2 px-2 bg-gray-700 hover:bg-[#3c39e3]"
                            @click="updateQty(item, item.qty - 1)"
                            :disabled="item.qty <= 1"
                            :class="{'opacity-50 cursor-not-allowed': item.qty <= 1}"
                        >
                            −
                        </button>

                        <span class="w-8 text-center">
                            {{ item.qty }}
                        </span>

                        <button
                            class="btn btn-sm py-2 px-2 bg-gray-700 hover:bg-[#3c39e3]"
                            @click="updateQty(item, item.qty + 1)"
                            :disabled="item.qty >= (item.book?.available_stock ?? 0)"
                            :class="{'opacity-50 cursor-not-allowed': item.qty >= (item.book?.available_stock ?? 0)}"
                        >
                            +
                        </button>
                    </div>

                    <div class="w-24 text-right font-semibold">
                        {{ formatMoney(item.line_total) }}
                    </div>

                    <button
                        class="btn btn-sm bg-red-600 hover:bg-red-700 py-2 px-2 border-none"
                        @click="removeItem(item)"
                    >
                        ✕
                    </button>
                </div>

                <div class="bg-gray-900 rounded-lg p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-lg">
                        <span class="opacity-70">Total:</span>
                        <b class="ml-2">
                            {{ formatMoney(cart.total) }}
                        </b>
                    </div>

                    <button
                        class="btn bg-[#5754E8] hover:bg-[#3c39e3] px-6 text-white"
                        @click="$inertia.visit(route('checkout.delivery'))"
                        :disabled="cart.items.some(i => i.book?.available_stock < i.qty)"
                        :title="cart.items.some(i => i.book?.available_stock < i.qty) ? 'Adjust quantities to match available stock' : ''"
                    >
                        Proceed to delivery
                    </button>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
