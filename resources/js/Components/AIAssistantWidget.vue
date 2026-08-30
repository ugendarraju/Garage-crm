<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const isOpen = ref(false);
const command = ref('');
const isProcessing = ref(false);
const responseMessage = ref('');
const isListening = ref(false);

const toggleWidget = () => {
    isOpen.value = !isOpen.value;
};

const submitCommand = async () => {
    if (!command.value.trim()) return;
    
    isProcessing.value = true;
    responseMessage.value = '';
    
    try {
        const res = await axios.post(route('ai-assistant.command'), { command: command.value });
        responseMessage.value = res.data.message;
        command.value = ''; // clear input
        
        // Refresh the page data so any newly created records show up instantly
        router.reload({ preserveScroll: true });
    } catch (error) {
        responseMessage.value = error.response?.data?.error || 'Error processing command.';
    } finally {
        isProcessing.value = false;
    }
};

const startListening = () => {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert("Your browser doesn't support speech recognition.");
        return;
    }
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;
    
    recognition.onstart = () => {
        isListening.value = true;
    };
    
    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        command.value = transcript;
        submitCommand();
    };
    
    recognition.onerror = (event) => {
        console.error('Speech recognition error', event.error);
        isListening.value = false;
    };
    
    recognition.onend = () => {
        isListening.value = false;
    };
    
    recognition.start();
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <div v-if="isOpen" class="bg-white rounded-xl shadow-2xl border border-gray-200 w-80 mb-4 overflow-hidden flex flex-col h-96">
            <div class="bg-gray-900 px-4 py-3 text-white flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="font-bold text-sm">Garage AI Assistant</span>
                </div>
                <button @click="toggleWidget" class="text-gray-400 hover:text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-3">
                <div class="text-xs text-gray-500 text-center mb-2">Speak or type your command</div>
                
                <div v-if="responseMessage" class="bg-blue-50 border border-blue-100 text-blue-800 text-sm p-3 rounded-lg whitespace-pre-wrap">
                    {{ responseMessage }}
                </div>
                
                <div v-if="isProcessing" class="flex justify-center mt-auto">
                    <div class="animate-pulse flex gap-1">
                        <div class="h-2 w-2 bg-gray-400 rounded-full"></div>
                        <div class="h-2 w-2 bg-gray-400 rounded-full animation-delay-200"></div>
                        <div class="h-2 w-2 bg-gray-400 rounded-full animation-delay-400"></div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 p-3 bg-white">
                <form @submit.prevent="submitCommand" class="flex gap-2">
                    <button type="button" @click="startListening" :class="[isListening ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600', 'p-2 rounded-full hover:bg-gray-200 transition-colors']">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                    </button>
                    <input type="text" v-model="command" placeholder="Ask me anything..." class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                    <button type="submit" :disabled="isProcessing || !command.trim()" class="bg-gray-900 text-white p-2 rounded-lg hover:bg-gray-800 disabled:opacity-50">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>
        
        <button v-if="!isOpen" @click="toggleWidget" class="bg-gray-900 text-white p-4 rounded-full shadow-xl hover:bg-gray-800 hover:scale-105 transition-transform">
            <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </button>
    </div>
</template>
