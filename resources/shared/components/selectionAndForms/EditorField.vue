<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { Errors } from '../feedbackIndicator';
const model = defineModel({
    type: String,
});
// Define interface for props
interface EditorProps {
    placeholder?: string;
    label?: string;
    error?: string | string[];
}

// Define props with types using the interface
defineProps<EditorProps>();

// Use ref instead of defineModel for QuillEditor
</script>

<template>
    <div class="w-full">
        <div :class="['block relative group rounded-lg max-w-full prose bg-white shadow-sm', error && 'ring-2 ring-red-500 ']">
            <QuillEditor v-model:content="model" content-type="html" theme="snow" :placeholder="placeholder" />
        </div>
        <Errors v-if="error" :errors="error" />
    </div>
</template>

<style>
.ql-toolbar.ql-snow {
    @apply rounded-t-lg;
}
.ql-container.ql-snow {
    @apply rounded-b-lg min-h-32;
}
.ql-container .ql-editor {
    @apply rounded-b-lg min-h-32;
}
</style>
