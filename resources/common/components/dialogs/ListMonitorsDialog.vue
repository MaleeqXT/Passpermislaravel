<script setup lang="ts">
import { reactive, watch } from 'vue';
import {
    Button,
    ButtonGroup,
    Dialog,
    EmptyState,
    RadioField,
    MultiCheckField,
    SearchField,
    Spinner,
    Thumb,
    Errors,
} from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import type { UserType } from '@common/types';
import { getFilePath } from '@shared/utils';
import { SizeEnum } from '@shared/enums';

interface PropsType {
    form?: boolean;
    filters?: any;
    multi?: boolean;
    disabled?: boolean;
    errors?: string[] | string;
}
type ModelType = Partial<UserType> | null | [];
const emit = defineEmits(['change']);
const props = withDefaults(defineProps<PropsType>(), {
    form: false,
    filters: () => ({}),
});

const selectedItem = defineModel<ModelType | UserType[]>({
    type: Object,
    default: null,
});

const state = reactive<{ show: boolean; selected: ModelType }>({
    show: false,
    selected: props.multi ? [] : null,
});

const monitorsQuery = useQuery({
    url: route(routes.api.monitors.all),
    transformable: true,
    callback: (data: any[] = []) => data.map((item) => ({ ...item, ...item.monitor })),
});

const onFetch = () => {
    monitorsQuery.params = props.filters || {};
    monitorsQuery.fetch();
};

const onOpen = () => {
    state.show = true;
    !monitorsQuery.meta.total && onFetch();
};

// const onChange = (value: UserType) => {
//     state.selected = value;
//     emit('change', value);
// };
// const onChanges = (value: UserType[]) => {
//     // state.selected = value;
//     emit('change', value);
// };

watch(() => JSON.stringify(props.filters), onFetch);
</script>
<template>
    <div>
        <div
            :class="[form ? 'list-form-control' : 'filter-control', disabled && '!bg-gray-200 pointer-events-none opacity-70']"
            @click="onOpen"
        >
            <div v-if="Array.isArray(selectedItem) && selectedItem.length" class="flex items-center px-2 gap-2 truncate">
                <span class="truncate">{{ selectedItem.length }} Moniteurs: </span>
                <div class="flex items-center -space-x-2 -mr-1">
                    <Thumb
                        v-for="item in selectedItem"
                        :key="item?.id"
                        class="border shadow-box hover:scale-125 t-3 hover:z-10"
                        :size="SizeEnum['2XS']"
                        :tooltip="item.name"
                        :src="getFilePath(item)"
                    />
                </div>
            </div>
            <p v-else-if="!Array.isArray(selectedItem) && selectedItem" class="flex items-center px-2 gap-1 truncate">
                <span :class="[form && 'form-label']"> Moniteur: </span>
                <b class="form-value">{{ selectedItem?.name }}</b>
            </p>
            <span v-else class="flex items-center px-2 truncate">{{ form ? 'Selectionner une Moniteur' : 'Filtrer par moniteur' }}</span>
        </div>
        <Errors v-if="errors" :errors="errors" />
        <Dialog
            :show="state.show"
            :title="form ? 'Choisir le Moniteur' : 'Filtrer par un ou plusieurs Moniteurs'"
            z-index="z-900"
            max-width="xs"
            @close="state.show = false"
        >
            <ul class="grid bg-gray-100 p-2">
                <li class="flex-1 flex flex-col relative min-h-80">
                    <SearchField
                        v-model="monitorsQuery.params.search"
                        class="bg-gray-200 rounded-lg mb-2 !h-9"
                        @change="monitorsQuery.fetch()"
                    />
                    <div v-if="monitorsQuery.fetching" class="bg-white/30 absolute inset-0 flex-center">
                        <Spinner class="w-5" />
                    </div>
                    <EmptyState v-else-if="!monitorsQuery.meta.total" heading="Aucune zone disponible" />
                    <RadioField v-else-if="!multi" v-model:full="state.selected" :items="monitorsQuery.data" />
                    <template v-else>
                        <MultiCheckField v-model="state.selected" :items="monitorsQuery.data" />
                        <Button v-if="monitorsQuery.links.next" class="mx-auto" link variant="info" @click="monitorsQuery.fetchNext()">
                            Voir plus
                        </Button>
                    </template>
                </li>
            </ul>
            <template #footer>
                <ButtonGroup
                    class="md:!gap-x-2"
                    :actions="[
                        {
                            label: 'Annuler',
                            variant: 'secondary',
                            onAction: () => {
                                state.show = false;
                            },
                        },
                        {
                            label: 'Effacer',
                            variant: 'secondary',
                            onAction: () => {
                                emit('change', multi ? [] : null);
                                selectedItem = multi ? [] : null
                                state.selected = multi ? [] : null
                                state.show = false;

                            },
                        },
                        {
                            label: 'Valider',
                            variant: 'primary',
                            onAction: () => {
                                const value = JSON.parse(JSON.stringify(state.selected)) as ModelType | UserType[];
                                emit('change', value);
                                selectedItem = value
                                state.show = false;
                            },
                        },
                    ]"
                />
            </template>
        </Dialog>
    </div>
</template>
