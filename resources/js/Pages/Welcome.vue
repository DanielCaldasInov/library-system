<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ApplicationMark from "@/Components/ApplicationMark.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
})
</script>

<template>
    <Head title="Home"/>
    <div class="min-h-screen bg-base-200">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow">
            <div class="flex-1">
                <Link href="/" class="btn btn-ghost text-xl">
                    <ApplicationMark class="block h-9 w-auto" /> InovStore
                </Link>
            </div>

            <div class="flex-none gap-2">
                <template v-if="$page.props.auth?.user">
                    <Link href="/dashboard" class="btn btn-ghost">
                        Dashboard
                    </Link>

                    <Link href="/books" class="btn btn-ghost">
                        Books
                    </Link>

                    <Link href="/authors" class="btn btn-ghost">
                        Authors
                    </Link>

                    <Link href="/publishers" class="btn btn-ghost">
                        Publishers
                    </Link>
                </template>

                <template v-else>
                    <Link v-if="canLogin" href="/login" class="btn btn-ghost">
                        Login
                    </Link>
                    <Link v-if="canRegister" href="/register" class="btn btn-primary">
                        Register
                    </Link>
                </template>
            </div>
        </div>

        <div class="hero py-16">
            <div class="hero-content flex-col lg:flex-row gap-10 max-w-6xl">
                <div class="w-full lg:w-1/2">
                    <h1 class="text-5xl font-bold leading-tight">
                        Discover your next great read
                    </h1>
                    <p class="py-6 opacity-80">
                        A simple library management experience built with Laravel, Jetstream and Inertia.
                        Manage books, authors, and publishers with a clean interface.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <template v-if="$page.props.auth?.user">
                            <Link href="/books" class="btn btn-primary">
                                Browse Books
                            </Link>
                            <Link href="/dashboard" class="btn btn-outline">
                                Go to Dashboard
                            </Link>
                        </template>

                        <template v-else>
                            <Link href="/login" class="btn btn-primary">
                                Sign in
                            </Link>
                            <Link v-if="canRegister" href="/register" class="btn btn-outline">
                                Create account
                            </Link>
                        </template>
                    </div>

                    <div class="mt-6 text-sm opacity-60">
                        Powered by Laravel {{ laravelVersion }} · PHP {{ phpVersion }}
                    </div>
                </div>

                <div class="w-full lg:w-1/2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h2 class="card-title">Books</h2>
                            <p class="opacity-80">Catalog and organize your collection.</p>
                            <div class="card-actions justify-end">
                                <Link v-if="$page.props.auth?.user" href="/books" class="btn btn-sm btn-outline">
                                    Open
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h2 class="card-title">Authors</h2>
                            <p class="opacity-80">Manage authors and their books.</p>
                            <div class="card-actions justify-end">
                                <Link v-if="$page.props.auth?.user" href="/authors" class="btn btn-sm btn-outline">
                                    Open
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow sm:col-span-2">
                        <div class="card-body">
                            <h2 class="card-title">Publishers</h2>
                            <p class="opacity-80">
                                Track publishers and the books they publish.
                            </p>
                            <div class="card-actions justify-end">
                                <Link v-if="$page.props.auth?.user" href="/publishers" class="btn btn-sm btn-outline">
                                    Open
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer footer-center p-6 bg-base-100 text-base-content">
            <aside>
                <p class="opacity-70">
                    Library System · Built with Laravel + Inertia + Vue + DaisyUI
                </p>
            </aside>
        </footer>
    </div>
</template>
