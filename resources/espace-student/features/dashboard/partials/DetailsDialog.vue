<script setup lang="ts">
import { PageDownIcon, CalendarIcon, ClockIcon } from '@adersolutions/icons';
import { Card, Button, DialogConfirm, Errors, Dialog, InputField } from '@shared/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { ItemImage } from '@common/components';
import { useForm } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { computed } from 'vue';
import { CancelStatus } from '@common/enums';
import { ReservationLocateDetail } from '@common/components';

const emit = defineEmits(['close', 'refresh']);

const props = defineProps({
    item: [Object, null],
    isPassed: Boolean,
});

const form = useForm({
    training_id: null,
    is_justified: false,
    comment: '',
});

const cancellation = computed(() => props.item?.training?.cancellation);

const onTryCancel = () => {
    form.training_id = props.item?.training?.id || null;
};
const onCancel = () => {
    form.post(route(routes.cancellations.store), {
        onSuccess: () => {
            form.reset();

            emit('close');
            emit('refresh');
        },
    });
};
</script>

<template>
    <div class="contents">
        <Dialog
            :show="!!item"
            class-name="!p-0"
            custom-class="modal-mobile h-[calc(90%-3.5rem)] md:h-fit max-h-screen md:max-h-[calc(100vh-2rem)]"
            title="Session details"
            subtitle="Vérifier les détails de la session et de l'activité"
            @close="$emit('close')"
        >
            <div class="flex-1 divide-y space-y-5">
                <Card class="px-3">
                    <h4 class="font-semibold text-gray-800 text-sm mb-1 mt-3">Leçon details</h4>
                    <Card block :separated="false" class="divide-y text-md bg-white !mb-1">
                        <li :class="['flex items-center gap-3 p-2 justify-between']">
                            <div class="flex-center gap-2 font-bold">
                                <CalendarIcon class="min-w-[20px] w-5 text-gray-500" />
                                Date
                            </div>
                            <span v-if="item?.date">{{ dateFormat(item.date, 'letter') }}</span>
                        </li>
                        <li :class="['flex items-center gap-3 p-2 justify-between']">
                            <div class="flex-center gap-2 font-bold">
                                <ClockIcon class="min-w-[20px] w-5 text-gray-500" />
                                Heures
                            </div>
                            <span>{{ item?.start_at }} à {{ item?.end_at }}</span>
                        </li>
                    </Card>
                    <ReservationLocateDetail v-if="item?.lieu" :lieu="item?.lieu" />
                    <h4 class="font-semibold text-gray-800 text-sm mb-1">Votre enseignement</h4>
                    <Card v-if="item" block padding="sm" class="!flex-row">
                        <ItemImage
                            :src="getFilePath(item.monitor?.user)"
                            :title="item.monitor?.user?.name"
                            :phone="item.monitor?.user?.phone"
                            is-mobile
                            size="w-10 h-10"
                            class="text-slate-950 text-sm font-bold"
                        />
                    </Card>

                    <h4 class="font-semibold text-gray-800 text-sm mb-1">Votre forfait</h4>
                    <Card v-if="item?.training?.offer" block padding="sm" class="!flex-row">
                        <ItemImage
                            :src="getFilePath(item?.training?.offer, true)"
                            is-mobile
                            size="w-10 h-10"
                            class="text-slate-950 text-sm font-bold"
                            :title="item?.training?.offer?.name"
                            :content="'Balance: ' + item?.training?.offer?.balance"
                        />
                    </Card>

                    <!-- review monitor -->
                    <Card
                        v-if="item?.review_monitor"
                        class="border-t pt-1"
                        title="Avis du moniteur"
                        :subtitle="dateFormat(item?.review_monitor?.created_at, 'letter')"
                    >
                        <template #action>
                            <b
                                :class="[
                                    'rounded-full px-3 py-0.5 text-white text-xs',
                                    item.review_monitor.status === 1 ? 'bg-emerald-600' : 'bg-red-600',
                                ]"
                            >
                                {{ item.review_monitor.status === 1 ? 'Acquis' : 'A travailler' }}
                            </b>
                        </template>
                        <p class="text-xs mt-2">
                            {{ item.review_monitor?.comment }}
                        </p>
                    </Card>
                </Card>
                <Card v-if="cancellation" class="p-3 bg-slate-100e">
                    <h3 class="text-lg font-bold mb-3">Annulation</h3>
                    <ul class="divide-y text-md">
                        <li :class="['flex items-center gap-3 py-2 justify-between']">
                            <b>Statut</b>
                            <b :class="['rounded-full px-3 py-0.5 text-white text-xs', CancelStatus[cancellation.status]?.color]">
                                {{ CancelStatus[cancellation.status]?.label }}
                            </b>
                        </li>
                        <li :class="['flex items-center gap-3 py-2 justify-between  ']">
                            <b>Date</b>
                            <span>{{ dateFormat(cancellation.created_at, 'letter') }}</span>
                        </li>
                        <li :class="['flex items-center gap-3 py-2 justify-between  ']">
                            <b>Commentaire</b>
                            <p v-html="cancellation.comment"></p>
                        </li>
                        <li :class="['flex items-center gap-3 py-2 justify-between  ']">
                            <b>Documents</b>

                            <a
                                class="font-bold flex-center gap-2 text-blue-500"
                                role="button"
                                :href="cancellation?.media?.storage_media?.path || '#'"
                                rel="noopener noreferrer"
                                :download="cancellation?.media?.storage_media?.name"
                            >
                                <PageDownIcon class="w-5" />
                                télécharger
                            </a>
                        </li>
                    </ul>
                </Card>
            </div>
            <div class="gap-2 bg-white sticky bottom-0 shadow-up h-fit">
                <div class="flex items-center gap-3 p-2">
                    <Button v-if="!cancellation" class="col-start- justify-center" danger submit :disabled="isPassed" @click="onTryCancel">
                        Annuler Leçon
                    </Button>
                    <Button class="col-start- justify-center flex-1" dark @click="$emit('close')"> Fermer </Button>
                </div>
            </div>
        </Dialog>
        <DialogConfirm :show="!!form.training_id" :loading="form.processing" @close="form.reset()" @confirm="onCancel">
            Etes-vous sûr que vous pouvez annuler cette session?
            <div class="px-4 mt-10 -mb-16 py-2 text-left text-gray-950 bg-gray-100 border-b border-slate-300">
                <InputField
                    v-model="form.comment"
                    :error="form.errors.comment"
                    :multiline="3"
                    label="Justification"
                    placeholder="Ajoute commentaire"
                >
                </InputField>
                <Errors :errors="form.errors.training_id" />
            </div>
        </DialogConfirm>
    </div>
</template>
