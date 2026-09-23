<script setup lang="ts">
import { UseQueryType } from '@shared/hooks';
import { EmptyState, Thumb } from '@shared/components';
import { LineProgress, PageMobile } from '@common/components';
import { getFilePath, strip } from '@shared/utils';
import { ChevronRightIcon, PersonExitIcon } from '@adersolutions/icons';
import { UserType } from '@common/types';
import { usePage } from '@inertiajs/vue3';
type PropsType = {
    // progressTotal: number;
    students: UseQueryType<Required<UserType>[]>;
};
defineEmits(['select']);
defineProps<PropsType>();
const progressTotal: number = (usePage().props.progressTotal as number) || 0;
</script>

<template>
    <ul class="pb-20 flex flex-col gap-2">
        <li v-for="item in students.data" :key="item.id" class="btn-m bg-white relative box overflow-clip" @click="$emit('select', item)">
            <div class="flex gap-2 py-1 btn-m px-2">
                <div class="h-rainbow absolute top-0 blur-[1px]"></div>
                <div class="h-rainbow absolute bottom-0 blur-[1px]"></div>
                <Thumb :src="getFilePath(item)" />
                <!-- :content="`${item.student?.realise_hour || 0} heures réalisées - ${
                        item.student?.review_monitor?.estimation || 0
                    } heures estimées`" -->
                <div class="flex-1">
                    <p class="flex justify-between items-center">
                        {{ item.name }}
                        <ChevronRightIcon class="w-5 opacity-70" />
                    </p>
                    <span class="text-2xs font-light flex justify-between">
                        <span>Competence progres</span>
                        <span> {{ strip((item.student.progress_done * 100) / progressTotal || 0) }}%</span>
                    </span>
                    <LineProgress :done="item.student.progress_done" :total="progressTotal" class="-mt-1" />
                </div>
            </div>
        </li>
        <EmptyState
            v-if="!students.meta?.total"
            :loading="students.fetching"
            heading="Aucun élève"
            class="bg-gradient-to-b w-full from-white to-gray-100 shadow-down border border-slate-100 rounded-xl mt-5 py-3"
            :image="PersonExitIcon"
        >
            <p>
                {{
                    students.params.search
                        ? `Aucun condidat trouvé pour la recherche '${students.params.search}'`
                        : "Vous n'avez aucune condidat pour le moment"
                }}
            </p>
        </EmptyState>
    </ul>
</template>
