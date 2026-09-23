<script setup lang="ts">
import { Switch, InputField, Card } from '@shared/components';
import { getFilePath, dateFormat } from '@shared/utils';
import { DialogPageAction } from '@espace-monitor/components';
import { ItemImage, PageMobile } from '@common/components';
import { useMutation } from '@shared/hooks';
import { routes } from '@espace-monitor/routes';
import { ListBulletedFilledIcon } from '@adersolutions/icons';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    review: {
        type: Object,
        default: () => ({}),
    },
});
const form = useMutation({
    comment: props.review?.comment || '',
    estimation: props.review?.estimation || null,
    is_absent: props.review?.is_absent || false,
});
const reservation = computed(() => props.review?.reservation || {});
const student = computed(() => props.review?.reservation?.training?.student || {});

const onSubmit = () => {
    form.put(route(routes.api.reviews.update, props.review?.id || '-')).then(() => {
        history.back();
    });
};
</script>

<template>
    <PageMobile class="flex flex-col w-full min-h-screen">
        <template #nav>
            <Link
                class="w-10 p-2 text-white bg-slate-900/10 rounded-full drop-shadow btn-hover"
                :href="route(routes.reviews.index, student.id)"
                tooltip="Voir tous les reviews"
                tb
            >
                <ListBulletedFilledIcon class="w-6" />
            </Link>
        </template>
        <template #title="{ isSticky }">
            <div class="relative px-3 text-white z-1 w-full t-5">
                <ItemImage
                    :src="getFilePath(student.user)"
                    :title="student.user?.name"
                    is-mobile
                    :size="isSticky ? 'w-8 h-8' : 'w-10 h-10'"
                    class="relative"
                />
            </div>
        </template>
        <section
            class="md:rounded-xl md:shadow-box-2 md:bg-white flex-col flex-1 md:max-w-lg mx-auto w-full my-4 flex flex-xol overflow-hidden"
        >
            <Card
                :title="`Leçon de ${dateFormat(reservation.date, 'letter')} ${reservation.start_at} à ${reservation.end_at}`"
                class="!block space-y-3 md:p-4 flex-1"
            >
                <InputField
                    v-model="form.comment"
                    label="Commentaire"
                    placeholder="Ecrivez commentaire de votre review"
                    :multiline="3"
                    :error="form.errors.comment"
                    :disabled="!!review?.is_absent || !!review?.comment"
                />
                <InputField
                    v-if="review?.is_estimated"
                    v-model="form.estimation"
                    label="Estimation"
                    placeholder="Estimez les heures de votre leçon"
                    type="number"
                    :error="form.errors.estimation"
                />
                <Switch
                    v-model="form.is_absent"
                    :disabled="!!review?.is_absent || !!review?.comment"
                    :errors="form.errors.is_absent"
                    label="L'élève est absent"
                />
            </Card>
            <DialogPageAction
                class="md:!bottom-0 md:!rounded-none md:!border-none"
                apply
                :loading="form.mutating"
                :text="!!review?.is_absent || !!review?.comment ? 'Vous ne pouvez pas modifier cet avis' : 'Enregistrer'"
                :disabled="!!review?.is_absent || !!review?.comment"
                back
                @click="onSubmit"
            />
        </section>
    </PageMobile>
</template>
