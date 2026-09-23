<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Drawer, Button, InputField, Switch, ButtonGroup, TabSwitch } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useEvents } from '@shared/hooks';
import type { CompetencyType } from '@common/types';

type PropsType = { item: CompetencyType | null; length: number };

const emit = defineEmits(['close']);
const props = defineProps<PropsType>();
const events = useEvents();
const form = useForm({
    main_competency_id: '',
    label: '',
    status: true as boolean,
    position: props.length + 1,
});

const dataAffectation = (item?: CompetencyType) => {
    form.main_competency_id = item?.main_competency_id || '-';
    form.label = item?.label || '';
    form.status = (item?.status ?? true) as boolean;
    form.position = item?.position || (props.length || 0) + 1;
    form.defaults();
};

const isEdit = computed(() => !!props.item?.id);

const handleClear = () => {
    dataAffectation();
    emit('close');
    events.emit('table:selected:clear');
};

const onSubmit = () => {
    if (isEdit.value) {
        return form.put(route(routes.settings.competences.sub.update, props.item?.id), {
            onSuccess: handleClear,
        });
    }
    form.post(route(routes.settings.competences.sub.store, props.item?.main_competency_id), {
        onSuccess: handleClear,
    });
};

watch(
    () => props.item,
    (v) => dataAffectation(v as CompetencyType)
);
</script>

<template>
    <Drawer :show="!!item" :title="isEdit ? 'Modifier competence ' + item?.name : 'Nouvelle compétence'" @close="$emit('close')">
        <form class="flex flex-col w-full px-3 flex-1" @submit.prevent="onSubmit">
            <div class="flex-1 space-y-4 py-5">
                <Switch v-model="form.status" label="Activer" />
                <InputField
                    v-model="form.position"
                    class="w-20 flex-none"
                    label="Position"
                    wrapper-class="!px-2"
                    type="number"
                    :error="form.errors.position"
                    required
                />
                <InputField v-model="form.label" label="Label" :error="form.errors.label" required />
            </div>

            <div class="gap-2 p-3 w-full">
                <Button variant="primary" submit full :disabled="form.processing || !form.isDirty" :loading="form.processing">
                    Enregistrer
                </Button>
            </div>
        </form>
    </Drawer>
</template>
