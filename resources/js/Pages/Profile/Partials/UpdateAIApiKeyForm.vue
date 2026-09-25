<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    garage: Object,
});

const form = useForm({
    ai_api_key: props.garage?.ai_api_key || '',
});

const updateApiKey = () => {
    form.post(route('ai-assistant.key'), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast or success message can go here
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Garage AI Assistant Settings
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update the API Key for the AI assistant (Google Gemini API) used by your garage.
            </p>

            <div class="mt-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
                <h3 class="text-sm font-bold text-orange-800 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    How to get your API Key (100% Free)
                </h3>
                <ol class="mt-2 text-sm text-orange-900 list-decimal list-inside space-y-1">
                    <li>Click the button below to open Google AI Studio.</li>
                    <li>Sign in with your Google account.</li>
                    <li>Click <strong>Create API Key</strong> in a new project.</li>
                    <li>Copy the key and paste it in the box below!</li>
                </ol>
                <div class="mt-4">
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Get API Key from Google
                    </a>
                </div>
            </div>
        </header>

        <form @submit.prevent="updateApiKey" class="mt-6 space-y-6">
            <div>
                <InputLabel for="ai_api_key" value="API Key" />

                <TextInput
                    id="ai_api_key"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.ai_api_key"
                    :placeholder="garage?.has_ai_api_key ? '•••••••••••••••• (API Key is configured — enter new key to replace)' : 'AIzaSy...'"
                    autocomplete="off"
                />

                <InputError :message="form.errors.ai_api_key" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save API Key</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
