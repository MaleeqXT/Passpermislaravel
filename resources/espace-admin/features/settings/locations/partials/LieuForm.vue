<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { useForm } from '@inertiajs/vue3';
import { Errors, TabSwitch, Button } from '@shared/components';
import { ActivationStatus } from '@common/enums';
const props = defineProps({
    zone: Object,
});
const form = useForm({
    name: '',
    status: 1,
    url: '',
});
const submit = () => {
    form.post(route(routes.settings.locations.places.store, props.zone?.id), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <form class="flex gap-3 md:gap-10 w-full items-center" @submit.prevent="submit">
        <div class="flex-1">
            <div class="flex flex-col border border-gray-300 divide-y rounded-lg overflow-hidden shadow-sm -my-1">
                <input
                    v-model="form.name"
                    type="text"
                    class="text-xs focus:ring-0 p-1 h-7 !border-none"
                    placeholder="Nouveau localisation"
                    autofocus
                />
                <div></div>
                <input
                    v-model="form.url"
                    type="url"
                    class="text-xs focus:ring-0 p-1 h-7 !border-none text-blue-500"
                    placeholder="URL: https://google.com/maps?q=1.00,-0.00"
                />
            </div>
            <Errors :error="form.errors.name" />
            <Errors :error="form.errors.url" />
        </div>
        <TabSwitch v-model="form.status" class="bg-slate-200" :items="Object.values(ActivationStatus)" />
        <div class="flex-center h-fit gap-2">
            <Button
                variant="primary"
                submit
                :disabled="form.processing || !form.name"
                :loading="form.processing"
                class="self-center !text-sm"
            >
                Enregistrer
            </Button>
        </div>
    </form>
</template>

<style scoped></style>
