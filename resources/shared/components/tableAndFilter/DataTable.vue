<script setup lang="ts">
import { reactive, onMounted, computed, onUnmounted } from 'vue';
import { MenuHorizontalIcon, SearchIcon } from '@adersolutions/icons';
import { Pagination, EmptyState, Button, Popup, DialogConfirm } from '@shared/components';
import { router } from '@inertiajs/vue3';
import { UseQueryType, useEvents, useMutation } from '@shared/hooks';
import { DataListType, BulkActionType } from '@shared/types';

const emit = defineEmits(['refresh']);

// Define the props interface
interface TableHeading {
    name: string;
    className?: string;
    [key: string]: any;
}

// Explicit function signatures
type BulkFuncType = (selectedItems: any | null) => BulkActionType[] | void;

type PropsType = {
    headings: TableHeading[];
    items: DataListType<any> & { fetch?: (...args: any) => Promise<unknown>; fetching?: boolean };
    isSelect?: boolean;
    isBlack?: boolean;
    paginate?: boolean;
    loading?: boolean;
    emptyText?: string;
    isApi?: boolean;

    bulkActions?: BulkActionType[] | BulkFuncType;
    promotedBulkActionTypes?: BulkActionType[] | BulkFuncType;
};

interface IState {
    selectedItem: any | null;
    confirm: any;
    deleting: boolean;
    loading: boolean;
}

const props = withDefaults(defineProps<PropsType>(), {
    paginate: true,
});

const events = useEvents();
const state = reactive<IState>({
    selectedItem: null,
    confirm: null,
    deleting: false,
    loading: false,
});

const deleteMutation = useMutation();

const actions = computed(() => {
    const isEmpty = !state.selectedItem || (!props.bulkActions && !props.promotedBulkActionTypes);
    if (isEmpty) return null;
    return {
        bulk: typeof props.bulkActions === 'function' ? props.bulkActions(state.selectedItem) : props.bulkActions,
        promoted: props.promotedBulkActionTypes,
        selected: state.selectedItem,
    };
});

const close = () => {
    state.selectedItem = null;
};

const handleLineClick = (item: any) => {
    if (item.actionDisabled) return;
    state.selectedItem = item.id === state.selectedItem?.id ? null : item;
};

const onConfirm = () => {
    state.deleting = true;
    if (props.isApi) {
        return deleteMutation
            .mutate(route(state.confirm.url, state.selectedItem?.id), state.confirm?.method || 'delete')
            .then((res) => {
                state.confirm = null;
                state.selectedItem = null;
                emit('refresh', res);
                props.items.fetch?.();
            })
            .finally(() => {
                state.deleting = false;
            });
    }

    const options = {
        onSuccess: () => {
            state.confirm = null;
            state.selectedItem = null;
        },
        onFinish: () => {
            state.confirm?.onFinish();
            state.deleting = false;
        },
    };

    if (state.confirm?.payload) {
        return router.put(route(state.confirm.url, state.selectedItem?.id), state.confirm.payload, options);
    }
    router.delete(route(state.confirm.url, state.selectedItem?.id), options);
};

const handleBulkActionType = (action: BulkActionType) => {
    if (action.confirm) {
        state.confirm = action.confirm;
        return;
    }
    if (action.isLink) {
        const url = action?.onAction?.(state.selectedItem);
        if (typeof url === 'string') {
            if (action.external) {
                return window.open(url, '_blank');
            } else {
                return router.get(url, {});
            }
        }
    }
    action.onAction?.(state.selectedItem, close);
};

onMounted(() => {
    events.on('table:filter:loading', ({ value }) => {
        state.loading = value;
    });
    events.on('table:selected:clear', () => {
        state.selectedItem = null;
    });
});

onUnmounted(() => {
    events.off('table:selected:clear');
});
</script>

<template>
    <div class="grid relative">
        <div class="h-rainbow -translate-y-px saturate-50 opacity-70" />
        <div class="align-middle max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead class="sticky top-0">
                    <tr class="bg-gray-100 border-gray-200">
                        <th colspan="0" class="h-full z-1 !max-w-0" />
                        <th
                            v-for="(head, index) in headings"
                            :key="index"
                            v-bind="head"
                            :class="[
                                'min-w-16 px-2 h-8 text-left text-2xs tracking-tight font-medium text-gray-500 border-l border-gray-200 first-of-type:border-l-0',
                                head.className,
                            ]"
                        >
                            {{ head.name }}
                        </th>
                    </tr>
                    <tr class="h-rainbow absolute w-full block left-0 bottom-0 saturate-0 opacity-70 -mt-px" />
                </thead>
                <tbody>
                    <template v-if="loading || state.loading || items.fetching">
                        <tr v-for="index in 10" :key="index" :class="['animate-pulse min-h-[5rem] group/actions']">
                            <td></td>
                            <td v-for="(_, idx) in headings" :key="idx" class="h-9 px-3 cell">
                                <div class="h-4 bg-gray-400 rounded"></div>
                            </td>
                        </tr>
                    </template>
                    <template v-else-if="items?.data?.length || Object.keys(items?.data).length">
                        <slot name="items" :items="items.data">
                            <tr
                                v-for="(item, idx) in items.data"
                                :key="item.id || idx"
                                :class="[
                                    'hover:bg-gray-50 group/actions relative cursor-pointer t-2',
                                    actions && state.selectedItem?.id === item.id ? '!bg-gray-100 ' : '',
                                ]"
                                @click="handleLineClick(item)"
                            >
                                <td
                                    colspan="0"
                                    :class="[
                                        'left-0 inset-y-1 w-1 rounded-r-full absolute',
                                        actions && state.selectedItem?.id === item.id && 'bg-primary',
                                    ]"
                                ></td>
                                <slot :item="item" :index="idx" />
                            </tr>
                        </slot>
                        <slot name="addItem"></slot>
                    </template>

                    <tr v-else>
                        <td :colspan="headings.length + (isSelect ? 2 : 1)" class="text-center py-16">
                            <slot name="empty">
                                <EmptyState :heading="emptyText || 'Aucune donnée n\'a été trouvée.'" :image="SearchIcon" />
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="(paginate && items?.total) || (paginate && items?.meta?.total)" class="py-2 px-2 flex-1 flex w-full">
            <Pagination :item="items?.meta || items" :is-api="isApi" />
        </div>
        <div v-if="actions" class="flex-center h-0">
            <DialogConfirm
                v-bind="state.confirm"
                :loading="state.deleting"
                :show="!!state.confirm"
                @close="state.confirm = null"
                @confirm="onConfirm"
            >
                {{ state.confirm?.message || 'Etes-vous sûr que vous voulez supprimer cette entrée' }}
            </DialogConfirm>
            <div class="table-actions enter-up">
                <div v-if="actions.bulk?.length" class="flex gap-2 w-full">
                    <Button
                        v-for="(action, idx) in typeof actions.bulk === 'function' ? actions.bulk(actions.selected) : actions.bulk"
                        :key="idx"
                        v-bind="action"
                        :class="[action.class, '!px-6 flex-1 justify-center text-nowrap']"
                        :variant="action.variant || 'secondary'"
                        @click="handleBulkActionType(action)"
                    >
                        {{ action.label || action.title }}
                    </Button>
                </div>
                <div v-if="actions.promoted?.length" class="flex gap-2">
                    <Popup position="top-end">
                        <MenuHorizontalIcon class="!p-1 btn btn-secondary text-gray-500" />
                        <template #content="{ close: onClose }">
                            <div class="flex flex-col divide-y -m-1.5">
                                <div
                                    v-for="(action, idx) in typeof actions.promoted === 'function'
                                        ? actions.promoted(actions.selected)
                                        : actions.promoted"
                                    :key="idx"
                                >
                                    <Button
                                        type="button"
                                        link
                                        v-bind="action"
                                        :class="[action.class, '!px-6 flex-1 py-2 justify-center text-nowrap']"
                                        @click="handleBulkActionType(action), onClose()"
                                    >
                                        {{ action.label || action.title }}
                                    </Button>
                                </div>
                            </div>
                        </template>
                    </Popup>
                </div>
            </div>
        </div>
    </div>
</template>
