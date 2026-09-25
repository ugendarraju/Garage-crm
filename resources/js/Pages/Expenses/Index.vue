<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    expenses: Object,
    summary: Object,
    categoryBreakdown: Object,
    filters: Object,
    categories: Object,
});

const showAddModal = ref(false);
const editingExpense = ref(null);

const filterRange = (range) => {
    router.get(route('expenses.index'), {
        range,
        category: props.filters.category,
        search: props.filters.search,
    }, { preserveState: true });
};

const filterCategory = (cat) => {
    router.get(route('expenses.index'), {
        range: props.filters.range,
        category: cat === props.filters.category ? '' : cat,
        search: props.filters.search,
    }, { preserveState: true });
};

const searchQuery = ref(props.filters.search || '');
const handleSearch = () => {
    router.get(route('expenses.index'), {
        range: props.filters.range,
        category: props.filters.category,
        search: searchQuery.value,
    }, { preserveState: true, replace: true });
};

// Form for Adding / Editing Expense
const form = useForm({
    title: '',
    amount: '',
    category: 'food_tea',
    expense_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: '',
});

const openCreateModal = () => {
    editingExpense.value = null;
    form.reset();
    form.expense_date = new Date().toISOString().split('T')[0];
    showAddModal.value = true;
};

const openEditModal = (expense) => {
    editingExpense.value = expense;
    form.title = expense.title;
    form.amount = expense.amount;
    form.category = expense.category;
    form.expense_date = expense.expense_date;
    form.payment_method = expense.payment_method || 'cash';
    form.notes = expense.notes || '';
    showAddModal.value = true;
};

const submitForm = () => {
    if (editingExpense.value) {
        form.put(route('expenses.update', editingExpense.value.id), {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('expenses.store'), {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            }
        });
    }
};

const deleteExpense = (expense) => {
    if (confirm(`Are you sure you want to delete "${expense.title}"?`)) {
        router.delete(route('expenses.destroy', expense.id), {
            preserveScroll: true,
        });
    }
};

const getCategoryIcon = (category) => {
    switch (category) {
        case 'rent': return '🏢';
        case 'utilities': return '💡';
        case 'food_tea': return '☕';
        case 'consumables': return '🧼';
        case 'tools': return '🛠️';
        case 'salary_advance': return '💼';
        case 'maintenance': return '🔧';
        default: return '📦';
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Daily Expenses & Net Profit" />

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                            <span class="p-2 rounded-xl bg-emerald-100 text-emerald-800">💰</span>
                            Daily Expenses & True Profit / Loss
                        </h1>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                            🔒 Garage Owner Only
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Track daily operational outlays (rent, tea, tools, advances) against revenue & parts cost to know your real bottom-line profit.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <span>+ Record Expense</span>
                    </button>
                </div>
            </div>

            <!-- Date Range Controls -->
            <div class="bg-white p-3 sm:p-4 rounded-xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-gray-500 uppercase mr-2">Timeframe:</span>
                    <button
                        type="button"
                        @click="filterRange('today')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', filters.range === 'today' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        @click="filterRange('this_week')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', filters.range === 'this_week' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        This Week
                    </button>
                    <button
                        type="button"
                        @click="filterRange('this_month')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', filters.range === 'this_month' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        This Month
                    </button>
                    <button
                        type="button"
                        @click="filterRange('last_month')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', filters.range === 'last_month' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        Last Month
                    </button>
                </div>

                <div class="text-xs text-gray-500 font-medium">
                    Showing: <strong>{{ filters.start_date }}</strong> to <strong>{{ filters.end_date }}</strong>
                </div>
            </div>

            <!-- Profit & Loss KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Revenue -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-blue-500"></div>
                    <div class="flex items-center justify-between text-blue-600">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Workshop Revenue</span>
                        <span class="text-xl">📈</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">
                        ₹{{ Number(summary.revenue).toLocaleString('en-IN') }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">Invoiced job cards & services</div>
                </div>

                <!-- Parts Cost (COGS) -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
                    <div class="flex items-center justify-between text-amber-600">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Parts & Stock Cost</span>
                        <span class="text-xl">📦</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">
                        ₹{{ Number(summary.parts_cost).toLocaleString('en-IN') }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">Purchase invoices & inventory</div>
                </div>

                <!-- Operating Expenses -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-rose-500"></div>
                    <div class="flex items-center justify-between text-rose-600">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Operating Expenses</span>
                        <span class="text-xl">🧾</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-rose-700 mt-2">
                        ₹{{ Number(summary.operating_expenses).toLocaleString('en-IN') }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">Daily workshop overheads</div>
                </div>

                <!-- True Net Profit -->
                <div :class="[
                    'p-5 rounded-2xl border shadow-sm relative overflow-hidden',
                    summary.net_profit >= 0 ? 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 border-emerald-300' : 'bg-rose-50 border-rose-300'
                ]">
                    <div :class="['absolute top-0 left-0 right-0 h-1', summary.net_profit >= 0 ? 'bg-emerald-600' : 'bg-rose-600']"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700">True Net Profit</span>
                        <span class="text-xs font-black px-2 py-0.5 rounded-full" :class="summary.net_profit >= 0 ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'">
                            {{ summary.net_profit_margin }}% Margin
                        </span>
                    </div>
                    <div :class="['text-2xl sm:text-3xl font-black mt-2', summary.net_profit >= 0 ? 'text-emerald-900' : 'text-rose-900']">
                        ₹{{ Number(summary.net_profit).toLocaleString('en-IN') }}
                    </div>
                    <div class="text-[11px] text-gray-600 mt-1">
                        Revenue minus (Parts + Daily Overheads)
                    </div>
                </div>
            </div>

            <!-- Category Outlay Breakdown Badges -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Expenses By Category</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-for="(label, key) in categories"
                        :key="key"
                        type="button"
                        @click="filterCategory(key)"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition',
                            filters.category === key
                                ? 'bg-gray-900 text-white border-gray-900 shadow-xs'
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                        ]"
                    >
                        <span>{{ getCategoryIcon(key) }}</span>
                        <span>{{ label }}:</span>
                        <strong>₹{{ Number(categoryBreakdown[key] || 0).toLocaleString('en-IN') }}</strong>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4">
                <div class="w-full sm:w-96 relative">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        placeholder="Search expense description or notes..."
                        class="w-full pl-10 pr-10 py-2 text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-gray-50"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <div class="text-xs font-semibold text-gray-500">
                    {{ expenses.total || expenses.data?.length || 0 }} expense records
                </div>
            </div>

            <!-- Expense List Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="!expenses.data || expenses.data.length === 0" class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        🧾
                    </div>
                    <h3 class="text-base font-bold text-gray-800">No expenses recorded for this period</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1">
                        Click "+ Record Expense" to log daily shop expenses like tea, rent, electricity, tools, or staff salary advances.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Expense Details</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Payment Mode</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Amount (₹)</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="item in expenses.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                                <!-- Date -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-semibold text-gray-600">
                                    {{ new Date(item.expense_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>

                                <!-- Title & Notes -->
                                <td class="px-5 py-4">
                                    <div class="text-xs font-bold text-gray-900">{{ item.title }}</div>
                                    <div v-if="item.notes" class="text-[11px] text-gray-500 mt-0.5">{{ item.notes }}</div>
                                </td>

                                <!-- Category -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <span>{{ getCategoryIcon(item.category) }}</span>
                                        <span>{{ categories[item.category] || item.category }}</span>
                                    </span>
                                </td>

                                <!-- Payment Mode -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-600 uppercase font-semibold">
                                    {{ item.payment_method }}
                                </td>

                                <!-- Amount -->
                                <td class="px-5 py-4 whitespace-nowrap text-right font-black text-sm text-gray-900">
                                    ₹{{ Number(item.amount).toLocaleString('en-IN') }}
                                </td>

                                <!-- Action -->
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openEditModal(item)"
                                            class="text-indigo-600 hover:text-indigo-900 font-bold"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteExpense(item)"
                                            class="text-rose-600 hover:text-rose-900 font-bold"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal for Adding / Editing Expense -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>{{ editingExpense ? '✏️ Edit Expense' : '💰 Record New Expense' }}</span>
                        </h3>
                        <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Expense Title / Description *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. Monthly Shop Electricity Bill, Tea & Snacks, Bought socket set"
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Amount (₹) *</label>
                                <input
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="500"
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Date *</label>
                                <input
                                    v-model="form.expense_date"
                                    type="date"
                                    required
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Category *</label>
                                <select
                                    v-model="form.category"
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                                >
                                    <option v-for="(label, key) in categories" :key="key" :value="key">
                                        {{ getCategoryIcon(key) }} {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Payment Method</label>
                                <select
                                    v-model="form.payment_method"
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                                >
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI / GPay / PhonePe</option>
                                    <option value="bank_transfer">Bank Transfer / NetBanking</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Notes / Remarks (Optional)</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Additional details..."
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition"
                            >
                                {{ editingExpense ? 'Update Expense' : 'Save Expense' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
