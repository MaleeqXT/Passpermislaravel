
<script setup lang="ts">
import { reactive, watch } from 'vue';
import {
    Button,
    ButtonGroup,
    Dialog,
    EmptyState,
    RadioField,
    MultiCheckField,
    Spinner,
    Thumb,
    SearchField,
    Errors,
} from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useQuery } from '@shared/hooks';
import type { UserType } from '@common/types';
import { getFilePath } from '@shared/utils';

interface PropsType {
    form?: boolean;
    filters?: any;
    multi?: boolean;
    disabled?: boolean;
    errors?: string[] | string;
}
type ModelType = Partial<UserType> | null | [];
const emit = defineEmits(['change']);
const selectedItem = defineModel<ModelType | UserType[]>({
    type: Object,
    default: null,
});

const props = withDefaults(defineProps<PropsType>(), {
    form: false,
    filters: () => ({}),
});

const studentsQuery = useQuery({
    url: route(routes.api.students.all),
    transformable: true,
    callback: (data: any[] = []) => data.map((item) => ({ ...item, ...item.student })),
});

const state = reactive<{ show: boolean; selected: ModelType }>({
    show: false,
    selected: null,
});


const onFetch = () => {
    studentsQuery.params = props.filters || {};
    studentsQuery.fetch();
};

const onOpen = () => {
    state.show = true;
    !studentsQuery.meta.total && onFetch();
};

watch(
    () => JSON.stringify(props.filters),
    () => {
        onFetch();
    }
);

</script>

<template>

    <div>
        <div
            :class="[form ? 'list-form-control' : 'filter-control', disabled && '!bg-gray-200 pointer-events-none opacity-70']"
            @click="onOpen"
        >
            <div v-if="Array.isArray(selectedItem) && selectedItem.length" class="flex items-center px-2 gap-4">
                <span class="truncate">{{ selectedItem.length }} Candidats: </span>
                <div class="flex items-center -space-x-3 -mr-2">
                    <Thumb
                        v-for="item in selectedItem"
                        :key="item?.id"
                        class="border shadow-box hover:scale-110 t-3 hover:z-10"
                        size="2xs"
                        :tooltip="item.name"
                        :src="getFilePath(item)"
                    />
                </div>
            </div>
            <p v-else-if="!Array.isArray(selectedItem) && selectedItem" class="flex items-center px-2 gap-1 truncate">
                <span :class="[form && 'form-label']"> Candidat: </span>
                <b class="form-value">{{ selectedItem?.name }}</b>
            </p>
            <span v-else class="flex items-center px-2 truncate">{{ form ? 'Selection une Candidat' : 'Filtrer Par Candidats' }}</span>
        </div>
        <Errors v-if="errors" :errors="errors" />

        <Dialog
            :show="state.show"
            :title="form ? 'Choisir un candidat' : 'Filtrer par un ou plusieurs Candidats'"
            z-index="z-900"
            max-width="xs"
            @close="state.show = false"
        >
            <ul class="grid bg-gray-100 p-2">
                <li class="flex-1 flex flex-col relative min-h-80">
                    <SearchField
                        v-model="studentsQuery.params.search"
                        class="bg-gray-200 rounded-lg mb-2 !h-9"
                        @change="studentsQuery.fetch()"
                    />
                    <div v-if="studentsQuery.fetching" class="bg-white/30 absolute inset-0 flex-center">
                        <Spinner class="w-5" />
                    </div>

                    <EmptyState v-else-if="!studentsQuery.meta.total" />
                    <RadioField v-else-if="!multi" v-model:full="state.selected" :items="studentsQuery.data" />
                    <template v-else>
                        <MultiCheckField v-model="state.selected" :items="studentsQuery.data" />
                        <Button v-if="studentsQuery.links.next" class="mx-auto" link variant="info" @click="studentsQuery.fetchNext()">
                            Voir plus
                        </Button>
                    </template>
                </li>
            </ul>
           <template #footer>
    <div class="flex flex-col gap-2 w-full">
        <!-- First line: main action buttons -->
        <ButtonGroup
            :actions="[
                {
                    label: 'Annuler',
                    variant: 'secondary',
                    onAction: () => (state.show = false),
                },
                {
                    label: 'Effacer',
                    variant: 'secondary',
                    onAction: () => {
                        emit('change', multi ? [] : null);
                        selectedItem = multi ? [] : null;
                        state.selected = multi ? [] : null;
                        state.show = false;
                    },
                },
                {
                    label: 'Valider',
                    variant: 'success',
                    onAction: () => {
                        const value = JSON.parse(JSON.stringify(state.selected)) as ModelType | UserType[];
                        emit('change', value);
                        selectedItem = value;
                        state.show = false;
                    },
                },
            ]"
        />

        <!-- ✅ Second line: 'Voir plus' + 'Tout afficher' -->
        <div v-if="studentsQuery.links.next" class="flex justify-center gap-2 mt-2">
            <Button variant="info" @click="studentsQuery.fetchNext()">Voir plus</Button>
        </div>
    </div>
</template>

        </Dialog>
    </div>

</template>
