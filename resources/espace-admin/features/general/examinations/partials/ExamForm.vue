<script setup lang="ts">
import { Card, DateField, TabSwitch, ButtonGroup, InputField } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useForm } from '@inertiajs/vue3';
import { AreaSelectionDialog, ListMonitorsDialog, SelectAreaPlaces } from '@common/components';
import { ExamenResultList, ExamenStatusEnum, ExamenStatusList, YesNoStatus } from '@common/enums';
import { ExamType } from '@common/types';
import { GroupActionType } from '@shared/types';
import { computed } from 'vue';
const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    actions: Array,
    isEdit: Boolean,
    isPassage: Boolean,
});

// const tabsStatus = [examStatus[EXAM_LIST_ATTENTE], examStatus[EXAM_PRE_LIST]];
const form = useForm<Partial<ExamType>>({
    user_id: props.data?.user_id || null,
    student_id: props.data?.student_id || null,

    date_examen: props.data?.date_examen || null,
    heure_passage: props.data?.heure_passage || null,
    comment: props.data?.comment || null,
    result_permis: props.data?.result_permis,
    status: props.data?.status ?? ExamenStatusEnum.PENDING,
});

const actions = computed((): GroupActionType[] => [
    {
        label: `${props.isEdit ? 'Enregister' : 'Ajouter'} modification`,
        variant: 'primary',
        submit: true,
        disabled: !form.isDirty,
        loading: form.processing,
        onAction: () => submit(),
    },
    {
        label: 'Rejeter',
        disabled: !form.isDirty,
        variant: 'secondary',
        onAction: () => form.reset(),
    },
]);

const submit = () => {
    form.transform((data) => {
        if (data.media?.id) {
            data.media = data.media.id;
        }
        return data;
    });

    form.post(route(routes.exams.update, props.data.id));
};
</script>

<template>
    <form class="py-10 gap-5 grid grid-cols-3" @submit.prevent="submit">
        <div class="col-span-2">
            <Card block padding title="Details">
                <div class="bg-white grid lg:grid-cols-2 gap-5">
                    <div class="flex justify-between items-center col-span-2">
                        <span class="block font-medium text-xs text-gray-600 mb-0.5 my-1">Statut</span>
                        <TabSwitch v-model="form.status" class="text-xs bg-gray-200" size="md" :items="Object.values(ExamenStatusList)" />
                    </div>
                </div>
                <div class="bg-white grid lg:grid-cols-2 gap-5">
                    <div class="flex justify-between items-center col-span-2">
                        <span class="block font-medium text-xs text-gray-600 mb-0.5 my-1">Result permis</span>
                        <TabSwitch
                            v-model="form.result_permis"
                            class="text-xs bg-gray-200"
                            size="md"
                            :items="Object.values(ExamenResultList)"
                        />
                    </div>
                </div>
                <InputField v-model="form.comment" label="Commentaire" :error="form.errors.comment" :multiline="3" />
            </Card>
            <ButtonGroup :actions="actions" />
        </div>

        <Card class="space-y-5">
            <DateField v-model="form.date_examen" label="Date d'examen" :min-date="new Date()" :error="form.errors.date_examen" />
            <DateField
                v-model="form.heure_passage"
                :error="form.errors.heure_passage"
                time
                min-time="07:00"
                max-time="23:00"
                label="Heure de début"
            />
        </Card>
    </form>
</template>
