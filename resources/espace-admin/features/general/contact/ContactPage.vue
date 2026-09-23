<script setup lang="ts">
import { Card, Filters, DataTable, Page } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useForm } from '@inertiajs/vue3';
import { dateFormat } from '@shared/utils';
import { Description } from '@common/components';
import { computed } from 'vue';
defineProps({
    contacts: {
        type: Object,
        default: () => ({}),
    },
});
const form = useForm({
    is_read: 0,
});
const tabs = [
    {
        name: 'Tous',
        id: 'all',
    },
    {
        name: 'Non lu',
        id: '0',
    },
    {
        name: 'Lu',
        id: '1',
    },
];
const headings = [{ name: 'Client' }, { name: 'Email & Télèphone' }, { name: 'Sujet' }, { name: 'Message' }, { name: 'Date' }];
const bulkActions = computed(
    () => (item) =>
        item.is_read == 0
            ? [
                  {
                      title: 'Marquer comme lu',
                      variant: 'primary',
                      loading: form.processing,
                      onAction: (item, close) => {
                          form.is_read = 1;
                          form.put(route(routes.contact.update, item.id), {
                              onFinish: () => {
                                  form.reset();
                                  close();
                              },
                          });
                      },
                  },
              ]
            : []
);
</script>

<template>
    <Page title="Message clients" width="xl">
        <Card block :separated="false">
            <Filters :tabs="tabs" :default-tab="tabs[1].id" key-tab="is_read" />
            <DataTable v-slot="{ item }" :headings="headings" :items="contacts" :bulk-actions="bulkActions">
                <td class="cell">{{ item.nom }} {{ item.prenom }}</td>
                <td class="cell">
                    <p>{{ item.email }}</p>
                    <p>{{ item.phone }}</p>
                </td>
                <td class="cell">
                    {{ item.subject }}
                </td>
                <td class="cell">
                    <Description :text="item.message" />
                </td>

                <td class="cell">
                    {{ dateFormat(item.created_at, 'letter') }}
                </td>
            </DataTable>
        </Card>
    </Page>
</template>
