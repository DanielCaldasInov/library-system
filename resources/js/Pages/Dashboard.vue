<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DashboardSummary from '@/Components/DashboardSummary.vue'

defineProps({
    stats: Object,
})
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <DashboardSummary :stats="stats" />
            </div>
        </div>
    </AppLayout>
</template>
