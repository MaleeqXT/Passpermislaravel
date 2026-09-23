<script setup lang="ts">
import { useQuery, UseQueryType } from '@shared/hooks';
import { watch } from 'vue';
import { SizeEnum } from '@shared/enums';
import { getFilePath, strip } from '@shared/utils';
import { Spinner, Thumb } from '@shared/components';
import { LineProgress } from '../index';
import { ArrowRightIcon, PhoneIcon } from '@adersolutions/icons';
import type { ButtonType } from '@shared/types';
import type { StudentType } from '@common/types';
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

type PropsType = {
    student: StudentType | null;
    action?: ButtonType;
    phone?: boolean;
    mounted?: boolean;
};
type UseQueryStatsType = {
    id: string;
    reservations: { passed: number; upcoming: number };
    balance: { rest: number; used: number };
    competences: {
        done: number;
        total: number;
    };
};
const props = withDefaults(defineProps<PropsType>(), {
    phone: true,
});
const progressTotal: number = (usePage().props.progressTotal as number) || 0;
const stats = useQuery<UseQueryStatsType>({
    url: route('api.stats.students.index', props.student?.id || '000'),
    mounted: (!!props.student?.id && !props.action) || (props.mounted && !!props.student?.id),
    init: {
        data: {
            reservations: { passed: 0, upcoming: 0 },
            balance: { rest: 0, used: 0 },
            competences: { done: 0, total: 0 },
        },
    },
});
const reserv = computed(() => stats.data?.reservations || { passed: 0, upcoming: 0 });
const balance = computed(() => stats.data?.balance || { rest: 0, used: 0 });
const onVisit = () => {
    if (props.action && props.action.onAction) {
        props.action.onAction();
    } else if (props.action?.href) {
        router.visit(props.action.href);
    }
};
// watch(props, (v) => {
//     if ((!!props.student?.id && !props.action) || props.mounted) {
//         stats.fetch(route('api.stats.students.index', props.student?.id));
//         // studentQuery.fetch();
//     }
// });
</script>

<template>
    <div v-if="student?.user" class="relative">
        <div class="bg-white/5 box rounded-xl relative overflow-clip">
            <div class="bg-rainbow absolute -inset-[60%] opacity-70 z-1"></div>
            <div class="flex items-center gap-3 p-2 relative z-1">
                <Thumb :src="getFilePath(student.user)" :size="SizeEnum.MD" class="bg-white" @click="onVisit" />
                <div class="flex-1" @click="onVisit">
                    <h2 class="font-bold tracking-tight sm:text-lg">{{ student.user.name }}</h2>
                    <p class="text-sm">{{ student.user?.email }}</p>
                </div>
                <a v-if="student.user?.phone && phone" :href="`Tel:${student.user?.phone}`" class="flex-center btn btn-warning w-8 p-1">
                    <PhoneIcon class="w-5 h-5" />
                </a>
            </div>
            <template v-if="action">
                <div class="h-rainbow"></div>
                <button @click="onVisit" class="btn btn-link flex items-center justify-between btn-dark w-full !p-2 z-1 relative">
                    <span class="text-sm">{{ action.label || 'Voir détail' }}</span>
                    <ArrowRightIcon class="w-4 h-4" />
                </button>
            </template>
        </div>

        <div
            v-if="!action || mounted"
            class="bg-dark box rounded-xl relative overflow-clip mt-1 text-white"
            @click="action?.onAction?.(student)"
        >
            <div v-if="stats.fetching" class="absolute inset-0 backdrop-blur-sm z-1 flex-center rounded-lg">
                <Spinner class="w-5" />
            </div>
            <div class="bg-rainbow absolute -inset-[60%] opacity-50"></div>
            <div class="text-xs px-2 pt-2 flex justify-between">
                <span>La Progrès de la competences</span>
                <span> {{ strip((stats.data.competences.done * 100) / progressTotal || 0) }}% </span>
            </div>
            <LineProgress :done="stats.data.competences.done" :total="progressTotal" class="px-2 pb-3" />
            <div class="h-rainbow"></div>

            <ul class="grid grid-cols-4 text-center gap-2 py-2 px-2 drop-shadow-sm">
                <li class="rounded-lg p-0.5 text-yellow-200 bg-yellow-500/10">
                    <p class="text-xl font-bold">{{ reserv.passed || 0 }}</p>
                    <small class="text-3xs">Seance passé</small>
                </li>
                <li class="rounded-lg p-0.5 text-blue-200 bg-blue-500/10">
                    <p class="text-xl font-bold">{{ reserv.upcoming || 0 }}</p>
                    <small class="text-3xs">Seance a venir</small>
                </li>
                <li class="rounded-lg p-0.5 text-orange-200 bg-orange-500/10">
                    <p class="text-xl font-bold">{{ balance.used || 0 }}</p>
                    <small class="text-3xs">Balance utilisé</small>
                </li>
                <li class="rounded-lg p-0.5 text-green-200 bg-green-500/10">
                    <p class="text-xl font-bold">{{ balance.rest || 0 }}</p>
                    <small class="text-3xs">Balance resté</small>
                </li>
            </ul>
        </div>
    </div>
    <div v-else class="contents"></div>
</template>
