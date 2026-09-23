<template>
    <div>
        <h3 class="font-bold">Customer</h3>
        <ul class="flex flex-col gap-3 bg-gray-100 rounded-lg p-3 mt-2">
            <ItemImage
                size="w-12 h-12"
                class="bg-dark text-white p-2 w-full rounded-lg bg-rainbow"
                :title="student?.user?.name"
                :src="getFilePath(student?.user)"
            />
            <li>
                <p class="opacity-50">Compte créé :</p>
                <span>{{ dateFormat(student?.created_at, 'fulltime') }}</span>
            </li>
        </ul>
        <h3 class="font-bold mt-5">Contact Informations</h3>
        <ul class="flex flex-col gap-3 bg-gray-100 rounded-lg p-3 mt-2">
            <li>
                <p class="opacity-50">Email :</p>
                <span>{{ student?.user?.email }}</span>
            </li>
            <li>
                <p class="opacity-50">Téléphone :</p>
                <span>{{ student?.user?.phone ?? 'Non numéro de téléphone' }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { Badge, Card } from '@shared/components';
import { ItemImage } from '@common/components';
import { dateFormat, getFilePath } from '@shared/utils';

defineProps({
    student: {
        type: Object,
        default: () => ({}),
    },
});
</script>
