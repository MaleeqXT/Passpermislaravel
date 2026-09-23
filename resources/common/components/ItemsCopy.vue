<script setup lang="ts">
import { copyText } from '@shared/utils';
import { ClipboardIcon, PhoneIcon, EnvelopeIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';

defineProps({
    name: String,
    phone: String,
    href: String,
    email: String,
    isIcons: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex-1 flex flex-col gap-0.5">
        <component :is="href ? Link : 'div'" :href="href" :class="['font-bold', href && 'hover:underline hover:text-blue-500 btn-m']">
            {{ name }}
        </component>
        <div v-if="email" class="flex items-center group">
            <a :href="'mailto:' + email" class="flex gap-1 duration-300 text-gray-500 hover:text-dark cursor-pointer text-xs">
                {{ email }}
            </a>
            <span
                v-if="email"
                title="copy"
                class="pl-4 text-dark invisible group-hover:visible btn-m"
                @click.prevent="copyText(email, 'Email ')"
            >
                <ClipboardIcon class="w-3.5" />
            </span>
        </div>
        <div v-if="phone" class="flex items-center group">
            <a :href="'tel:' + phone" class="flex gap-1 text-2xs duration-300 cursor-pointer text-orange-600/80 hover:text-primary">
                {{ phone }}
            </a>
            <span
                v-if="phone"
                title="copy"
                class="pl-4 text-dark invisible group-hover:visible btn-m"
                @click.prevent="copyText(phone, 'N° de tele')"
            >
                <ClipboardIcon class="w-3.5" />
            </span>
        </div>
    </div>
</template>
