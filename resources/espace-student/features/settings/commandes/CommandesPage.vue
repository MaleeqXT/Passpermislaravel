<script setup lang="ts">
import { EmptyState, Spinner } from '@shared/components';
import { PageMobile } from '@common/components';
import { ref } from 'vue';
import { CommandeItemCard } from './partials';
import { routes } from '@espace-student/routes';
import { watch } from 'vue';
import axios from 'axios';
import { SizeEnum } from '@shared/enums';

const props = defineProps({
    sales: Object,
});

const data = ref(props.sales?.data || []);
const loading = ref(false);
watch(props, ({ sales }) => {
    data.value = sales?.data || [];
});
const onLoadmore = () => {
    loading.value = true;
    axios.get(route(routes.commandes.index, { page: props.sales.current_page + 1 })).then(({ props }) => {
        loading.value = false;
        data.value = [...data.value, ...props.sales.data];
    });
};
</script>

<template>
    <PageMobile title="Mes Commandes" subtitle="Retrouvez tous vos achats en un coup d'œil." :width="SizeEnum.MD" immediate slided>
        <ul class="space-y-3 w-full my-3">
            <CommandeItemCard v-for="item in data" :key="item.id" :item="item" />
            <li
                v-if="sales?.next_page_url"
                class="btn-hover bg-slate-50 w-32 flex-center mx-auto rounded-full h-8 shadow-sm text-slate-500 text-sm font-medium"
                @click="onLoadmore"
            >
                <Spinner v-if="loading" class="w-5 mx-auto" />
                <span v-else>Charger plus</span>
            </li>
            <EmptyState v-if="!sales?.total" as="li" class="py-16 md:py-10">
                <p class="text-sm text-gray-500">Vous n'avez pas encore effectué d'achat.</p>
            </EmptyState>
        </ul>

        <!-- <DetailsAnnulationModal :item="selected" @close="selected = null" /> -->
    </PageMobile>
</template>
