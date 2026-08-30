<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome" />
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-gray-50 text-gray-800 dark:bg-zinc-900 dark:text-gray-200">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-transparent"></div>
        
        <div class="relative z-10 w-full max-w-4xl px-6 py-12 text-center shadow-2xl bg-white dark:bg-zinc-800 rounded-3xl border border-gray-100 dark:border-zinc-700">
            
            <div class="mb-8 flex justify-center">
                <div class="bg-orange-500 p-4 rounded-2xl shadow-lg shadow-orange-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>

            <h1 class="mb-4 text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                Welcome to <span class="text-orange-500">Garage CRM</span>
            </h1>
            
            <p class="mx-auto mb-10 max-w-2xl text-lg text-gray-500 dark:text-gray-400">
                The modern, premium solution for managing your garage, customers, vehicles, and job cards.
            </p>

            <nav v-if="canLogin" class="flex justify-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-xl bg-orange-500 px-8 py-4 font-semibold text-white shadow-lg transition-all hover:bg-orange-600 hover:shadow-orange-500/25 focus:outline-none focus:ring-4 focus:ring-orange-500/20"
                >
                    Go to Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-xl bg-orange-500 px-8 py-4 font-semibold text-white shadow-lg transition-all hover:bg-orange-600 hover:shadow-orange-500/25 focus:outline-none focus:ring-4 focus:ring-orange-500/20"
                    >
                        Log in to CRM
                    </Link>

                    <Link
                        :href="route('service-requests.create')"
                        class="rounded-xl border-2 border-gray-200 px-8 py-4 font-semibold text-gray-700 transition-all hover:border-orange-500 hover:text-orange-500 focus:outline-none dark:border-zinc-700 dark:text-gray-300 dark:hover:border-orange-500 dark:hover:text-orange-400"
                    >
                        Book a Service
                    </Link>
                </template>
            </nav>
        </div>
        
        <footer class="absolute bottom-6 text-sm text-gray-400">
            Powered by Garage Saarthi Architecture
        </footer>
    </div>
</template>
