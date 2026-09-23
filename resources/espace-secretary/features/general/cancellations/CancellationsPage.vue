<script setup lang="ts">
import { Badge, Card, Button, Filters, Page, Popup, RadioField, DataTable } from '@shared/components';
import { routes } from '@espace-secretary/routes';
import { useForm } from '@inertiajs/vue3';
import { Description, ItemImage } from '@common/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { CancelStatus, CancelStatusEnum } from '@common/enums';
import { PageDownIcon, CheckIcon, AlertDiamondIcon } from '@adersolutions/icons';
import { useRoute } from '@shared/hooks';
import { DataListType } from '@shared/types';
import { CancellationsType } from '@common/types';
type PropsType = {
    cancellations: DataListType<CancellationsType[]>;
};
defineProps<PropsType>();
const params = useRoute();
const form = useForm<{ status: number | null }>({
    // is_justified: null,
    status: null,
});
const tabs = [
    {
        name: 'Tous',
    },
    {
        name: 'Sans justificatif',
        id: '0',
    },
    {
        name: 'Justifié',
        id: '1',
    },
];
const headings = [
    { name: 'Moniteur' },
    { name: 'candidat' },
    { name: 'Date de demande' },
    { name: 'Date réservée' },
    { name: 'Horaire' },
    { name: 'Message' },
    { name: 'Statut' },
    { name: 'Documents' },
];

const onChangeStudent = (item: CancellationsType) => {
    form.status = item.status;
    // form.is_justified = item.is_justified;
};
const onSubmit = (item: CancellationsType, close: () => void) => {
    form.put(route(routes.cancellations.update, item.id), {
        onFinish: () => {
            form.reset();
            close();
        },
    });
};
</script>

<template>
    <Page title="Les annulations des réservations" width="xl">
        <Card block :separated="false">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="params.is_justified || tabs[0].id" key-tab="is_justified" />
            <DataTable v-slot="{ item }" :headings="headings" :items="cancellations">
                <td class="cell">
                    <ItemImage
                        :src="getFilePath(item.training?.reservation?.monitor?.user)"
                        :href="route(routes.users.monitors.edit, item.training?.reservation?.monitor?.id || '-')"
                        :title="item.training?.reservation?.monitor?.user?.name"
                        :content="item.training?.reservation?.monitor?.user?.email"
                        :phone="item.training?.reservation?.monitor?.user?.phone"
                    />
                </td>
                <td class="cell">
                    <ItemImage
                        :src="getFilePath(item.training?.student?.user)"
                        :href="route(routes.users.students.general, item.training?.student?.id || '-')"
                        :title="item.training?.student?.user?.name"
                        :content="item.training?.student?.user?.email"
                        :phone="item.training?.student?.user?.phone"
                    />
                </td>

                <td class="cell">
                    {{ dateFormat(item.created_at, 'letter') }}
                </td>
                <td class="cell">
                    <div v-if="item.training?.reservation">
                        {{ dateFormat(item.training.reservation?.date, 'letter') }}
                    </div>
                </td>
                <td class="cell">{{ item.training.reservation?.start_at }} à {{ item.training.reservation?.end_at }}</td>
                <td class="cell">
                    <Description :text="item.comment" />
                </td>

                <td class="cell">
                    <Popup @popover-opened="onChangeStudent(item)">
                        <Badge :id="item.status" :options="CancelStatus" />
                        <template #content="{ close }">
                            <Card v-if="item.status == CancelStatusEnum.PENDING" title="Statut change" padding="sm" class="min-w-72 py-2 bg-gray-100 rounded-t-xl">
                                <RadioField v-model="form.status" class="mt-3" :items="Object.values(CancelStatus)" />
                            </Card>
                            <Card v-else title="Statut change" padding="sm" class=" py-2 bg-gray-100 rounded-t-xl">
                                <p class="text-center max-w-xs py-5 flex flex-col items-center gap-3">
                                    <AlertDiamondIcon class="w-10 " />
                                    <span>La séance a étè deja traitée. Vous ne pouvez pas la modifier.</span>
                                </p>
                            </Card>

                            <form class="flex gap-3 border-t p-2 items-center rounded-b-xl" @submit.prevent="onSubmit(item, close)">
                                <Button variant="secondary" @click="close">Fermer</Button>
                                <Button
                                    variant="primary"
                                    submit
                                    full
                                    :loading="form.processing"
                                    :disabled="form.status === item.status || form.status == CancelStatusEnum.PENDING || !form.status"
                                    :icon="CheckIcon"
                                >
                                    Valider
                                </Button>
                            </form>
                        </template>
                    </Popup>
                </td>

                <td class="cell">
                    <a v-if="item.media" class="flex-center flex-col btn-m hover:underline" :href="item.media.storage_media?.path" download>
                        <PageDownIcon class="w-5 text-primary" />
                        <span>Telecharger</span>
                    </a>
                    <div v-else>
                        <span>Pas de document</span>
                    </div>
                </td>
            </DataTable>
        </Card>
    </Page>
</template>
