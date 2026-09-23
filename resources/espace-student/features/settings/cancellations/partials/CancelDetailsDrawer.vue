<script setup lang="ts">
import { AlertDiamondIcon, CheckIcon, ChevronLeftIcon, PhoneIcon } from '@adersolutions/icons';
import { Card, Button, DialogConfirm, FileLibrary, SingleImageField, InputField, Drawer, Thumb } from '@shared/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { useForm } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { computed, watch, ref } from 'vue';
import { useFiles } from '@shared/hooks';
import { fileTypeGroups, SizeEnum } from '@shared/enums';
import { CancelStatus } from '@common/enums';
import type { CancellationsType, ReservationType, UserType } from '@common/types';

const emit = defineEmits(['close', 'change:week']);
type PropsType = {
    item: CancellationsType | null;
};
const props = defineProps<PropsType>();
const medias = useFiles();

const reservation = computed<ReservationType | undefined>(() => props.item?.training?.reservation);
const monitor = computed<UserType | undefined>(() => props.item?.training?.reservation?.monitor?.user);
const confirmation = ref(false);
const form = useForm({
    media: props.item?.media?.storage_media || null,
    comment: props.item?.comment || '',
});
const onDelete = () => {
    confirmation.value = true;
};
const onCancellationConfirmed = () => {
    form.transform((data) => {
        if (data.media?.id) {
            data.media = data.media.id;
        }
        return data;
    });
    form.post(route(routes.cancellations.update, props.item?.id || '-'), {
        onSuccess: () => {
            confirmation.value = false;
            emit('close');
        },
    });
};
const onDeleteFile = () => {
    if (props.item?.media) {
        medias.delete(props.item?.media?.id).then(() => {
            form.media = null;
        });
    } else {
        form.media = null;
    }
};
watch(props, () => {
    form.comment = props.item?.comment || '';
    form.media = props.item?.media?.storage_media || null;
    form.defaults();
});
</script>

<template>
    <div class="contents">
        <FileLibrary :types="fileTypeGroups.all" menu @submit="form.media = $event" />

        <Drawer :show="!!item" title="Détails de seance" @close="$emit('close')">
            <dl class="px-3">
                <div class="flex items-center gap-3 bg-rainbow rainbow-opacity-30 bg-dark text-white rounded-lg box p-2">
                    <Thumb :src="getFilePath(monitor)" :size="SizeEnum.MD" />
                    <div class="flex-1">
                        <b v-if="monitor">{{ monitor.name }}</b>
                        <p v-if="monitor">{{ monitor.phone }}</p>
                    </div>
                    <PhoneIcon class="btn-header w-8" />
                </div>
            </dl>
            <div v-if="item" class="flex-1 flex flex-col py-3">
                <div :class="['status-' + CancelStatus[item?.status].class, 'p-2 mx-3 box flex gap-2 items-center text-sm mb-3']">
                    <AlertDiamondIcon class="size-9 p-1 bg-white/20 rounded-lg" aria-hidden="true" />
                    <p>
                        {{ CancelStatus[item?.status].desc }}
                    </p>
                </div>
                <Card block class="mx-3">
                    <ul v-if="reservation" class="divide-y">
                        <li class="flex justify-between gap-3 p-2 text-sm">
                            <span class="text-gray-500">Date de la Reservation </span>
                            {{ dateFormat(reservation.date) }}
                        </li>
                        <li class="flex justify-between gap-3 p-2 text-sm">
                            <span class="text-gray-500">L'heure </span>
                            {{ reservation.start_at }} à {{ reservation.end_at }}
                        </li>
                        <li class="flex justify-between gap-3 p-2 text-sm">
                            <span class="text-gray-500">Date d'annulation</span>
                            {{ dateFormat(item?.created_at) }}
                        </li>
                        <li class="flex justify-between gap-3 p-2 text-sm">
                            <span class="text-gray-500">Etat d'annulation</span>
                            <div :class="['px-2 rounded-md status-' + CancelStatus[item?.status].class]">
                                {{ CancelStatus[item?.status].name }}
                            </div>
                        </li>
                    </ul>
                </Card>

                <Card class="p-3 flex-1">
                    <InputField v-model="form.comment" :error="form.errors.comment" :multiline="3" label="Justification"> </InputField>
                    <SingleImageField
                        inline
                        menu
                        :title="form.media?.name || 'File'"
                        :error="form.errors.media"
                        :media-type="form.media?.type"
                        :src="form.media?.path || form.media || null"
                        @delete="onDeleteFile"
                    />
                </Card>
                <!-- <Button class="text-xs" full link danger @click="onDelete"> Supprimer mon document </Button> -->
            </div>
            <div class="page-actions">
                <Button class="text-xs justify-center w-full" full variant="info" submit :disabled="!form.isDirty" @click="onDelete">
                    Confirmer
                </Button>
            </div>
        </Drawer>
        <DialogConfirm
            :show="confirmation"
            :loading="form.processing"
            heading="Confirmer la modification"
            empty-class="text-slate-700"
            @close="confirmation = false"
            @confirm="onCancellationConfirmed"
        >
            Etes-vous sûr que vous voulez enregistrer les modifications ?
            <!-- <Errors :errors="form.errors.id" /> -->
        </DialogConfirm>
    </div>
</template>
