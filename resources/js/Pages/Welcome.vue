<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from "@/Layouts/PublicLayout.vue";
</script>

<template>
    <PublicLayout title="Home">
        <div class="hero py-16">
            <div class="hero-content flex-col lg:flex-row gap-10 max-w-6xl">
                <div class="w-full lg:w-1/2">
                    <h1 class="text-5xl font-bold leading-tight">
                        Welcome to InovStore Library
                    </h1>

                    <p class="py-6 opacity-80">
                        Explore our catalog of books, authors, and publishers.
                        A minimal and modern library system built with Laravel + Inertia + Vue.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link href="/books" class="btn btn-primary">
                            Browse Books
                        </Link>

                        <Link href="/authors" class="btn btn-primary">
                            Explore Authors
                        </Link>

                        <Link href="/publishers" class="btn btn-primary">
                            View Publishers
                        </Link>
                    </div>

                    <div class="mt-6 text-sm opacity-70">
                        Public catalog available for everyone.
                    </div>
                </div>

                <div class="w-full lg:w-1/2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h2 class="card-title">Books</h2>
                            <p class="opacity-80">Browse the library catalog and discover new reads.</p>
                            <div class="card-actions justify-end">
                                <Link href="/books" class="btn btn-sm btn-outline">Open</Link>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h2 class="card-title">Authors</h2>
                            <p class="opacity-80">Find authors and view the books they’ve written.</p>
                            <div class="card-actions justify-end">
                                <Link href="/authors" class="btn btn-sm btn-outline">Open</Link>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-base-100 shadow sm:col-span-2">
                        <div class="card-body">
                            <h2 class="card-title">Publishers</h2>
                            <p class="opacity-80">Explore publishers and their available books.</p>
                            <div class="card-actions justify-end">
                                <Link href="/publishers" class="btn btn-sm btn-outline">Open</Link>
                            </div>
                        </div>
                    </div>

                    <div v-if="!$page.props.auth?.user" class="alert alert-info sm:col-span-2">
            <span>
              Want to request books? Please login or create an account.
            </span>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
