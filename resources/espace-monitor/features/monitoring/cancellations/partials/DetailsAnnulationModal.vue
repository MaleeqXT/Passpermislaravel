<script setup lang="ts">
import { Card, FileLibrary, Dialog } from '@shared/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { ItemImage } from '@common/components';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { fileTypeGroups } from '@shared/enums';

defineEmits(['close']);
const props = defineProps({
    item: [Object, null],
});

const reservation = computed(() => props.item?.training?.reservation || {});
const student = computed(() => props.item?.training?.student?.user || {});
const form = useForm({
    media: getFilePath(props.item?.media, true) || null,
    id: null,
    is_justified: false,
    comment: props.item?.comment || '',
});

watch(props, () => {
    form.comment = props.item?.comment || '';
    form.defaults();
});
</script>

<template>
    <div class="contents">
        <FileLibrary menu :types="fileTypeGroups.all" @submit="form.media = $event" />

        <Dialog
            :show="!!item"
            class-name="!p-0"
            custom-class="modal-mobile max-h-[calc(90%-3rem)] md:max-h-[calc(100vh-2rem)] h-full"
            title="Reservation details"
            subtitle="Détails de la session et de l'activité"
            @close="$emit('close')"
        >
            <div class="flex-1 divide-y flex flex-col pb-3">
                <Card class="px-3">
                    <ul class="divide-y">
                        <ItemImage
                            is-mobile
                            :src="getFilePath(student)"
                            size="h-14 w-14"
                            class="flex-1 px-2 py-5"
                            :title="student?.name"
                            :phone="student?.phone"
                        />
                        <li class="flex justify-between gap-3 py-2 text-sm">
                            <i class="text-gray-500">Date de la Reservation </i>
                            {{ dateFormat(reservation.date) }} de {{ reservation.start_at }} à {{ reservation.end_at }}
                        </li>
                        <li class="flex justify-between gap-3 py-2 text-sm">
                            <i class="text-gray-500">Date d'annulation</i> {{ dateFormat(item?.created_at) }}
                        </li>
                        <li class="flex justify-between gap-3 py-2 text-sm">
                            <i class="text-sm text-gray-500">Commentaire</i>
                            <p v-html="form.comment"></p>
                        </li>
                    </ul>
                </Card>
            </div>
        </Dialog>
    </div>
</template>
