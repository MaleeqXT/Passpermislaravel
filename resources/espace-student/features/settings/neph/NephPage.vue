<script setup lang="ts">
import { PageMobile } from '@common/components';
import { Card, DateField, InputField } from '@shared/components';
import { ChevronLeftIcon } from '@adersolutions/icons';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { routes } from '@espace-student/routes';
import { useApp } from '@shared/stores';
import { SizeEnum } from '@shared/enums';

const { user } = useApp();

const form = useForm({
    neph: user?.student?.neph || '',
    date_code: user?.student?.date_code || '',
});

const actions = computed(() => [
    {
        label: 'Enregistrer',
        variant: 'warning',
        full: true,
        loading: form.processing,
        disabled: !form.isDirty,
        onAction: onSubmit,
    },
]);

const onBack = () => {
    history.back();
};

const onSubmit = () => {
    form.put(route(routes.settings.neph.update), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults();
        },
    });
};
</script>

<template>
    <PageMobile title="Examen Infos" :width="SizeEnum.SM" :actions="actions" back>
        <Card class="grid gap-3 mt-4">
            <InputField id="neph" v-model="form.neph" :error="form.errors.neph" label="Neph" mask="### ### ### ###" required />
            <DateField
                v-model="form.date_code"
                :error="form.errors.date_code"
                :min-date="new Date()"
                format="dd/MM/yyyy"
                label="Date de code"
            />
        </Card>
    </PageMobile>
</template>
