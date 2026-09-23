<script setup lang="ts">
import { LightbulbIcon } from '@adersolutions/icons';
import { LineProgress } from '@common/components';
import type { CompetencyGroupType } from '@common/types';
import { CheckIcon } from '@heroicons/vue/20/solid';
import { strip } from '@shared/utils';
import { it } from 'node:test';
import { computed, onMounted, ref } from 'vue';

type PropsType = {
    items: CompetencyGroupType[];
};

const props = defineProps<PropsType>();
const selected = ref(props.items[0] || {});

const checkIsValid = (item: CompetencyGroupType) => {
    return !item.competencies.some((v) => v?.feedback?.rating !== 1);
};
const evaluate = (item: CompetencyGroupType) => {
    if (!item.competencies || item.competencies.length === 0) {
        return { percentOfProgress: 0, result: 'Aucune compétence disponible' };
    }

    const total = item.competencies.length;
    let pendingCount = 0,
        needImproveCount = 0,
        masteredCount = 0;

    item.competencies.forEach((comp) => {
        if (comp.rating === 1) pendingCount++;
        else if (comp.rating === 2) needImproveCount++;
        else if (comp.rating === 3) masteredCount++;
    });

    // Calculate general progress percentage (based on mastered and need improvement competencies)
    const totalEvaluated = masteredCount + needImproveCount;
    const percentOfProgress = strip((totalEvaluated / total) * 100);

    let result;
    if (masteredCount === total) {
        result = 'Toutes les compétences sont maîtrisées';
    } else if (pendingCount === total) {
        result = 'Toutes les compétences nécessitent encore du travail';
    } else if (needImproveCount > 0 || pendingCount > 0) {
        result = 'Certaines compétences nécessitent des améliorations';
    } else {
        result = 'Évaluation des compétences en cours';
    }

    return {
        total: total,
        done: totalEvaluated,
        result,
        percent: percentOfProgress,
    };
};
</script>

<template>
    <nav class="grid md:grid-cols-3">
        <ol role="list" class="overflow-hidden px-3">
            <li
                v-for="(item, idx) in items"
                :key="item.name"
                :class="[idx !== items.length - 1 ? 'pb-6' : '', 'relative']"
                @click="selected = item"
            >
                <template v-if="checkIsValid(item) && item.competencies.length > 0">
                    <div
                        v-if="idx !== items.length - 1"
                        class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-indigo-600"
                        aria-hidden="true"
                    />
                </template>
                <template v-else>
                    <div
                        v-if="idx !== items.length - 1"
                        class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300"
                        aria-hidden="true"
                    />
                </template>
                <div
                    :class="[
                        'group relative flex items-center py-2 px-2 -ml-2 rounded-xl btn-m',
                        selected?.id === item.id ? 'bg-primary text-white' : 'hover:bg-white/70 hover:shadow-sm',
                    ]"
                >
                    <b class="flex-center relative z-10 size-8 rounded-lg btn-header text-xs" aria-hidden="true">
                        {{ item.position }}
                    </b>
                    <span class="ml-4 flex min-w-0 flex-col text-sm -mt-0.5">
                        <span class="font-medium">{{ item.name }}</span>
                        <span class="opacity-70 text-xs">{{ item.label }}</span>
                    </span>
                </div>
            </li>
        </ol>
        <div class="col-span-2">
            <li class="flex flex-col gap-4 bg-dark-block p-3">
                <div class="flex justify-between items-center gap-3">
                    <p class="text-base font-medium flex gap-2">
                        <LightbulbIcon class="w-7 p-1 bg-white/10 rounded-lg" />
                        Progrès de la {{ selected.name }}
                    </p>
                    <b class="text-xs h-5"> {{ evaluate(selected).percent }} %</b>
                </div>
                <LineProgress :done="evaluate(selected).done ?? 0" :total="evaluate(selected).total ?? 0" />
                <p class="text-xs">{{ evaluate(selected).result }}</p>
            </li>

            <slot :item="selected" />
        </div>
    </nav>
</template>
