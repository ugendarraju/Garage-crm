<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    backups: Array,
    garages: Array,
    schedule: Object,
    stats: Object,
});

// Modals
const showCreateModal = ref(false);
const showRestoreModal = ref(false);
const showUploadModal = ref(false);
const selectedBackup = ref(null);

// Forms
const createForm = useForm({
    garage_id: '',
    label: '',
});

const scheduleForm = useForm({
    frequency: props.schedule.frequency || 'biweekly',
    retention: props.schedule.retention || 5,
});

const restoreForm = useForm({
    filename: '',
    restore_type: 'single_garage',
    target_garage_id: '',
});

const uploadForm = useForm({
    backup_file: null,
});

// Create Backup Actions
const openCreateSingleModal = () => {
    createForm.reset();
    createForm.garage_id = props.garages?.[0]?.id || '';
    showCreateModal.value = true;
};

const triggerFullBackup = () => {
    if (confirm('Generate a complete system backup now? This will bundle database records and uploads for all active garages.')) {
        router.post(route('admin.backups.store'), {
            garage_id: null,
            label: 'Full System Backup',
        });
    }
};

const submitCreateBackup = () => {
    createForm.post(route('admin.backups.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

// Save Schedule
const saveSchedule = () => {
    scheduleForm.post(route('admin.backups.schedule'), {
        preserveScroll: true,
    });
};

// Restore Actions
const openRestoreModal = (backup) => {
    selectedBackup.value = backup;
    restoreForm.reset();
    restoreForm.filename = backup.filename;
    restoreForm.restore_type = backup.type === 'single_garage' ? 'single_garage' : 'single_garage';
    restoreForm.target_garage_id = backup.garages?.[0]?.id || props.garages?.[0]?.id || '';
    showRestoreModal.value = true;
};

const submitRestore = () => {
    const isSingle = restoreForm.restore_type === 'single_garage';
    const targetGarageName = props.garages.find(g => g.id == restoreForm.target_garage_id)?.name || 'Selected Garage';

    const warningText = isSingle
        ? `⚠️ RESTORE CONFIRMATION:\n\nAre you sure you want to restore "${targetGarageName}"?\n\nThis will replace ${targetGarageName}'s current database records and uploads with this backup.\n\nAll other garages will remain 100% untouched.`
        : `⚠️ FULL SYSTEM RESTORE WARNING:\n\nAre you sure you want to restore the entire CRM system?\nThis will overwrite database records for all garages present in this backup archive.`;

    if (confirm(warningText)) {
        restoreForm.post(route('admin.backups.restore'), {
            onSuccess: () => {
                showRestoreModal.value = false;
                restoreForm.reset();
            }
        });
    }
};

// Upload Backup File
const handleFileSelected = (e) => {
    uploadForm.backup_file = e.target.files[0];
};

const submitUpload = () => {
    uploadForm.post(route('admin.backups.upload'), {
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
        }
    });
};

// Delete Backup
const deleteBackup = (backup) => {
    if (confirm(`Are you sure you want to permanently delete backup archive "${backup.filename}"?`)) {
        router.delete(route('admin.backups.destroy', backup.filename), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="System Backups & Disaster Recovery" />

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                            <span class="p-2 rounded-xl bg-indigo-100 text-indigo-800">💾</span>
                            System Backups & Disaster Recovery
                        </h1>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                            Super Admin Console
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Automated scheduled snapshots, downloadable ZIP archives, and selective single-garage restoration without affecting other tenants.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        @click="showUploadModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 shadow-2xs transition active:scale-95"
                    >
                        <span>📤 Upload ZIP</span>
                    </button>
                    <button
                        type="button"
                        @click="openCreateSingleModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 shadow-2xs transition active:scale-95"
                    >
                        <span>🏢 Backup Single Garage</span>
                    </button>
                    <button
                        type="button"
                        @click="triggerFullBackup"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm transition active:scale-95"
                    >
                        <span>⚡ Create Full Backup</span>
                    </button>
                </div>
            </div>

            <!-- Stats & Automated Schedule Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Stats Overview -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Storage Usage</div>
                        <div class="text-2xl sm:text-3xl font-black text-gray-900">{{ stats.total_size }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ stats.total_count }} total backup archive{{ stats.total_count === 1 ? '' : 's' }} stored</div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500 flex items-center justify-between">
                        <span>Last Backup Taken:</span>
                        <strong class="text-gray-800">{{ schedule.last_backup_at || 'Never' }}</strong>
                    </div>
                </div>

                <!-- Automated Scheduling Configuration -->
                <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
                    <form @submit.prevent="saveSchedule" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                <span>⏰</span>
                                <span>Automated Backup Schedule</span>
                            </div>
                            <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                Runs Daily at 2:00 AM
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Frequency</label>
                                <select
                                    v-model="scheduleForm.frequency"
                                    class="w-full text-xs sm:text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="biweekly">Every 2 Weeks (1st & 15th) - Recommended</option>
                                    <option value="weekly">Weekly (Every Monday)</option>
                                    <option value="monthly">Monthly (1st of Month)</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Retention (Keep Last N Backups)</label>
                                <select
                                    v-model="scheduleForm.retention"
                                    class="w-full text-xs sm:text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option :value="3">Keep last 3 backups</option>
                                    <option :value="5">Keep last 5 backups</option>
                                    <option :value="10">Keep last 10 backups</option>
                                    <option :value="15">Keep last 15 backups</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <p class="text-[11px] text-gray-400">Older backups exceeding retention limit are automatically pruned to conserve disk space.</p>
                            <button
                                type="submit"
                                :disabled="scheduleForm.processing"
                                class="px-3.5 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Backups Table -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <span>📦</span>
                        <span>Available Backup Archives</span>
                    </h2>
                    <span class="text-xs font-semibold text-gray-500">{{ backups.length }} files</span>
                </div>

                <div v-if="backups.length === 0" class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mx-auto mb-3">
                        💾
                    </div>
                    <h3 class="text-base font-bold text-gray-800">No backup archives created yet</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1">
                        Click "Create Full Backup" above to generate your first complete system snapshot with database records and uploads.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-5 py-3.5">Backup Name & Date</th>
                                <th class="px-5 py-3.5">Scope & Tenant(s)</th>
                                <th class="px-5 py-3.5">Archive Size</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="b in backups" :key="b.filename" class="hover:bg-slate-50/70 transition">
                                <!-- File & Date -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                        <span class="text-base">🗜️</span>
                                        <span>{{ b.filename }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ b.created_at }}</div>
                                </td>

                                <!-- Scope -->
                                <td class="px-5 py-4">
                                    <div v-if="b.type === 'full'" class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800">
                                            Full CRM Snapshot
                                        </span>
                                        <span class="text-[11px] text-gray-500">({{ b.garages?.length || 0 }} garages)</span>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800">
                                            Single Garage
                                        </span>
                                        <span class="text-[11px] font-semibold text-gray-800">
                                            {{ b.garages?.[0]?.name || 'Specific Garage' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Size -->
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-gray-700">
                                    {{ b.size_formatted }}
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-2">
                                    <a
                                        :href="route('admin.backups.download', b.filename)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold transition"
                                    >
                                        <span>📥</span>
                                        <span>Download</span>
                                    </a>
                                    <button
                                        type="button"
                                        @click="openRestoreModal(b)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-xs font-bold transition"
                                    >
                                        <span>🔄</span>
                                        <span>Restore</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteBackup(b)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition"
                                        title="Delete Backup File"
                                    >
                                        <span>🗑️</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Single Garage Backup Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>🏢</span>
                            <span>Backup Specific Garage</span>
                        </h3>
                        <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitCreateBackup" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Select Garage *</label>
                            <select
                                v-model="createForm.garage_id"
                                required
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="g in garages" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Optional Label</label>
                            <input
                                v-model="createForm.label"
                                type="text"
                                placeholder="e.g. Pre-migration backup"
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                            <button type="submit" :disabled="createForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs transition">
                                Create Backup Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Restore Modal -->
            <div v-if="showRestoreModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>🔄</span>
                            <span>Restore From Backup</span>
                        </h3>
                        <button type="button" @click="showRestoreModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitRestore" class="space-y-4 mt-4">
                        <div class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl text-xs text-indigo-900">
                            <strong>Source Archive:</strong> {{ selectedBackup?.filename }}
                        </div>

                        <!-- Scope Options -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Restoration Mode</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    :class="[
                                        'flex flex-col p-3 rounded-xl border text-xs cursor-pointer transition',
                                        restoreForm.restore_type === 'single_garage' ? 'bg-indigo-50/50 border-indigo-500 text-indigo-900 font-bold' : 'border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <input type="radio" v-model="restoreForm.restore_type" value="single_garage" class="text-indigo-600 focus:ring-indigo-500" />
                                        <span>Single Garage Only</span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 font-normal mt-1">Leaves all other garages untouched</span>
                                </label>

                                <label
                                    :class="[
                                        'flex flex-col p-3 rounded-xl border text-xs cursor-pointer transition',
                                        restoreForm.restore_type === 'full' ? 'bg-indigo-50/50 border-indigo-500 text-indigo-900 font-bold' : 'border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <input type="radio" v-model="restoreForm.restore_type" value="full" class="text-indigo-600 focus:ring-indigo-500" />
                                        <span>Full System Restore</span>
                                    </div>
                                    <span class="text-[11px] text-gray-500 font-normal mt-1">Restores all garages in archive</span>
                                </label>
                            </div>
                        </div>

                        <!-- Target Garage Selection (when Single Garage is selected) -->
                        <div v-if="restoreForm.restore_type === 'single_garage'">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Target Garage to Overwrite / Restore *</label>
                            <select
                                v-model="restoreForm.target_garage_id"
                                required
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="g in garages" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1">
                                Only records belonging to this selected garage will be refreshed. Other tenants in the CRM will not be affected.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button type="button" @click="showRestoreModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                            <button type="submit" :disabled="restoreForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs transition">
                                Confirm & Restore
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Upload Backup Modal -->
            <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📤</span>
                            <span>Upload Backup Archive</span>
                        </h3>
                        <button type="button" @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitUpload" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Select Backup ZIP File *</label>
                            <input
                                type="file"
                                accept=".zip"
                                required
                                @change="handleFileSelected"
                                class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-lg"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Accepts .zip backup archives generated by this CRM.</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                            <button type="submit" :disabled="uploadForm.processing || !uploadForm.backup_file" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs transition">
                                Upload Archive
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
