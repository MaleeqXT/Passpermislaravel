<script setup lang="ts">
import { DateField, EmptyState, Errors, Select, Scrollable, ButtonGroup } from '@shared/components';
import { routes } from '@espace-secretary/routes';
import { useForm } from '@inertiajs/vue3';
import { ItemImage } from '@common/components';
import { getFilePath, moneyFormat } from '@shared/utils';
import { useQuery } from '@shared/hooks';
import { CheckCircleIcon, SearchIcon, XIcon } from '@adersolutions/icons';
import { computed } from 'vue';
import { UserType } from '@common/types';
import { BulkActionType } from '@shared/types';

const emit = defineEmits(['close']);
const form = useForm({
    student_id: null,
    offer_id: null,
    start_at: null,
    end_at: null,
});
const offersQuery = useQuery({
    url: route(routes.api.offers.index),
    transformable: true,
    mounted: true,
    params: {
        is_cpf: 1,
        archived: 0,
    },
});

const studentsQuery = useQuery({
    url: route(routes.api.students.all, { new_cpf: true }),
    transformable: true,
    callback: (data = []) => data.map((item: UserType) => ({ ...item, ...item.student })),
    mounted: true,
});

const submit = () => {
    form.post(route(routes.cpf.store), {
        preserveScroll: true,
        onSuccess: onClose,
    });
};
const onClose = () => {
    form.reset();
    emit('close');
};
const actions = computed<BulkActionType[]>(() => [
    {
        label: 'Enregister CPF',
        variant: 'primary',
        submit: true,
        disabled: form.processing || !form.isDirty,
        loading: form.processing,
        onAction: submit,
    },
    {
        label: 'Rejeter',
        disabled: form.processing,
        variant: 'secondary',
        onAction: onClose,
    },
]);
</script>

<template>
    <form class="flex flex-col h-full" @submit.prevent="submit">
        <article class="p-4 bg-white shadow-down">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">L'ajoute de CPFs</h2>
                <XIcon class="h-7 w-7 p-0.5 text-gray-500 cursor-pointer btn-m" @click="onClose" />
            </div>
        </article>
        <article class="flex-1 p-4 flex flex-col gap-5">
            <div class="gap-5">
                <DateField v-model="form.start_at" :error="form.errors.start_at" label="Date début" />
                <DateField v-model="form.end_at" :error="form.errors.end_at" label="Date fin" />
            </div>
            <Select
                v-slot="{ selectedItem }"
                v-model="form.student_id"
                :query="studentsQuery"
                :error="form.errors.student_id"
                placeholder="Selectionner une condidat"
                label="Condidat"
                clear
            >
                <ItemImage
                    class="-ml-2"
                    :title="selectedItem.name"
                    :src="selectedItem.media || selectedItem.profile_photo_url"
                    :content="selectedItem.email"
                />
            </Select>
            <Scrollable>
                <span class="block font-medium text-xs text-gray-600 mb-1">Forfait:</span>
                <ul v-if="offersQuery.data.length" class="min-h-60 flex flex-col divide-y rounded-xl bg-white shadow-sm">
                    <li
                        v-for="item in offersQuery.data"
                        :key="item.id"
                        class="pr-10 relative p-2 hover:bg-slate-50 btn-m"
                        @click="form.offer_id = item.id"
                    >
                        <ItemImage
                            is-mobile
                            :src="getFilePath(item, true)"
                            :title="item?.name"
                            size="w-10 h-10"
                            :content="`prix: ${moneyFormat(item.final_price)}`"
                        />
                        <CheckCircleIcon v-if="item.id === form.offer_id" class="absolute top-1/2 -mt-3 right-3 h-6 w-6 text-green-500" />
                    </li>
                </ul>
                <EmptyState
                    v-else-if="!offersQuery.fetching"
                    title="Aucun produit trouvé"
                    class="min-h-60 flex flex-col justify-center"
                    :image="SearchIcon"
                >
                    <p v-if="form.student_id">Il n'y a aucun produit trouvé.</p>
                    <p v-else>voulez vous selectionnez une condidat.</p>
                </EmptyState>
                <Errors :errors="form.errors.offer_id" />
            </Scrollable>
        </article>
        <ButtonGroup :actions="actions" class="px-4 py-3 bg-white shadow-up" />
    </form>
</template>
