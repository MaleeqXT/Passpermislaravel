<script setup lang="ts">
import { LineProgress, PageMobile } from '@common/components';
import { reactive } from 'vue';
import { CompetencesDrawer } from './partials';
import { SizeEnum } from '@shared/enums';
import { CompetencyGroupType } from '@common/types';

type PropsType = {
    competencies: CompetencyGroupType[];
};

defineProps<PropsType>();

const state = reactive({
    selected: null,
});
</script>

<template>
    <PageMobile :width="SizeEnum.XS" title="Competences" subtitle="Suivi des compétences" :profile="false" slided back>
        <div class="md:bg-white h-full relative z-900 rounded-xl p-3">
            <ul class="grid divide-y">
                <li
                    v-for="item in competencies"
                    :key="item?.id"
                    class="flex flex-col gap-4 p-3"
                    :style="{ order: item?.position || 0 }"
                    @click="state.selected = item"
                >
                    <div class="flex justify-between items-center gap-3">
                        <p class="text-base font-medium flex gap-2">
                            <span class="w-7 h-7 p-1 bg-dark/10 rounded-lg flex-center">
                                {{ item.position }}
                            </span>
                            {{ item.name }}
                        </p>
                    </div>
                    <LineProgress :done="item.competencies_done_count || 0" :total="item.competencies_count || 0" />

                    <p class="text-xs">{{ item.label }}</p>
                </li>
            </ul>
        </div>
        <CompetencesDrawer :item="state.selected" @close="state.selected = null" />
    </PageMobile>
</template>
