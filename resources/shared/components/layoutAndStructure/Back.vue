<script setup lang="ts">
import { ArrowLeftIcon } from '@adersolutions/icons';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '../feedbackIndicator/Badge.vue';

type BackPropsType = {
    title: string;
    back?: boolean | string;
    badges?: any[];
};

const props = defineProps<BackPropsType>();

const onBack = () => {
    switch (typeof props.back) {
        case 'boolean':
            return history.back();
        case 'function':
            return props.back();
        default:
            return;
    }
};
</script>

<template>
    <div class="header-back h-16 flex items-center">
        <Head :title="title" />
        <div class="flex items-center gap-2">
            <component
                :is="typeof back === 'string' ? Link : 'button'"
                v-if="back"
                :href="typeof back === 'string' ? back : '#'"
                class="rounded-lg p-1.5 flex-center hover:bg-dark/10 t-3 bg-dark/5"
                @click="onBack"
            >
                <ArrowLeftIcon class="h-5 w-5 flex-shrink-0 text-dark" aria-hidden="true" />
            </component>
            <h2 class="text-md sm:text-xl font-semibold">
                {{ title }}
            </h2>
            <Badge v-for="(badge, idx) in badges" :key="idx" :value="badge" />
        </div>
    </div>
</template>
