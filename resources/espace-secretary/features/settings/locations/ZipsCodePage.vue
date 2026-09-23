<script setup lang="ts">
import { Page } from '@shared/components';

import { Card, DialogConfirm, Filters, DataTable } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ZipForm, LieuZipItem } from './partials';

defineProps({
    zips: {
        type: Object,
        default: () => ({}),
    },
    zone: {
        type: Object,
        default: () => ({}),
    },
});
const confirmation = ref(null);
const deleting = ref(false);
const tabs = [
    { name: 'Actif', id: '' },
    { name: 'Archivé', id: 0 },
    { name: 'Tout', id: 'all' },
];
const headings = [
    { name: 'Zone', className: 'text-start px-3 font-normal py-2' },
    { name: 'Status', className: 'text-center px-3 font-normal' },
    { name: 'Action', className: 'text-center px-3 sticky right-0 bg-[#f9fafb] border-l-[1px] border-[#e2e8f0]' },
];

const onConfirmedDelete = () => {
    deleting.value = true;
    router.delete(route(routes.settings.locations.zip.destroy, confirmation.value?.id), {
        onFinish: () => {
            deleting.value = false;
        },
        onSuccess: () => {
            confirmation.value = null;
        },
    });
};
</script>

<template>
    <Page title="Code postals" :subtitle="`Zone: ${zone.name}`" width="md" back>
        <Card block :separated="false" class="mt-8">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[0].id" key-tab="status">
                <ZipForm :zone="zone" />
            </Filters>
            <DataTable :headings="headings" :items="zips">
                <template #items="{ items }">
                    <LieuZipItem v-for="item in items" :key="item.id" is-zip :item="item" @item:delete="confirmation = item" />
                </template>
            </DataTable>
        </Card>

        <DialogConfirm :loading="deleting" :show="!!confirmation" @close="confirmation = null" @confirm="onConfirmedDelete">
            Etes-vous sûr que vous voulez archiver le code postal
            <b class="text-red-500">{{ confirmation?.code }}</b>
            ?
        </DialogConfirm>
    </Page>
</template>
