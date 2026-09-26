<script setup>
import { ref, computed, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AIAssistantWidget from '@/Components/AIAssistantWidget.vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);
const activeGarageId = computed(() => page.props.auth.active_garage_id);

const crmName = computed(() => page.props.system_settings?.name || 'PistonCRM');
const crmSubheading = computed(() => page.props.system_settings?.subheading || 'Smart Workspace');
const crmLogoUrl = computed(() => page.props.system_settings?.logo_url || null);
const crmLogoSize = computed(() => page.props.system_settings?.logo_size || 36);
const crmFaviconUrl = computed(() => page.props.system_settings?.favicon_url || null);

watch(crmName, (newName) => {
    if (typeof document !== 'undefined' && newName) {
        let meta = document.querySelector('meta[name="crm-name"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.name = 'crm-name';
            document.head.appendChild(meta);
        }
        meta.content = newName;
    }
}, { immediate: true });

const updateFavicon = (url) => {
    if (typeof document === 'undefined') return;
    let link = document.querySelector("link[rel~='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.getElementsByTagName('head')[0].appendChild(link);
    }
    link.href = url || '/favicon.ico';
};

watch(crmFaviconUrl, (newUrl) => {
    if (newUrl) {
        updateFavicon(newUrl);
    }
}, { immediate: true });

const crmInitials = computed(() => {
    const name = (crmName.value || 'GS').trim();
    const parts = name.split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

const navigation = computed(() => {
    if (user.value.role === 'super_admin' && !activeGarageId.value) {
        return [
            { name: 'Garages', href: route('garages.index'), current: route().current('garages.*'), icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { name: 'Action Logs', href: route('action-logs.index'), current: route().current('action-logs.*'), icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'System Backups', href: route('admin.backups.index'), current: route().current('admin.backups.*'), icon: 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4' },
            { name: 'Settings', href: route('admin.settings.index'), current: route().current('admin.settings.*'), icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
            { name: 'User Guide', href: route('user-guide'), current: route().current('user-guide'), icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' }
        ];
    }
    if (user.value.role === 'customer') {
        return [
            { name: 'My Dashboard', href: route('dashboard'), current: route().current('dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
            { name: 'My Job Cards', href: route('job-cards.index'), current: route().current('job-cards.*'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
            { name: 'My Vehicles', href: route('vehicles.index'), current: route().current('vehicles.*'), icon: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' },
            { name: 'User Guide', href: route('user-guide'), current: route().current('user-guide'), icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' }
        ];
    }
    
    const isOwner = ['owner', 'super_admin'].includes(user.value.role);

    return [
        { name: 'Dashboard', href: route('dashboard'), current: route().current('dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        { name: 'Service Requests', href: route('service-requests.index'), current: route().current('service-requests.*'), icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { name: 'Job Cards', href: route('job-cards.index'), current: route().current('job-cards.*'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
        { name: 'Service Reminders', href: route('service-reminders.index'), current: route().current('service-reminders.*'), icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
        { name: 'Vehicles & History', href: route('vehicles.index'), current: route().current('vehicles.*'), icon: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' },
        { name: 'Inventory', href: route('inventory.index'), current: route().current('inventory.*'), icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
        { name: 'Purchases', href: route('purchases.index'), current: route().current('purchases.*'), icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
        { name: 'Sales', href: route('sales.index'), current: route().current('sales.*'), icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
        ...(isOwner ? [
            { name: 'Expenses & Profit', href: route('expenses.index'), current: route().current('expenses.*'), icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Action Logs', href: route('action-logs.index'), current: route().current('action-logs.*'), icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }
        ] : []),
        { name: 'Coupons', href: route('coupons.index'), current: route().current('coupons.*'), icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
        { name: 'Vehicle Database', href: route('vehicle-brands.index'), current: route().current('vehicle-brands.*') || route().current('models.*'), icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
        { name: 'Customers', href: route('customers.index'), current: route().current('customers.*'), icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
        { name: 'User Management', href: route('staff.index'), current: route().current('staff.*'), icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
        { name: 'Service Types', href: route('service-types.index'), current: route().current('service-types.*'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
        { name: 'Settings', href: route('settings.edit'), current: route().current('settings.*'), icon: 'M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0a7.5 7.5 0 11-15 0m15 0a7.5 7.5 0 10-15 0M9 12h.008v.008H9V12z' },
        { name: 'User Guide', href: route('user-guide'), current: route().current('user-guide'), icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' }
    ];
});

const exitGarage = () => {
    router.post(route('garages.leave'));
};
</script>

<template>
    <div class="flex flex-col h-screen bg-gray-50 font-sans">
        <!-- Exit Garage Banner -->
        <div v-if="user.role === 'super_admin' && activeGarageId" class="bg-red-600 text-white px-4 py-2 flex justify-between items-center text-sm shrink-0">
            <div>
                <span class="font-bold text-base">Impersonation Mode:</span> You are currently managing a specific Garage.
            </div>
            <button @click="exitGarage" class="bg-white text-red-600 px-4 py-1.5 rounded-lg font-bold hover:bg-red-50 transition shadow-sm">
                Exit to Master Dashboard
            </button>
        </div>
        
        <div class="flex flex-1 overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside class="hidden w-64 flex-col bg-white md:flex border-r border-gray-200 shadow-sm z-20">
                <div class="flex h-20 shrink-0 items-center px-6 border-b border-gray-100">
                    <Link :href="route('dashboard')" class="flex items-center gap-3 overflow-hidden">
                        <img
                            v-if="crmLogoUrl"
                            :src="crmLogoUrl"
                            class="object-contain rounded shrink-0 max-w-[48px]"
                            :style="{ height: crmLogoSize + 'px' }"
                            alt="Logo"
                        />
                        <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-xl shadow-lg shadow-primary-900/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="overflow-hidden">
                            <span class="text-xl font-extrabold text-gray-900 tracking-tight leading-none block truncate">{{ crmName }}</span>
                            <span v-if="crmSubheading" class="text-[10px] text-gray-500 font-semibold leading-none uppercase tracking-wider block mt-1 truncate">{{ crmSubheading }}</span>
                        </div>
                    </Link>
                </div>
                
                <nav class="flex flex-1 flex-col overflow-y-auto px-4 py-6 space-y-1">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href"
                        :class="[item.current ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center rounded-lg px-4 py-3 text-sm font-semibold transition-all duration-200']">
                        <svg class="mr-3 h-5 w-5 flex-shrink-0" :class="item.current ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </nav>
            </aside>

            <!-- Mobile Layout -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <header class="flex h-20 shrink-0 items-center justify-between bg-white px-4 sm:px-6 lg:px-8 border-b border-gray-200 shadow-sm z-10">
                    <!-- Mobile hamburger -->
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="md:hidden p-2 text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <div class="md:hidden flex items-center">
                        <img
                            v-if="crmLogoUrl"
                            :src="crmLogoUrl"
                            class="h-8 w-auto max-w-[40px] object-contain rounded"
                            alt="Logo"
                        />
                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-xs shadow-sm">
                            {{ crmInitials }}
                        </div>
                    </div>

                    <!-- Center Garage Name -->
                    <div class="hidden md:flex flex-1 justify-center items-center gap-3">
                        <img v-if="$page.props.auth.active_garage?.logo_path" :src="`/storage/${$page.props.auth.active_garage.logo_path}`" class="object-contain" :style="{ height: ($page.props.auth.active_garage.header_logo_size || 32) + 'px' }" alt="Logo" />
                        <div class="flex flex-col items-start">
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">
                                <template v-if="$page.props.auth.user.role === 'super_admin' && !activeGarageId">
                                    Master Administration
                                </template>
                                <template v-else-if="$page.props.auth.active_garage_name">
                                    {{ $page.props.auth.active_garage_name }}
                                </template>
                                <template v-else>
                                    Your Garage
                                </template>
                            </h2>
                            <span class="text-xs text-gray-500 font-medium">
                                <template v-if="$page.props.auth.user.role === 'super_admin' && !activeGarageId">
                                    {{ crmSubheading || 'Smart Garage Management' }}
                                </template>
                                <template v-else-if="$page.props.auth.active_garage?.tagline">
                                    {{ $page.props.auth.active_garage.tagline }}
                                </template>
                                <template v-else>
                                    Smart Garage Management
                                </template>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-1 justify-end items-center">
                        <!-- Profile dropdown -->
                        <div class="relative ml-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="flex items-center gap-3 bg-transparent text-sm focus:outline-none p-1 transition-colors group">
                                        <div class="h-10 w-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold shadow-sm group-hover:bg-primary-700 transition">
                                            {{ $page.props.auth.user.name.charAt(0) }}
                                        </div>
                                        <div class="hidden md:flex flex-col items-start text-left">
                                            <span class="font-bold text-gray-800 text-sm leading-none">{{ $page.props.auth.user.name }}</span>
                                            <span class="text-xs text-gray-500 mt-1 leading-none capitalize">{{ $page.props.auth.user.role.replace('_', ' ') }}</span>
                                        </div>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('user-guide')">User Guide & Help</DropdownLink>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <!-- Mobile menu -->
                <div v-show="showingNavigationDropdown" class="md:hidden absolute inset-0 z-40 bg-gray-900/80 backdrop-blur-sm transition-opacity" @click="showingNavigationDropdown = false"></div>
                <div v-show="showingNavigationDropdown" class="md:hidden absolute inset-y-0 left-0 z-50 w-72 bg-gray-900 shadow-2xl transform transition-transform">
                    <div class="flex h-20 shrink-0 items-center justify-between px-6 border-b border-gray-800">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <img
                                v-if="crmLogoUrl"
                                :src="crmLogoUrl"
                                class="h-7 w-auto object-contain rounded shrink-0"
                                alt="Logo"
                            />
                            <span class="text-xl font-bold text-white tracking-tight truncate">{{ crmName }}</span>
                        </div>
                        <button @click="showingNavigationDropdown = false" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="px-4 py-6 space-y-2">
                        <Link v-for="item in navigation" :key="item.name" :href="item.href"
                            :class="[item.current ? 'bg-gray-800 text-white ring-1 ring-gray-700' : 'text-gray-300 hover:bg-gray-800 hover:text-white', 'group flex items-center rounded-lg px-4 py-3 text-base font-semibold transition-colors']"
                            @click="showingNavigationDropdown = false">
                            <svg class="mr-4 h-6 w-6" :class="item.current ? 'text-primary-500' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </nav>
                </div>

                <!-- Main Content Area -->
                <main class="flex-1 overflow-y-auto p-4 md:p-8">
                    <!-- Page Heading (Only show if provided, but dashboard has its own) -->
                    <div v-if="$slots.header" class="mb-8">
                        <slot name="header" />
                    </div>
                    
                    <div class="max-w-7xl mx-auto">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
        
        <!-- Garage AI Assistant Widget -->
        <AIAssistantWidget v-if="$page.props.auth.user.role !== 'customer'" />
    </div>
</template>
