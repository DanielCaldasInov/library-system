<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue"
import { Link, useForm } from "@inertiajs/vue3"

const props = defineProps({
    cart: Object,
})

const form = useForm({
    delivery_name: "",
    delivery_address_line1: "",
    delivery_address_line2: "",
    delivery_zip: "",
    delivery_city: "",
    delivery_country: "PT",
})

const formatMoney = (cents, currency = "EUR") => {
    return new Intl.NumberFormat("pt-PT", {
        style: "currency",
        currency,
    }).format((cents ?? 0) / 100)
}

const submit = () => {
    form.post(route("checkout.store"), {
        preserveScroll: true,
    })
}
</script>

<template>
    <PublicLayout title="Delivery details">
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold">Delivery</h1>
                <Link :href="route('cart.index')" class="btn btn-ghost">Back to cart</Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- FORM -->
                <div class="lg:col-span-2 bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Delivery address</h2>

                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm opacity-70">Full name</label>
                                <input
                                    v-model="form.delivery_name"
                                    type="text"
                                    class="input input-bordered bg-white text-black w-full"
                                    placeholder="Name of recipient"
                                />
                                <div v-if="form.errors.delivery_name" class="text-sm text-red-300">
                                    {{ form.errors.delivery_name }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm opacity-70">Country</label>
                                <select
                                    v-model="form.delivery_country"
                                    class="select select-bordered bg-white text-black w-full"
                                >
                                    <option value="PT">Portugal (PT)</option>
                                    <option value="ES">Spain (ES)</option>
                                    <option value="FR">France (FR)</option>
                                    <option value="DE">Germany (DE)</option>
                                    <option value="IT">Italy (IT)</option>
                                    <option value="GB">United Kingdom (GB)</option>
                                    <option value="US">United States (US)</option>
                                </select>
                                <div v-if="form.errors.delivery_country" class="text-sm text-red-300">
                                    {{ form.errors.delivery_country }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm opacity-70">Address line 1</label>
                            <input
                                v-model="form.delivery_address_line1"
                                type="text"
                                class="input input-bordered bg-white text-black w-full"
                                placeholder="Street and number"
                            />
                            <div v-if="form.errors.delivery_address_line1" class="text-sm text-red-300">
                                {{ form.errors.delivery_address_line1 }}
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm opacity-70">Address line 2 (optional)</label>
                            <input
                                v-model="form.delivery_address_line2"
                                type="text"
                                class="input input-bordered bg-white text-black w-full"
                                placeholder="Apartment, floor, etc."
                            />
                            <div v-if="form.errors.delivery_address_line2" class="text-sm text-red-300">
                                {{ form.errors.delivery_address_line2 }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm opacity-70">ZIP / Postal code</label>
                                <input
                                    v-model="form.delivery_zip"
                                    type="text"
                                    class="input input-bordered bg-white text-black w-full"
                                    placeholder="e.g. 1000-000"
                                />
                                <div v-if="form.errors.delivery_zip" class="text-sm text-red-300">
                                    {{ form.errors.delivery_zip }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm opacity-70">City</label>
                                <input
                                    v-model="form.delivery_city"
                                    type="text"
                                    class="input input-bordered bg-white text-black w-full"
                                    placeholder="e.g. Lisboa"
                                />
                                <div v-if="form.errors.delivery_city" class="text-sm text-red-300">
                                    {{ form.errors.delivery_city }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <Link :href="route('cart.index')" class="btn btn-ghost">
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                class="btn bg-[#5754E8] hover:bg-[#3c39e3] px-6"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? "Redirecting..." : "Pay with Stripe" }}
                            </button>
                        </div>

                        <p class="text-sm opacity-70 mt-2">
                            You’ll be redirected to Stripe to complete the payment.
                        </p>
                    </form>
                </div>

                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Order summary</h2>

                    <div class="space-y-3">
                        <div
                            v-for="item in cart.items"
                            :key="item.id"
                            class="flex gap-3 items-center bg-gray-900/70 rounded p-3"
                        >
                            <img
                                :src="item.book?.cover ?? '/images/book-placeholder.png'"
                                class="w-12 h-14 object-contain bg-white rounded"
                                alt="cover"
                            />

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate">
                                    {{ item.book?.name ?? "Deleted book" }}
                                </p>
                                <p class="text-xs opacity-70">
                                    Qty: {{ item.qty }}
                                </p>
                            </div>

                            <div class="text-sm font-semibold whitespace-nowrap">
                                {{ formatMoney(Math.round((item.book?.price ?? 0) * 100) * item.qty) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-gray-700 pt-4 flex items-center justify-between">
                        <span class="opacity-70">Total</span>
                        <b class="text-lg">{{ formatMoney(cart.total, cart.currency ?? "EUR") }}</b>
                    </div>

                    <div class="mt-3 text-xs opacity-60">
                        Currency: {{ cart.currency ?? "EUR" }}
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
