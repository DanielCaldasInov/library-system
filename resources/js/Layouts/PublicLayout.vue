<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ApplicationMark from "@/Components/ApplicationMark.vue"
import { ref, onBeforeUnmount } from "vue"

defineProps({
    title: {
        type: String,
        default: 'InovStore',
    },
})

const resolveProfilePhotoUrl = (user) => {
    if (!user) return null

    if (user.profile_photo_url) return user.profile_photo_url

    if (user.profile_photo_path) {
        const base = window.location.origin.replace(/\/$/, '')
        const path = String(user.profile_photo_path).replace(/^\//, '')
        return `${base}/${path}`
    }

    return null
}

const userMenuOpen = ref(false)

const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value
}

const closeUserMenu = () => {
    userMenuOpen.value = false
}

const onKeydown = (e) => {
    if (e.key === 'Escape') closeUserMenu()
}

window.addEventListener('keydown', onKeydown)
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

const logout = () => {
    closeUserMenu()
    router.post(route('logout'))
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-base-200 flex flex-col">
        <div class="navbar bg-base-100 shadow relative">
            <div class="flex-1">
                <Link href="/" class="btn btn-ghost text-xl">
                    <ApplicationMark class="block h-9 w-auto" /> InovStore
                </Link>
            </div>

            <div class="absolute left-1/2 -translate-x-1/2 flex gap-2">
                <Link href="/books" class="btn btn-ghost">Books</Link>
                <Link href="/authors" class="btn btn-ghost">Authors</Link>
                <Link href="/publishers" class="btn btn-ghost">Publishers</Link>
                <Link v-if="$page.props.auth?.user" href="/requests" class="btn btn-ghost">Requests</Link>
                <Link v-if="$page.props.auth?.user" href="/orders" class="btn btn-ghost">Orders</Link>
                <Link v-if="$page.props.auth?.user?.is_admin" href="/reviews" class="btn btn-ghost">Reviews</Link>
                <Link v-if="$page.props.auth?.user?.is_admin" href="/users" class="btn btn-ghost">Users</Link>
            </div>

            <div class="flex flex-row gap-2 items-center">
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
                        href="/cart"
                        class="relative btn bg-[#5754E8] hover:bg-[#3c39e3] text-white px-3"
                        title="Cart"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="w-5 h-5"
                            aria-hidden="true"
                        >
                            <path d="M2.25 3.75A.75.75 0 0 1 3 3h1.386a.75.75 0 0 1 .728.569L5.5 5.25H21a.75.75 0 0 1 .73.914l-1.5 7.5A.75.75 0 0 1 19.5 14.25H7.2a.75.75 0 0 1-.728-.57L4.28 4.5H3a.75.75 0 0 1-.75-.75Zm5.56 9h11.08l1.2-6H6.61l1.2 6Z"/>
                            <path d="M7.5 21a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3ZM18 21a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/>
                        </svg>

                        <span
                            v-if="$page.props.cartCount > 0"
                            class="absolute -top-1 -right-1"
                            aria-label="Cart items"
                        >
                            <span class="absolute inline-flex h-5 w-5 rounded-full bg-red-500 opacity-75 animate-ping"></span>

                            <span
                                class="relative inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-bold leading-none"
                            >
                                {{ $page.props.cartCount }}
                            </span>

                        </span>
                    </Link>


                    <div class="relative">
                        <button
                            type="button"
                            class="ml-2 w-10 h-10 rounded-full overflow-hidden bg-base-300 flex items-center justify-center ring-1 ring-base-300 hover:ring-base-400 transition"
                            :title="$page.props.auth.user.name"
                            @click="toggleUserMenu"
                        >
                            <img
                                v-if="resolveProfilePhotoUrl($page.props.auth.user)"
                                :src="resolveProfilePhotoUrl($page.props.auth.user)"
                                :alt="`${$page.props.auth.user.name} profile photo`"
                                class="w-full h-full object-cover"
                            />
                            <span v-else class="text-sm font-bold">
                                {{ ($page.props.auth.user.name ?? 'U').slice(0, 1).toUpperCase() }}
                            </span>
                        </button>

                        <div
                            v-if="userMenuOpen"
                            class="fixed inset-0 z-40"
                            @click="closeUserMenu"
                        />

                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-56 z-50 bg-base-100 border border-base-300 rounded-lg shadow-lg overflow-hidden"
                        >
                            <div class="px-4 py-3 border-b border-base-300">
                                <p class="font-semibold truncate">
                                    {{ $page.props.auth.user.name }}
                                </p>
                                <p class="text-sm opacity-70 truncate">
                                    {{ $page.props.auth.user.email }}
                                </p>
                            </div>

                            <div class="py-1">
                                <Link
                                    v-if="$page.props.auth.user.is_admin"
                                    href="/dashboard"
                                    class="block px-4 py-2 hover:bg-base-200"
                                    @click="closeUserMenu"
                                >
                                    Dashboard
                                </Link>

                                <Link
                                    :href="route('profile.show')"
                                    class="block px-4 py-2 hover:bg-base-200"
                                    @click="closeUserMenu"
                                >
                                    Profile
                                </Link>
                            </div>

                            <div class="border-t border-base-300">
                                <button
                                    type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600"
                                    @click="logout"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <main class="flex-1 bg-[#191E24]">
            <slot />
        </main>

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
