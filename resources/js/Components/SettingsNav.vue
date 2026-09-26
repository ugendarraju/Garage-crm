<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role);
const isOwner = computed(() => ['owner', 'super_admin'].includes(userRole.value));

const tabs = computed(() => [
    {
        name: 'General Settings',
        href: route('settings.edit'),
        current: route().current('settings.*'),
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
    },
    ...(isOwner.value ? [
        {
            name: 'Staff & Roles',
            href: route('staff.index'),
            current: route().current('staff.*'),
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
        }
    ] : []),
    {
        name: 'Service Types',
        href: route('service-types.index'),
        current: route().current('service-types.*'),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'
    },
    {
        name: 'Vehicle Database',
        href: route('vehicle-brands.index'),
        current: route().current('vehicle-brands.*') || route().current('models.*'),
        icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'
    },
    {
        name: 'Coupons',
        href: route('coupons.index'),
        current: route().current('coupons.*'),
        icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'
    },
    ...(isOwner.value ? [
        {
            name: 'Action Logs',
            href: route('action-logs.index'),
            current: route().current('action-logs.*'),
            icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
        }
    ] : [])
]);
</script>

<template>
    <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-2 overflow-x-auto">
        <nav class="flex items-center gap-1 min-w-max">
            <Link
                v-for="tab in tabs"
                :key="tab.name"
                :href="tab.href"
                :class="[
                    tab.current
                        ? 'bg-orange-500 text-white font-bold shadow-md shadow-orange-500/20'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 font-medium',
                    'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all'
                ]"
            >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                </svg>
                <span>{{ tab.name }}</span>
            </Link>
        </nav>
    </div>
</template>
