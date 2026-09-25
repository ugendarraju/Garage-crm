<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({})
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const SECTIONS = [
    {
        id: 'exterior',
        name: 'Exterior & Body',
        icon: '🚗',
        items: [
            'Front & Rear Bumper',
            'Body Scratches / Dents',
            'Windshield & Glass',
            'Headlights & Taillights',
            'Side Mirrors'
        ]
    },
    {
        id: 'electricals',
        name: 'Electricals & Controls',
        icon: '⚡',
        items: [
            'Battery & Starter',
            'Horn & Indicators',
            'AC & Blower',
            'Power Windows / Central Lock',
            'Wiper & Washer'
        ]
    },
    {
        id: 'mechanical',
        name: 'Mechanical & Fluids',
        icon: '🔧',
        items: [
            'Engine Oil Level',
            'Coolant & Brake Fluid',
            'Brake & Clutch Response',
            'Suspension & Steering',
            'Exhaust / Smoke'
        ]
    },
    {
        id: 'tyres',
        name: 'Tyres & Wheels',
        icon: '🔘',
        items: [
            'Front Tyres',
            'Rear Tyres',
            'Spare Tyre & Jack'
        ]
    },
    {
        id: 'toolkit',
        name: 'Belongings & Toolkit',
        icon: '🧰',
        items: [
            'Jack, Rod & Tool Kit',
            'Floor Mats & Mudflaps',
            'Customer Belongings Removed'
        ]
    }
];

const STATUS_OPTIONS = [
    { value: 'ok', label: 'OK', bg: 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100', activeBg: 'bg-emerald-600 text-white border-emerald-600 shadow-sm' },
    { value: 'attention', label: 'Needs Attn', bg: 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100', activeBg: 'bg-amber-500 text-white border-amber-500 shadow-sm' },
    { value: 'damaged', label: 'Damaged', bg: 'bg-rose-50 text-rose-700 border-rose-300 hover:bg-rose-100', activeBg: 'bg-rose-600 text-white border-rose-600 shadow-sm' },
    { value: 'na', label: 'N/A', bg: 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100', activeBg: 'bg-gray-600 text-white border-gray-600 shadow-sm' },
];

const checklist = computed({
    get: () => props.modelValue || {},
    set: (val) => emit('update:modelValue', val)
});

const getItemStatus = (sectionId, item) => {
    return checklist.value?.[sectionId]?.[item] || 'na';
};

const setItemStatus = (sectionId, item, status) => {
    if (props.readonly) return;
    const current = JSON.parse(JSON.stringify(checklist.value || {}));
    if (!current[sectionId]) current[sectionId] = {};
    current[sectionId][item] = status;
    checklist.value = current;
};

const markAllOk = () => {
    if (props.readonly) return;
    const current = JSON.parse(JSON.stringify(checklist.value || {}));
    SECTIONS.forEach(sec => {
        if (!current[sec.id]) current[sec.id] = {};
        sec.items.forEach(item => {
            current[sec.id][item] = 'ok';
        });
    });
    checklist.value = current;
};

const updateNotes = (e) => {
    const current = JSON.parse(JSON.stringify(checklist.value || {}));
    current.intake_notes = e.target.value;
    checklist.value = current;
};

// Summary stats for view mode
const summaryStats = computed(() => {
    let ok = 0;
    let attention = 0;
    let damaged = 0;
    let total = 0;

    SECTIONS.forEach(sec => {
        sec.items.forEach(item => {
            const st = getItemStatus(sec.id, item);
            total++;
            if (st === 'ok') ok++;
            else if (st === 'attention') attention++;
            else if (st === 'damaged') damaged++;
        });
    });

    return { ok, attention, damaged, total };
});
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="p-4 sm:p-5 bg-gradient-to-r from-slate-50 to-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base shadow-sm border border-indigo-100">
                    📋
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-gray-900">Vehicle Intake Inspection Checklist</h3>
                    <p class="text-xs text-gray-500">Document existing vehicle condition, scratches, fluids & toolkit before repair</p>
                </div>
            </div>

            <!-- Action / Stats -->
            <div class="flex items-center gap-2">
                <div v-if="summaryStats.damaged > 0" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                    ⚠️ {{ summaryStats.damaged }} Damaged / Dent
                </div>
                <div v-if="summaryStats.attention > 0" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                    ⚡ {{ summaryStats.attention }} Needs Attention
                </div>
                <button
                    v-if="!readonly"
                    type="button"
                    @click="markAllOk"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-lg transition-colors shadow-xs active:scale-95"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Mark All OK
                </button>
            </div>
        </div>

        <!-- Sections Grid -->
        <div class="p-4 sm:p-6 space-y-6">
            <div v-for="section in SECTIONS" :key="section.id" class="border border-gray-100 rounded-lg p-3 sm:p-4 bg-slate-50/50">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-base">{{ section.icon }}</span>
                    <h4 class="text-xs sm:text-sm font-bold text-gray-800 uppercase tracking-wider">{{ section.name }}</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    <div
                        v-for="item in section.items"
                        :key="item"
                        class="flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 shadow-xs hover:border-gray-300 transition-colors"
                    >
                        <span class="text-xs font-medium text-gray-700 truncate pr-2" :title="item">{{ item }}</span>

                        <!-- Interactive Pills -->
                        <div v-if="!readonly" class="inline-flex rounded-md shadow-xs shrink-0" role="group">
                            <button
                                v-for="opt in STATUS_OPTIONS"
                                :key="opt.value"
                                type="button"
                                @click="setItemStatus(section.id, item, opt.value)"
                                :class="[
                                    'px-1.5 sm:px-2 py-0.5 text-[11px] font-bold border first:rounded-l-md last:rounded-r-md -ml-px transition-all',
                                    getItemStatus(section.id, item) === opt.value
                                        ? opt.activeBg
                                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                ]"
                            >
                                {{ opt.label }}
                            </button>
                        </div>

                        <!-- Readonly Badge -->
                        <div v-else class="shrink-0">
                            <span
                                v-if="getItemStatus(section.id, item) === 'ok'"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800"
                            >
                                ✓ OK
                            </span>
                            <span
                                v-else-if="getItemStatus(section.id, item) === 'attention'"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800"
                            >
                                ⚡ Attention
                            </span>
                            <span
                                v-else-if="getItemStatus(section.id, item) === 'damaged'"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800"
                            >
                                ✕ Damaged
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-500"
                            >
                                N/A
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="border-t border-gray-100 pt-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Inspection Remarks / Customer Intake Notes
                </label>
                <textarea
                    v-if="!readonly"
                    :value="checklist?.intake_notes || ''"
                    @input="updateNotes"
                    rows="2"
                    placeholder="e.g. Dent on rear left door, spare wheel key kept in dashboard glovebox..."
                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50"
                ></textarea>
                <p v-else class="text-xs sm:text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200 italic">
                    {{ checklist?.intake_notes || 'No intake notes recorded for this vehicle.' }}
                </p>
            </div>
        </div>
    </div>
</template>
