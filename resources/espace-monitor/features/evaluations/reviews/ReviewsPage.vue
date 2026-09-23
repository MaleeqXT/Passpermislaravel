<script setup lang="ts">
import { Button, EmptyState } from '@shared/components';
import { ReviewItem } from './partials';
import { getFilePath } from '@shared/utils';
import { DialogPageAction, ReviewFormDrawer } from '@espace-monitor/components';
import { DataView, ItemImage, PageMobile } from '@common/components';
import { useQuery } from '@shared/hooks';
import { routes } from '@espace-monitor/routes';
import { ChatIcon } from '@adersolutions/icons';
import { reactive } from 'vue';
import { useApp } from '@shared/stores';

const props = defineProps({
    student: {
        type: Object,
        default: () => ({}),
    },
});
const { user } = useApp();
const state = reactive({
    selected: null,
});
const reviews = useQuery({
    url: route(routes.api.reviews.index, { student_id: props.student?.id }),
    transformable: true,
    mounted: true,
});
const handleClickItem = (item) => {
    if (user.id !== item.reservation?.monitor?.user_id) {
        return;
    }
    state.selected = item;
};
const isDisabled = (item) => user.id !== item.reservation?.monitor?.user_id || item.is_absent || !!item.comment;
</script>

<template>
    <PageMobile :title="student.user?.name" back>
        <DataView :query="reviews">
            <ul class="grid py-2 overflow-hidden rounded-xl gap-1">
                <ReviewItem
                    v-for="item in reviews.data"
                    :key="item.id"
                    :item="item"
                    :class="[isDisabled(item) ? 'pointer-events-none bg-gray-200' : '']"
                    @click="handleClickItem(item)"
                />
            </ul>
        </DataView>
        <!-- <div
            class="max-w-lg mx-auto text-slate-900 md:border bg-slate-100 md:bg-white md:mt-4 md:rounded-b-3xl mb-8 md:px-3 relative rounded-t-3xl flex-1 w-full"
        >
            <ul class="grid py-2 overflow-hidden rounded-xl gap-1">
                <EmptyState v-if="!reviews.meta?.total && !reviews.fetching" heading="Aucune Reviews" class="y-3" :image="ChatIcon">
                    <p>Vous n'avez aucune Reviews pour le moment</p>
                </EmptyState>
                <ReviewItem
                    v-for="item in reviews.data"
                    :key="item.id"
                    :item="item"
                    :class="[isDisabled(item) ? 'pointer-events-none bg-gray-200' : '']"
                    @click="handleClickItem(item)"
                />

                <Button v-if="reviews.links?.next" class="mx-auto" :loading="reviews.fetchingMore" @click="reviews.fetch(null, {}, true)">
                    Charger plus
                </Button>
            </ul>
        </div> -->

        <ReviewFormDrawer :item="state.selected" @close="state.selected = null" @refresh="reviews.fetch()" />
    </PageMobile>
</template>
