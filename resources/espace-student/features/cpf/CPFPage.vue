<script setup lang="ts">
import { PageMobile } from '@common/components';
import { reactive } from 'vue';
import { ChevronRightIcon, SearchIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { EmptyState } from '@shared/components';
defineProps({
    cpfs: {
        type: Object,
        default: () => ({}),
    },
});

const state = reactive({
    selected: null,
});
</script>

<template>
    <PageMobile width="xs" class-wrapper="h-screen mx-auto" title="Mon CPF" :profile="false" slided dark back>
        <div class="md:bg-white h-full relative z-900 rounded-xl mt-3 py-5 overflow-hidden">
            <ul class="grid divide-y">
                <Link
                    v-for="item in cpfs.data"
                    :key="item.id"
                    :href="route(routes.cpf.view, item.id)"
                    class="btn-m flex gap-3 px-3 py-3 relative hover:bg-slate-50"
                    :style="{ '--customcolor': item.offer?.color }"
                    @click="state.selected = item"
                >
                    <span class="absolute bg-custom left-0 inset-y-1 w-1.5 rounded-r-lg"></span>
                    <p class="flex-1 font-bold">
                        {{ item.offer?.name }}
                    </p>
                    <div class="flex-center text-sm text-custom">Voir <ChevronRightIcon class="w-5" /></div>
                </Link>
                <EmptyState v-if="!cpfs.total" :image="SearchIcon" class="py-16 bg-slate-50 rounded-xl shadow" heading="Aucun commentaire">
                    <p class="text-gray-500">Aucun CPF n'a été trouvé.</p>
                </EmptyState>
            </ul>
        </div>
    </PageMobile>
</template>
<style>
.color {
    background-color: var(--cpfcolor);
}
</style>
