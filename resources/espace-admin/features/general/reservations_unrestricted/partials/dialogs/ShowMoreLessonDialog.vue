<script setup lang="ts">
import { Dialog, Button, Thumb } from '@shared/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { EditIcon, DeleteIcon } from '@adersolutions/icons';
import { useReservations } from '../../ReservationsPage';
import type { ReservationType } from '@common/types';
import { SizeEnum } from '@shared/enums';

type PropsType = {
    list: ReservationType[] | null;
};

defineProps<PropsType>();

const { state } = useReservations();
const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
const onEdit = (item: ReservationType) => {
    state.selected = item;
    state.show = true;
};
const onDelete = (item: ReservationType) => {
    state.selected = item;
    state.delete = true;
};
</script>

<template>
    <Dialog :show="!!list && !state.show && !state.delete" max-width="md" title="list Reservation" @close="close">
        <div class="border-y bg-white border-y-slate-300 flex gap-5">
            <div class="flex-1 p-2">
                <div class="grid grid-cols-9 gap-3 p-2 font-medium text-sm bg-gray-100 rounded-lg">
                    <span class="col-span-2 pl-1.5">Condidat</span>
                    <span class="col-span-2">Moniteur</span>
                    <span class="col-span-2">Date</span>
                    <span class="col-span-2">Lieu</span>
                </div>
                <ul class="divide-y divide-gray-300 pb-1">
                    <li v-for="(item, index) in list" :key="index" :class="['grid grid-cols-9 py-1 gap-3 bg-opacity-10 ']">
                        <div class="col-span-2 flex gap-3 items-center relative pl-3">
                            <span
                                :class="['absolute -left-2 inset-y-1 w-1 rounded-r-full']"
                                :style="{
                                    backgroundColor: item?.training?.offer?.color || '',
                                }"
                            ></span>
                            <Thumb :src="getFilePath(item.monitor?.user)" :size="SizeEnum.SM" />
                            <p>{{ item.monitor?.user?.name }}</p>
                        </div>
                        <div class="col-span-2 flex gap-3 items-center">
                            <Thumb :src="getFilePath(item.training?.student?.user)" :size="SizeEnum.SM" />
                            <p>{{ item.training?.student?.user?.name }}</p>
                        </div>
                        <div class="col-span-2 flex flex-col text-sm">
                            <b>{{ dateFormat(item?.date, 'fr') }}</b>
                            {{ item?.start_at?.slice(0, 5) }} - {{ item?.end_at?.slice(0, 5) }}
                        </div>
                        <div class="col-span-2 flex-center justify-start text-sm">
                            {{ item.lieu?.name }}
                        </div>
                        <div class="flex gap-2 justify-end items-center">
                            <Button variant="info" @click="onEdit(item)" :icon="EditIcon" />
                            <Button variant="danger" @click="onDelete(item)" :icon="DeleteIcon" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex flex-row justify-end p-4 bg-gray-100 text-right">
            <Button variant="secondary" @click="close">Annuler</Button>
        </div>
    </Dialog>
</template>
