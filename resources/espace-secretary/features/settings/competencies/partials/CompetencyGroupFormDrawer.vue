<script setup lang="ts">
import { computed, watch } from 'vue';
import { ActivationStatus, GeneralStatusEnum } from '@common/enums';
import { useForm } from '@inertiajs/vue3';
import { Drawer, Button, InputField, Switch, ButtonGroup, TabSwitch } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useEvents } from '@shared/hooks';
import type { CompetencyGroupType } from '@common/types';

type PropsType = { item: CompetencyGroupType | null | object; length: number };

const emit = defineEmits(['close']);
const props = defineProps<PropsType>();
const events = useEvents();
const form = useForm({
    name: '',
    label: '',
    status: true as boolean,
    position: 1,
});

const dataAffectation = (item?: CompetencyGroupType) => {
    form.name = item?.name || '';
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
        return form.put(route(routes.settings.competences.group.update, props.item?.id), {
            onSuccess: handleClear,
        });
    }
    form.post(route(routes.settings.competences.group.store), {
        onSuccess: handleClear,
    });
};

watch(
    () => props.item,
    (v) => dataAffectation(v as CompetencyGroupType)
);
</script>

<template>
    <Drawer :show="!!item" :title="isEdit ? 'Modifier group ' + item?.name : 'Ajouter une group'" @close="$emit('close')">
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
                <InputField id="zone-name" v-model="form.name" class="flex-1" label="Nom" :error="form.errors.name" required />
                <InputField v-model="form.label" label="Label" :error="form.errors.label" required />
            </div>
            <div class="gap-2 p-3 w-full">
                <Button variant="primary" submit full :disabled="form.processing || !form.name" :loading="form.processing">
                    Enregistrer
                </Button>
            </div>
        </form>
    </Drawer>
</template>
