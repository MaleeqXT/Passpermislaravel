<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { useForm } from '@inertiajs/vue3';
import { TabSwitch, InputField, Button } from '@shared/components';
import { ActivationStatus } from '@common/enums';
const props = defineProps({
    zone: Object,
});
const form = useForm({
    status: 1,
    code: '',
});
const submit = () => {
    form.post(route(routes.settings.locations.zip.store, props.zone?.id), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <form class="flex gap-3 md:gap-10 w-full" @submit.prevent="submit">
        <InputField
            id="zone-code"
            v-model="form.code"
            class="flex-1"
            label="Ajouter une nouveau code postal"
            :error="form.errors.code"
            required
        />
        <TabSwitch v-model="form.status" class="bg-slate-200" :items="Object.values(ActivationStatus)" />
        <div class="flex-center h-fit gap-2">
            <Button
                variant="primary"
                submit
                :disabled="form.processing || !form.code"
                :loading="form.processing"
                class="self-center !text-sm"
            >
                Enregistrer
            </Button>
        </div>
    </form>
</template>
