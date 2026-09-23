<script setup lang="ts">
import { Thumb, Badge } from '@shared/components';
import { GeneralStatus } from '@common/enums';
import { getFilePath } from '@shared/utils';
import { pagesBadges } from '../../features/roles/students/views/StudentsView';
import { SizeEnum } from '@shared/enums';
defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div class="bg-dark mx-2 rounded-xl bg-rainbow mb-2">
        <div class="flex items-center gap-3 p-2">
            <Thumb :src="getFilePath(user)" :size="SizeEnum.MD" />
            <div class="flex-1">
                <h2 class="font-bold tracking-tight text-white sm:text-lg">{{ user.name }}</h2>
            </div>
        </div>
        <div class="h-rainbow"></div>
        <div class="p-2 border-t border-white/30 text-center relative">
            <p class="text-gray-300 text-sm">{{ user?.email }}</p>
            <p class="text-gray-300 text-sm">{{ user?.phone }}</p>
            <div class="flex gap-1 justify-center mt-1">
                <Badge v-for="v in pagesBadges(user)" :key="v.name" :value="v" class="ring-1 ring-white/10" />
                <Badge :id="user.status" :options="GeneralStatus" class="ring-1 ring-white/10" />
            </div>
        </div>
    </div>
</template>
