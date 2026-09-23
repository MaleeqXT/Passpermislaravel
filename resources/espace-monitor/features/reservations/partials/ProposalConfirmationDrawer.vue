<script setup lang="ts">
import { Drawer, InputField } from '@shared/components';
import { dateFormat } from '@shared/utils';
import { useMonitorSpace } from '@espace-monitor/stores';
import Button from '@shared/components/actions/Button.vue';
import { StudentStatsCard } from '@common/components';

const emit = defineEmits(['refresh']);
const { proposals, state } = useMonitorSpace();
const onSubmit = () => {
    proposals.save(() => {
        proposals.close();
        emit('refresh');
    });
};
const onClose = () => {
    proposals.showConfirmation = false;
};
</script>

<template>
    <Drawer :show="proposals.showConfirmation" title="Motif de la Séance" @close="onClose">
        <div class="flex flex-col h-full">
            <ul class="divide-y px-4 text-xs flex-1 space-y-1">
                <!-- <Thumb class="md:hiden" :src="getFilePath(item.student?.user)" :size="SizeEnum.MD" /> -->
                <StudentStatsCard v-show="state.student?.id" :student="state.student" />

                <li
                    v-for="item in proposals.data"
                    :key="item.reservation_id"
                    class="bg-white shadow-md rounded-lg p-2 border border-gray-200"
                >
                    <dl class="grid grid-cols-3 gap-y-2 text-gray-800">
                        <dt class="font-light">📅 Séance du</dt>
                        <dd class="font-bold col-span-2">
                            {{ dateFormat(item.reservation?.date, 'letter') }}
                            &nbsp;
                            {{ item.reservation?.start_at }}
                            <span class="text-gray-500"> à </span>
                            {{ item.reservation?.end_at }}
                        </dd>

                        <dt v-if="item.reservation?.lieu" class="font-light">📍 Lieu</dt>
                        <dd v-if="item.reservation?.lieu" class="font-bold col-span-2">
                            {{ item.reservation?.lieu?.name }}
                        </dd>
                    </dl>
                </li>

                <li class="pt-5">
                    <InputField v-model="proposals.comment" :multiline="2" label="Justification et Objectifs" />
                </li>
            </ul>
            <div class="sticky bottom-0 p-3 backdrop-blur bg-white/70 border-t">
                <Button :loading="proposals.processing" variant="success" full class="h-10" @click="onSubmit">
                    Confirmer et proposé
                </Button>
            </div>
        </div>
    </Drawer>
</template>
