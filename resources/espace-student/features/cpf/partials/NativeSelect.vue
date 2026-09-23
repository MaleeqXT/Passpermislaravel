<script setup lang="ts">
import { Errors } from '@shared/components';

const model = defineModel({ type: String, default: '' });
defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    error: String,
    disabled: Boolean,
});
</script>
<template>
    <div>
        <select v-model="model" :class="['form-control max-w-xs font-normal', disabled && 'disabled']">
            <option value="" disabled selected>Veuillez remplir le champ</option>
            <option v-for="(option, key) in options" :key="key" :value="option.id || option">
                {{ option.name || option }}
            </option>
        </select>
        <Errors v-if="error" :errors="error" />
    </div>
</template>
