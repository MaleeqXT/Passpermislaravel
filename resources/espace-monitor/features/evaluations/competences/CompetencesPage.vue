<script setup lang="ts">
import { LineProgress, PageMobile } from '@common/components';
import { reactive } from 'vue';
import { ItemImage } from '@common/components';
import { getFilePath } from '@shared/utils';
import { CompetenceDetailDrawer } from './partials';
import { CompetencyGroupType, StudentType } from '@common/types';

type PropsType = { student: StudentType; competencies: CompetencyGroupType[] };
defineProps<PropsType>();

const state = reactive<{ selected: CompetencyGroupType | null }>({
    selected: null,
});
</script>

<template>
    <PageMobile :title="student.user?.name" subtitle="Competences" back>
        <template #title>
            <ItemImage
                :src="getFilePath(student.user)"
                :title="student.user?.name"
                is-mobile
                class="relative text-whit lg:mb-10 text-white"
            />
        </template>

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

        <CompetenceDetailDrawer :item="state.selected" @close="state.selected = null" :student="student" />
    </PageMobile>
</template>
