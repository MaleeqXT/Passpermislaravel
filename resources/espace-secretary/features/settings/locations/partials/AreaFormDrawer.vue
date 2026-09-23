<script setup lang="ts">
import { computed, watch } from 'vue';
import { GeneralStatusEnum } from '@common/enums';
import { useForm } from '@inertiajs/vue3';
import { Card, Drawer, Button, InputField, Switch, ButtonGroup } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useEvents } from '@shared/hooks';
import type { AreaType } from '@common/types';

type PropsType = { item: AreaType | null | undefined };

const emit = defineEmits(['close']);
const props = defineProps<PropsType>();
const events = useEvents();
const form = useForm({
    name: '',
    status: false as boolean,
});

const dataAffectation = (item?: PropsType['item']) => {
    form.name = item?.name || '';
    form.status = item?.status ?? true;
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
        return form.put(route(routes.settings.locations.area.update, props.item?.id), {
            onSuccess: handleClear,
        });
    }
    form.post(route(routes.settings.locations.area.store), {
        onSuccess: handleClear,
    });
};

watch(() => props.item, dataAffectation);
</script>

<template>
    <Drawer :show="!!item" :title="isEdit ? 'Modifier Zone' : 'Ajouter une zone'" @close="$emit('close')">
        <Card as="form" padding class="h-full flex flex-col mt-5" @submit.prevent="onSubmit">
            <Switch v-model="form.status" label="Activer" />
            <InputField v-model="form.name" label="Nom de la Zone" :error="form.errors.name" required />
            <ButtonGroup
                class="!justify-between"
                :actions="[
                    { label: 'Fermer', variant: 'secondary', disabled: !form.isDirty, onAction: handleClear },
                    {
                        label: 'Enregistrer' + (!isEdit ? ' Nouveau' : ''),
                        submit: true,
                        variant: isEdit ? 'primary' : 'dark',
                        loading: form.processing,
                    },
                ]"
            />
        </Card>
    </Drawer>
</template>
