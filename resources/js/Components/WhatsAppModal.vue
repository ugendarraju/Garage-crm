<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    jobCard: Object,
    garage: Object,
});

const emit = defineEmits(['close']);
const page = usePage();

const selectedTemplate = ref('ready');
const customerPhone = ref('');
const messageText = ref('');
const copied = ref(false);

const activeGarage = computed(() => {
    return props.garage || props.jobCard?.garage || page.props.auth?.active_garage || {};
});

const cleanPhone = (raw) => {
    if (!raw) return '';
    let digits = String(raw).replace(/\D/g, '');
    if (digits.length === 10) {
        return '91' + digits;
    }
    return digits;
};

const getVehicleTitle = (jc) => {
    if (!jc?.vehicle) return 'Vehicle';
    const brand = jc.vehicle.brand?.name || '';
    const model = jc.vehicle.vehicle_model?.name || jc.vehicle.vehicleModel?.name || '';
    const reg = jc.vehicle.registration_number ? `(${jc.vehicle.registration_number})` : '';
    return `${brand} ${model} ${reg}`.trim();
};

const buildMessage = (templateKey) => {
    const jc = props.jobCard;
    if (!jc) return '';

    const customerName = jc.customer?.user?.name || 'Valued Customer';
    const vehicleStr = getVehicleTitle(jc);
    const jobNumber = jc.job_number || 'N/A';
    const garageName = activeGarage.value?.name || 'Our Workshop';
    const garagePhone = activeGarage.value?.phone ? `📞 ${activeGarage.value.phone}` : '';
    const garageAddress = activeGarage.value?.address || '';
    
    const finalTotal = jc.final_total ? `₹${Number(jc.final_total).toLocaleString('en-IN')}` : '₹0';
    const amountPaid = jc.amount_paid ? `₹${Number(jc.amount_paid).toLocaleString('en-IN')}` : '₹0';
    const balance = Math.max(0, (jc.final_total || 0) - (jc.amount_paid || 0));
    const balanceStr = `₹${balance.toLocaleString('en-IN')}`;
    const paymentStatus = jc.payment_status ? jc.payment_status.toUpperCase() : 'PENDING';

    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const invoiceUrl = `${origin}/job-cards/${jc.id}/invoice`;

    if (templateKey === 'ready') {
        return `🚗 *Vehicle Ready for Pickup!*\n\nDear *${customerName}*,\nGood news! The repair and service work on your vehicle *${vehicleStr}* is completed and it is ready for delivery.\n\n📋 *Job Card:* #${jobNumber}\n💰 *Total Amount:* ${finalTotal}\n💳 *Payment Status:* ${paymentStatus}\n\n📍 *Workshop Details:*\n*${garageName}*\n${garageAddress ? garageAddress + '\n' : ''}${garagePhone}\n\nThank you for choosing *${garageName}*!`;
    }

    if (templateKey === 'progress') {
        return `🔧 *Service Update - Work in Progress*\n\nDear *${customerName}*,\nWe have commenced servicing your vehicle *${vehicleStr}* under Job Card *#${jobNumber}*.\n\nOur team is inspecting and repairing your vehicle with utmost care. We will inform you as soon as all checks are completed!\n\n📍 *${garageName}*\n${garagePhone}`;
    }

    if (templateKey === 'invoice') {
        return `🧾 *Invoice & Payment Summary*\n\nDear *${customerName}*,\nPlease find the invoice breakdown for your vehicle *${vehicleStr}* under Job Card *#${jobNumber}*:\n\n💰 *Total Bill:* ${finalTotal}\n💵 *Paid:* ${amountPaid}\n💳 *Balance Due:* ${balanceStr}\n\n🔗 *View & Print Invoice:* ${invoiceUrl}\n\n📍 *${garageName}*\n${garagePhone}\nThank you!`;
    }

    if (templateKey === 'estimate') {
        return `📋 *Service Estimate for Approval*\n\nDear *${customerName}*,\nWe have inspected your vehicle *${vehicleStr}* under Job Card *#${jobNumber}*.\n\n💰 *Estimated Total:* ${finalTotal}\n\nPlease confirm your approval so our team can proceed with the repairs.\n\n📍 *${garageName}*\n${garagePhone}`;
    }

    return '';
};

watch(() => props.show, (newVal) => {
    if (newVal && props.jobCard) {
        const rawPhone = props.jobCard.customer?.whatsapp_number || props.jobCard.customer?.user?.phone || '';
        customerPhone.value = rawPhone;

        const st = (props.jobCard.status || '').toLowerCase();
        if (st === 'complete' || st === 'delivered') {
            selectedTemplate.value = 'ready';
        } else if (st === 'scheduled') {
            selectedTemplate.value = 'estimate';
        } else {
            selectedTemplate.value = 'progress';
        }

        messageText.value = buildMessage(selectedTemplate.value);
    }
});

const onTemplateChange = (templateKey) => {
    selectedTemplate.value = templateKey;
    if (templateKey !== 'custom') {
        messageText.value = buildMessage(templateKey);
    }
};

const sendOnWhatsApp = () => {
    const phone = cleanPhone(customerPhone.value);
    if (!phone || phone.length < 10) {
        alert('Please ensure a valid 10-digit customer mobile/WhatsApp number is provided.');
        return;
    }
    const encoded = encodeURIComponent(messageText.value);
    const url = `https://wa.me/${phone}?text=${encoded}`;
    window.open(url, '_blank');
};

const copyMessage = async () => {
    try {
        await navigator.clipboard.writeText(messageText.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <Modal :show="show" maxWidth="lg" @close="emit('close')">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center border border-[#25D366]/20">
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Send WhatsApp Alert</h3>
                        <p class="text-xs text-gray-500">Opens WhatsApp directly on your phone with pre-filled message</p>
                    </div>
                </div>

                <button @click="emit('close')" type="button" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Recipient Phone & Vehicle Info -->
            <div class="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div>
                    <span class="font-bold text-gray-800">{{ jobCard?.customer?.user?.name || 'Customer' }}</span>
                    <span class="text-gray-400 mx-1.5">•</span>
                    <span class="text-gray-600 font-medium">{{ getVehicleTitle(jobCard) }}</span>
                    <div class="text-[11px] text-gray-500 mt-0.5 font-mono">Job Card: {{ jobCard?.job_number }}</div>
                </div>

                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-bold text-gray-500 shrink-0">WhatsApp No:</label>
                    <input
                        type="text"
                        v-model="customerPhone"
                        placeholder="Mobile number"
                        class="text-xs py-1 px-2.5 rounded-lg border-gray-300 focus:ring-[#25D366] focus:border-[#25D366] font-mono font-bold text-gray-900 w-36"
                    />
                </div>
            </div>

            <!-- Template Selectors -->
            <div class="mt-4">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Select Message Template</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button
                        type="button"
                        @click="onTemplateChange('ready')"
                        :class="selectedTemplate === 'ready' ? 'bg-[#25D366] text-white border-[#25D366] shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-2.5 py-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                    >
                        <span class="text-base">🚗</span>
                        <span>Ready for Pickup</span>
                    </button>

                    <button
                        type="button"
                        @click="onTemplateChange('progress')"
                        :class="selectedTemplate === 'progress' ? 'bg-[#25D366] text-white border-[#25D366] shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-2.5 py-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                    >
                        <span class="text-base">🔧</span>
                        <span>In Progress</span>
                    </button>

                    <button
                        type="button"
                        @click="onTemplateChange('invoice')"
                        :class="selectedTemplate === 'invoice' ? 'bg-[#25D366] text-white border-[#25D366] shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-2.5 py-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                    >
                        <span class="text-base">🧾</span>
                        <span>Invoice Link</span>
                    </button>

                    <button
                        type="button"
                        @click="onTemplateChange('estimate')"
                        :class="selectedTemplate === 'estimate' ? 'bg-[#25D366] text-white border-[#25D366] shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        class="px-2.5 py-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                    >
                        <span class="text-base">📋</span>
                        <span>Estimate</span>
                    </button>
                </div>
            </div>

            <!-- Message Preview / Editable Box -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                        Message Preview <span class="font-normal text-gray-400 lowercase">(editable before sending)</span>
                    </label>
                    <button
                        type="button"
                        @click="copyMessage"
                        class="text-xs font-semibold text-gray-500 hover:text-gray-800 inline-flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span>{{ copied ? 'Copied!' : 'Copy' }}</span>
                    </button>
                </div>

                <div class="relative">
                    <textarea
                        v-model="messageText"
                        rows="7"
                        class="w-full text-xs sm:text-sm rounded-xl border-gray-300 font-sans shadow-inner focus:ring-[#25D366] focus:border-[#25D366] bg-emerald-50/20"
                        placeholder="Type or customize your WhatsApp message..."
                    ></textarea>
                </div>
                <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                    <span>💡</span>
                    <span>Tapping <strong>Send on WhatsApp</strong> directly opens the WhatsApp app on your smartphone with this message ready. Zero ban risk!</span>
                </p>
            </div>

            <!-- Footer Action Buttons -->
            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="sendOnWhatsApp"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all active:scale-95"
                >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>Open in WhatsApp</span>
                </button>
            </div>
        </div>
    </Modal>
</template>
