<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ApplicationMark from "@/Components/ApplicationMark.vue";

defineProps({
    title: {
        type: String,
        default: 'InovStore',
    },
})
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-base-200 flex flex-col">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow relative">
            <!-- Logo -->
            <div class="flex-1">
                <Link href="/" class="btn btn-ghost text-xl">
                    <ApplicationMark class="block h-9 w-auto" /> InovStore
                </Link>
            </div>

            <!-- Links -->
            <div class="absolute left-1/2 -translate-x-1/2 flex gap-2">
                <Link href="/books" class="btn btn-ghost">Books</Link>
                <Link href="/authors" class="btn btn-ghost">Authors</Link>
                <Link href="/publishers" class="btn btn-ghost">Publishers</Link>
                <Link v-if="$page.props.auth?.user" href="/requests" class="btn btn-ghost">Requests</Link>
                <Link v-if="$page.props.auth?.user" href="/users" class="btn btn-ghost">Users</Link>
            </div>

            <!-- Auth -->
            <div class="flex flex-row gap-2">
                <template v-if="!$page.props.auth?.user">
                    <Link
                        v-if="$page.props.routes?.canLogin"
                        href="/login"
                        class="btn btn-error"
                    >
                        Login
                    </Link>

                    <Link
                        v-if="$page.props.routes?.canRegister"
                        href="/register"
                        class="btn btn-primary"
                    >
                        Register
                    </Link>
                </template>

                <template v-else>
                    <Link
                        v-if="$page.props.auth.user.is_admin"
                        href="/dashboard"
                        class="btn btn-accent"
                    >
                        Dashboard
                    </Link>

                    <Link :href="route('profile.show') " class="btn btn-secondary">
                        Profile
                    </Link>

                    <button
                        @click="router.post(route('logout'))"
                        class="btn bg-red-500 hover:bg-red-800 px-3"
                    >
                        Logout
                    </button>
                </template>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 bg-[#191E24]">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-base-300 bg-base-100">
            <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-sm opacity-70">
                    © {{ new Date().getFullYear() }} InovStore Library
                </div>

                <div class="text-sm opacity-70 flex gap-4">
          <span class="flex items-center gap-2">
            <span class="text-lg">📚</span>
            <span>Reading • Learning • Community</span>
          </span>
                </div>

                <div class="text-sm opacity-70">
                    Minimal library system
                </div>
            </div>
        </footer>
    </div>
</template>
