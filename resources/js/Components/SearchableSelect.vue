<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Select...'
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const selectContainer = ref(null);
const searchInput = ref(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => 
        opt && opt.toLowerCase().includes(query)
    );
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        nextTick(() => {
            if (searchInput.value) searchInput.value.focus();
        });
    }
};

const selectOption = (opt) => {
    emit('update:modelValue', opt);
    emit('change', opt);
    isOpen.value = false;
};

const closeOnOutsideClick = (e) => {
    if (selectContainer.value && !selectContainer.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeOnOutsideClick));
onUnmounted(() => document.removeEventListener('click', closeOnOutsideClick));
</script>

<template>
    <div class="relative" ref="selectContainer">
        <div 
            @click="toggleDropdown"
            class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm bg-white cursor-pointer flex justify-between items-center"
            :class="{ 'ring-1 ring-primary-500 border-primary-500': isOpen }"
        >
            <span :class="{ 'text-gray-400': !modelValue }" class="truncate">{{ modelValue || placeholder }}</span>
            <svg class="h-4 w-4 text-gray-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
        
        <div v-if="isOpen" class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 flex flex-col overflow-hidden">
            <div class="p-2 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                <input 
                    type="text" 
                    ref="searchInput"
                    v-model="searchQuery" 
                    class="w-full rounded-md border-gray-300 py-1.5 px-3 text-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Search..."
                    @click.stop
                >
            </div>
            <ul class="overflow-y-auto overflow-x-hidden flex-1">
                <li 
                    v-for="(opt, index) in filteredOptions" 
                    :key="index"
                    @click="selectOption(opt)"
                    class="px-3 py-2 text-sm hover:bg-primary-50 cursor-pointer truncate"
                    :class="{ 'bg-primary-50 text-primary-700 font-medium': modelValue === opt }"
                >
                    {{ opt }}
                </li>
                <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                    No matching parts found.
                </li>
            </ul>
        </div>
    </div>
</template>
