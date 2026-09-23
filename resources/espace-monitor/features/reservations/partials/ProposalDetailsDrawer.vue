<script setup lang="ts">
import { computed } from 'vue';
import { Button, Drawer, Thumb } from '@shared/components';
import { CalendarIcon, ClockIcon } from '@adersolutions/icons';
import { routes } from '@espace-monitor/routes';
import { dateFormat, getFilePath } from '@shared/utils';
import { useMonitorSpace } from '@espace-monitor/stores';
import { ReservationLocateDetail } from '@common/components';
import { SizeEnum } from '@shared/enums';
import type { ReservationType, StudentType } from '@common/types';
const emit = defineEmits(['refresh']);

const { proposals } = useMonitorSpace();

const item = computed(() => proposals.selected?.reservation || ({} as ReservationType));
const student = computed(() => proposals.selected?.student || ({} as StudentType));
const onDelete = () => {
    proposals.delete(route(routes.api.proposals.delete, proposals.selected?.id || '-')).then(() => {
        proposals.selected = null;
        emit('refresh');
    });
};
</script>

<template>
    <Drawer
        :show="!!proposals.selected"
        class-name="!p-0"
        custom-class="modal-mobile max-h-[calc(100%-3rem)] !h-fit"
        title="Proposition "
        @close="proposals.selected = null"
    >
        <div class="flex flex-col flex-1 px-3">
            <div class="flex items-center gap-2 bg-w hite sha dow-sm py-3 m-2 rounded-xl">
                <Thumb :src="getFilePath(student.user)" :size="SizeEnum.MD" />
                <div>
                    <b>{{ student.user?.name }}</b>
                    <p>{{ student.user?.phone }}</p>
                </div>
            </div>
            <div class="text-dark border-t py-4 text-md overflow-clip relative">
                <div class="rainbow absolute -top-0.5 -left-20"></div>

                <dl class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center">
                        <CalendarIcon class="w-4" />
                        Date
                    </dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>
                        {{ dateFormat(item.date, 'full') }}
                    </dd>
                </dl>
                <dl class="grid grid-cols-3 py-1">
                    <dd class="opacity-70 flex gap-2 items-center"><ClockIcon class="w-4" />Heure</dd>
                    <dd class="col-span-2 font-semibold">
                        <span class="mr-2">:</span>
                        {{ item.start_at }} à{{ item.end_at }}
                    </dd>
                </dl>
            </div>

            <ReservationLocateDetail :lieu="item.lieu" />
        </div>
        <div class="sticky bottom-0 p-2 backdrop-blur-md">
            <Button :loading="proposals.processing" variant="danger" full @click="onDelete"> Annuler cette proposition </Button>
        </div>
    </Drawer>
</template>
