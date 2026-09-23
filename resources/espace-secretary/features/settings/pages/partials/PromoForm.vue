<script setup lang="ts">
import { Button } from '@shared/components';
import { useForm } from '@inertiajs/vue3';
import PromoFormRefs from './PromoFormRefs.vue';
import { routes } from '@espace-admin/routes';

const props = defineProps({
    page: {
        type: Object,
        default: () => ({}),
    },

    isEdit: Boolean,
});
const form = useForm({
    title: props.page?.title || '',
    is_active: props.page?.is_active || false,
    offers: props.page?.offers?.map((v) => v.id) || [],
    start_at: props.page?.start_at || null,
    end_at: props.page?.end_at || null,
    extra: props.page?.extra || {
        start_at: null,
        end_at: null,
        offers: [],
    },
});

const handleclose = () => {
    form.reset();
};

const onSubmit = () => {
    if (props.page?.id) return form.post(route(routes.pages.promo.update, props.page?.id));
    form.post(route(routes.pages.promo.store));
};
</script>
<template>
    <div class="mt-5">
        <PromoFormRefs :values="form" />
        <div class="flex justify-between gap-5 mt-10">
            <Button variant="secondary" @click="handleclose"> Reintialiser </Button>
            <Button variant="primary" :disabled="!form.isDirty" :loading="form.processing" @click="onSubmit">
                {{ isEdit ? 'Modifier' : 'Enregistrer' }}
            </Button>
        </div>
    </div>
</template>
