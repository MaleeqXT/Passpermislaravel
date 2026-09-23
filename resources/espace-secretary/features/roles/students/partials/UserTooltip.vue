<template>
    <Popup :delay="200">
        <slot />
        <template #content>
            <div class="flex flex-col gap-5 justify-between rounded-xl w-72">
                <h2 class="font-semibold text-lg text-left px-2 border-b border-gray-500/50 pb-2">Condidat details</h2>
                <dl class="flex flex-col gap-3">
                    <dd class="flex justify-between gap-2 text-white px-2">
                        <span class="text-sm text-gray-300">Nom et prenom :</span>
                        <span class="font-semibold">{{ user?.name }}</span>
                    </dd>
                    <dd class="flex justify-between gap-2 px-2">
                        <span class="text-sm text-gray-300">N° tele :</span>
                        <span class="font-semibold">{{ user?.phone ?? '_' }}</span>
                    </dd>
                    <dd class="flex justify-between gap-2 px-2">
                        <span class="text-sm text-gray-300">Email :</span>
                        <span class="font-semibold">{{ user?.email ?? '_' }}</span>
                    </dd>
                    <dd class="flex justify-between gap-2 px-2">
                        <span class="text-sm text-gray-300">Balance :</span>
                        <span class="font-semibold">{{ user?.student.balance ?? '_' }}</span>
                    </dd>
                    <dd class="flex justify-between gap-2 px-2">
                        <span class="text-sm text-gray-300">Neph code :</span>
                        <span class="font-semibold">{{ user?.student.neph ?? '_' }}</span>
                    </dd>
                </dl>
            </div>
        </template>
    </Popup>
</template>

<script setup lang="ts">
import { Popup } from '@shared/components';

defineProps({
    user: Object,
});
</script>
