<script setup lang="ts">
import { SizeEnum } from '@shared/enums';
import { getFilePath, strip } from '@shared/utils';
import { Spinner, Thumb } from '@shared/components';
import { LineProgress } from '../../common/components/index';
import { ArrowRightIcon } from '@adersolutions/icons';
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useStudentSpace } from '@espace-student/stores';
import { routes } from '@espace-student/routes';

// type PropsType = {
//     phone: boolean;
// };

const emit = defineEmits(['onAction']);
const { stats, user } = useStudentSpace();
// withDefaults(defineProps<PropsType>(), {
//     phone: true,
// });

const reserv = computed(() => stats.data?.reservations);
const balance = computed(() => stats.data?.balance);
const onVisit = () => {
    router.visit(route(routes.settings.profile.index), {
        onSuccess: () => {
            emit('onAction');
        },
    });
};
</script>

<template>
    <div class="relative">
        <Link
            as="div"
            :href="route(routes.settings.profile.index)"
            class="bg-white/5 box rounded-xl relative overflow-clip"
            @click="onVisit"
        >
            <div class="bg-rainbow absolute -inset-[60%] opacity-70"></div>
            <div class="flex items-center gap-3 p-2">
                <Thumb :src="getFilePath(user)" :size="SizeEnum.MD" />
                <div class="flex-1">
                    <h2 class="font-bold tracking-tight sm:text-lg">{{ user.name }}</h2>
                    <p class="text-sm">{{ user?.email }}</p>
                </div>
            </div>
            <div class="h-rainbow"></div>
            <button class="btn btn-link flex items-center justify-between btn-dark w-full !p-2">
                <span class="text-sm"> Voir le profil </span>
                <ArrowRightIcon class="w-4 h-4" />
            </button>
        </Link>

        <div class="bg-dark box rounded-xl relative overflow-clip mt-1 text-white">
            <div v-if="stats.fetching" class="absolute inset-0 backdrop-blur-sm z-1 flex-center rounded-lg">
                <Spinner class="w-5" />
            </div>
            <div class="bg-rainbow absolute -inset-[60%] opacity-50"></div>
            <div class="text-xs px-2 pt-2 flex justify-between">
                <span>La Progrès de votre competences</span>
                <span> {{ strip((stats.data.competences.done * 100) / stats.data.competences.total || 0) }}% </span>
            </div>
            <LineProgress :done="stats.data.competences.done || 0" :total="stats.data.competences.total || 0" class="px-2 pb-3" />
            <div class="h-rainbow"></div>

            <ul class="grid grid-cols-4 text-center gap-2 py-2 px-2 drop-shadow-sm">
                <li class="rounded-lg p-0.5 text-yellow-200 bg-yellow-500/10">
                    <p class="text-xl font-bold">{{ reserv.passed }}</p>
                    <small class="text-3xs">Seance passé</small>
                </li>
                <li class="rounded-lg p-0.5 text-blue-200 bg-blue-500/10">
                    <p class="text-xl font-bold">{{ reserv.upcoming }}</p>
                    <small class="text-3xs">Seance a venir</small>
                </li>
                <li class="rounded-lg p-0.5 text-orange-200 bg-orange-500/10">
                    <p class="text-xl font-bold">{{ balance.used }}</p>
                    <small class="text-3xs">Balance utilisé</small>
                </li>
                <li class="rounded-lg p-0.5 text-green-200 bg-green-500/10">
                    <p class="text-xl font-bold">{{ balance.rest }}</p>
                    <small class="text-3xs">Balance resté</small>
                </li>
            </ul>
        </div>
    </div>
</template>
