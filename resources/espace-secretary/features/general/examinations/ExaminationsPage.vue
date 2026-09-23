<script setup lang="ts">
import { reactive } from 'vue';
import { ChatIcon, EditIcon, PlusCircleIcon } from '@adersolutions/icons';
import { Page, Card, Button, InputField, Popup, DataTable, Badge } from '@shared/components';
import { dateFormat } from '@shared/utils';
import { bulkActions, getReservation, headings } from './exam';
import { router } from '@inertiajs/vue3';
import { routes } from '@espace-secretary/routes';
import { Link } from '@inertiajs/vue3';
import { ExamFilters } from './partials';
import { ExamenStatusList, GearboxTypes } from '@common/enums';
import type { DataListType } from '@shared/types';
import type { ExamType } from '@common/types';
type PropsType = {
    exams: DataListType<ExamType>;
};
type StateType = {
    selectedItem: null | ExamType;
    comment: string | null;
    loading: {
        comment: boolean;
    };
};
defineProps<PropsType>();

const state = reactive<StateType>({
    selectedItem: null,
    comment: '',
    loading: {
        comment: false,
    },
});

const onSubmit = (item: ExamType, close) => {
    const payload = {
        comment: state.comment,
        date_comment: dateFormat(new Date(), 'fulliso'),
    };
    state.loading.comment = true;
    router.post(route(routes.exams.update, item.id), payload, {
        onFinish: () => {
            state.loading.comment = false;
            close();
        },
    });
};
</script>

<template>
    <Page title="Liste d'examination" width="full">
        <!-- <ExamsStatsDPP :stats="stats" /> -->
        <Card block :separated="false">
            <ExamFilters />
            <DataTable v-slot="{ item }" :headings="headings" :bulk-actions="bulkActions" :items="exams">
                <td class="cell">
                    <Link :href="route(routes.users.students.general, item.student_id)" class="w-28 truncate btn btn-link btn-info">
                        {{ item.student?.user?.name }}
                    </Link>
                </td>
                <td class="cell">
                    {{ item.student?.user?.phone }}
                </td>
                <td class="cell">
                    <Badge :id="item.is_auto" :options="GearboxTypes" />
                </td>

                <td class="cell">{{ dateFormat(item.date_examen, 'fr') }} {{ item.heure_passage }}</td>

                <td class="cell">
                    <div v-if="getReservation(item).date">
                        <p>{{ dateFormat(getReservation(item).date, 'letter') }}</p>
                    </div>
                </td>
                <td class="cell">
                    <Badge :id="item.status" :options="ExamenStatusList" />
                </td>
                <td class="cell">
                    <p class="line-clamp-2 text-wrap w-full">
                        {{ item.result_permis }}
                    </p>
                </td>

                <td class="cell text-center">
                    <Popup @popover-closed="state.comment = null">
                        <ChatIcon :class="['w-5 h-5', item.comment ? 'text-green-600' : 'text-gray-600']" />
                        <template #content="{ close }">
                            <div class="p-2 w-96">
                                <InputField
                                    :multiline="3"
                                    label="Commentaire"
                                    input-class="bg-gray-200 border-none form-control"
                                    :model-value="item.comment"
                                    @update:model-value="state.comment = $event"
                                />
                                <p v-if="item.date_comment" class="text-xs pb-2">
                                    Le dernier commentaire a été ajouté le
                                    {{ dateFormat(item.date_comment, 'fulltime') }}
                                </p>
                            </div>
                            <div class="bg-gray-100 flex justify-end items-center gap-5 py-1.5 px-3 rounded-b-xl relative overflow-clip">
                                <div class="rainbow absolute -top-px"></div>
                                <Button link danger @click="close">Annuler</Button>
                                <Button
                                    info
                                    class="h-7"
                                    :disabled="!state.comment"
                                    :loading="state.loading.comment"
                                    @click="onSubmit(item, close)"
                                >
                                    Enregister
                                </Button>
                            </div>
                        </template>
                    </Popup>
                </td>
            </DataTable>
        </Card>
    </Page>
</template>
