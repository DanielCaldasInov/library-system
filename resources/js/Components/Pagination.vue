<script setup>
import { computed } from "vue"
import { router } from "@inertiajs/vue3"

const props = defineProps({
    meta: { type: Object, required: true },
    route: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
})

const current = computed(() => Number(props.meta?.current_page ?? 1))
const last = computed(() => Number(props.meta?.last_page ?? 1))

const canPrev = computed(() => current.value > 1)
const canNext = computed(() => current.value < last.value)

const goTo = (page) => {
    const p = Number(page)
    if (!p || p < 1 || p > last.value || p === current.value) return

    router.get(props.route, { ...(props.filters ?? {}), page: p }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
}

const items = computed(() => {
    const L = last.value
    const C = current.value

    if (L <= 1) return []

    if (L <= 9) {
        return Array.from({ length: L }, (_, i) => ({
            type: "page",
            page: i + 1,
        }))
    }

    const pages = new Set()

    pages.add(1)
    pages.add(2)

    for (let p = C - 2; p <= C + 2; p++) {
        if (p >= 1 && p <= L) pages.add(p)
    }

    pages.add(L - 1)
    pages.add(L)

    const sorted = Array.from(pages).sort((a, b) => a - b)

    const out = []
    for (let i = 0; i < sorted.length; i++) {
        out.push({ type: "page", page: sorted[i] })

        const next = sorted[i + 1]
        if (next && next - sorted[i] > 1) {
            out.push({ type: "ellipsis" })
        }
    }

    return out
})
</script>

<template>
    <div
        v-if="meta?.last_page > 1"
        class="mt-4 flex flex-wrap justify-center gap-2 bg-gray-800 px-4 py-3 rounded-lg"
    >
        <button
            class="btn btn-sm rounded-md px-3 hover:bg-black"
            :class="!canPrev ? 'bg-gray-600 cursor-not-allowed' : ''"
            :disabled="!canPrev"
            @click="goTo(current - 1)"
        >
            Previous
        </button>

        <template v-for="(it, idx) in items" :key="`${it.type}-${idx}-${it.page ?? 'e'}`">
            <span
                v-if="it.type === 'ellipsis'"
                class="px-2 py-1 opacity-70 select-none"
            >
                ...
            </span>

            <button
                v-else
                class="btn btn-sm rounded-md px-3 hover:bg-black"
                :class="it.page === current ? 'btn-primary' : ''"
                @click="goTo(it.page)"
            >
                {{ it.page }}
            </button>
        </template>

        <button
            class="btn btn-sm rounded-md px-3 hover:bg-black"
            :class="!canNext ? 'bg-gray-600 cursor-not-allowed' : ''"
            :disabled="!canNext"
            @click="goTo(current + 1)"
        >
            Next
        </button>
    </div>
</template>
