<script setup lang="ts">
import Button from '../actions/Button.vue';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref } from 'vue';
import Errors from '../feedbackIndicator/Errors.vue';
import { onMounted } from 'vue';
import { nextTick } from 'vue';
// Define the model property with a string type for the color
const model = defineModel<string>({
    type: String,
    default: '', // Optional default value
});

// Define the props interface for the component to ensure type safety
type PropsType = {
    label?: string;
    error?: string | string[]; // Can be a string or an array of errors
};

defineProps<PropsType>();
const signaturePad = ref(null);

const clear = () => {
    signaturePad.value?.clearSignature?.();
};

const onEnd = () => {
    const { data } = signaturePad.value?.saveSignature?.() || '';
    if (data) {
        model.value = data;
    }
};
onMounted(() => {
    // Initialize the signature pad if needed
    nextTick(() => {
        if (model.value && signaturePad.value) {
            signaturePad.value?.fromDataURL?.(model.value);
        }
    });
});
</script>

<template>
    <div>
        <label class="form-label mb-1">
            {{ label }}
        </label>
        <div class="w-full relative">
            <VueSignaturePad ref="signaturePad" :options="{ onEnd }" class="bg-gray-200 rounded-lg shadow aspect-square" />
            <Errors v-if="error" :errors="error" />
            <Button variant="danger" full class="mt-2" @click="clear"> Effacer </Button>
        </div>
    </div>
</template>
