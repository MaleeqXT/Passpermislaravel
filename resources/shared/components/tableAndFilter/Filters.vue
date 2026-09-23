<script setup lang="ts">
import { ref, watch, reactive, computed, useSlots, onUnmounted } from 'vue';
import {
    ChevronDownIcon,
    SearchIcon,
    XCircleIcon,
    ThemeEditIcon,
    DeleteIcon,
    RefreshIcon,
    SortIcon,
    FilterIcon,
} from '@adersolutions/icons';
import { router, usePage } from '@inertiajs/vue3';
import { useDebounce, useEvents, useRoute, useStorage } from '@shared/hooks';
import { Button, Popup, Spinner, Dialog, InputField } from '@shared/components';

const emit = defineEmits(['change', 'change:tab', 'change:sort']);
const props = defineProps({
    options: {
        type: Object,
        default: () => ({}),
    },
    defaultTab: [String, Number],
    tabs: {
        type: Array,
        default: () => [],
    },
    keyTab: {
        type: String,
        default: 'status',
    },
    noSearch: Boolean,
    api: Boolean,
    placeholder: String,
});
const events = useEvents();
const page = usePage();
const pathname = window.location.pathname;
const tabsStorageKey = 'tabs-' + pathname;
const debounce = useDebounce(800);
const [storage, setStorage, , getRefreshedValue] = useStorage(tabsStorageKey);
const params = useRoute(!props.api);

// @ts-ignore
const tabsList = ref([...props.tabs, ...(storage.value || [])]);
const tabSelected = ref(params[props.keyTab] || props.defaultTab || '');
const search = ref(params.search);
const showFilters = computed(() => useSlots().default);
const tabState = reactive<{
    show: boolean;
    field: string;
    edit: { id: string; name: string } | null;
    showSearch: boolean;
}>({
    show: false,
    field: '',
    edit: null,
    showSearch: tabsList.value.length === 0,
});

const state = reactive({
    reset: Object.values(params.getAll()).length,
    isloading: false,
    refreshComp: performance.now(),
    isInputFocused: false,
});

const handleAddOrEditTab = () => {
    if (tabState.edit) {
        const storageList = getRefreshedValue() || [];
        // @ts-ignore
        const indexList = tabsList.value.findIndex(({ id }) => id === tabState.edit.id);
        // @ts-ignore
        const indexStorage = storageList.findIndex(({ id }: { id: string }) => id === tabState.edit.id);
        indexList > -1 && (tabsList.value[indexList].name = tabState.field);
        if (indexStorage > -1) {
            storageList[indexStorage].name = tabState.field;
            setStorage(storageList);
        }
    } else {
        const res = {
            name: tabState.field,
            id: location.href,
            isCustom: true,
        };
        setStorage([...(getRefreshedValue() || []), res]);
        tabsList.value = [...tabsList.value, res];
    }
    tabState.show = false;
    tabState.field = '';
    tabState.edit = null;
};
const onDeleteTab = (value: { id: string; name: string }): void => {
    const listStored = getRefreshedValue() || [];
    tabsList.value = tabsList.value.filter(({ id, name }) => id !== value.id && name !== value.name);
    setStorage(listStored.filter(({ id, name }: { id: string; name: string }) => id !== value.id && name !== value.name));
    handlClear();
};
const handleRenameTab = (value: { id: string; name: string }) => {
    tabState.edit = value;
    tabState.show = true;
    tabState.field = value.name;
};
const handlClear = () => {
    tabSelected.value = props.defaultTab || '';
    state.isloading = true;
    search.value = '';
    events.emit('table:filter:loading', { value: true });
    router.get(pathname, undefined, {
        onFinish: () => {
            state.refreshComp = performance.now();
            events.emit('table:filter:loading', { value: false });
            state.isloading = false;
            events.emit('filter:clear');
        },
    });
};
const onSearch = (value: string) => {
    search.value = value;
    if (props.api) {
        emit('change', {
            search: value,
            [props.keyTab]: tabSelected.value,
        });
        return;
    }
    state.isloading = true;
    events.emit('table:filter:loading', { value: true });
    params.set(
        { search: value },
        {
            onFinish: () => {
                state.isloading = false;
                events.emit('table:filter:loading', { value: false });
            },
        }
    );
};
const onDebounceSearch = debounce(onSearch);
watch(
    () => page.props,
    () => {
        state.reset = Object.values(params.getAll()).length;
    }
);
const onTabChange = (tab: any) => {
    tabSelected.value = tab.id;
    search.value = '';

    events.emit('table:selected:clear');
    emit('change', { [tab.key || props.keyTab]: tab.id });
    emit('change:tab', tab.id);
    if (props.api) return;

    state.isloading = true;
    let payload: { [key: string]: any } = {};
    events.emit('table:filter:loading', { value: true });

    if (String(tab.id)?.includes('http')) {
        payload = params.getAll(new URL(tab.id).search);
    } else {
        if (tab.key) {
            payload[props.keyTab] = null;
            payload[tab.key] = tab.id;
        } else {
            // remove old tab.key
            payload[props.keyTab] = tab.id;
            props.tabs.forEach(({ key }: any) => {
                if (key) {
                    payload[key] = null;
                }
            });
        }
    }
    params.set(payload, {
        override: !!tab.key,
        onFinish: () => {
            state.isloading = false;
            events.emit('table:filter:loading', { value: false });
        },
    });
};
const onSort = () => {
    events.emit('table:filter:loading', { value: true });
    const sort = params.sort === 'asc' ? 'desc' : 'asc';
    emit('change:sort', sort);
    params.set(
        { sort },
        {
            onFinish: () => {
                state.isloading = false;
                events.emit('table:filter:loading', { value: false });
            },
        }
    );
};
onUnmounted(() => {
    events.off('table:filter:loading', () => {});
    events.off('filter:clear', () => {});
});
</script>

<template>
    <section :key="state.refreshComp" class="text-sm">
        <div class="flex gap-2 p-1.5 items-center">
            <article v-if="tabState.showSearch" class="flex-1 flex relative items-center rounded-lg">
                <button
                    type="button"
                    class="btn-m flex-center h-7 w-7 rounded-lg btn p-0 absolute left-1 top-1/2 transform -translate-y-1/2"
                    @click="onSearch(search)"
                >
                    <SearchIcon class="w-5" />
                </button>
                <input
                    ref="inputSearchRef"
                    v-model="search"
                    type="text"
                    name="search"
                    autofocus
                    :placeholder="placeholder || 'Entrez des mots-clés pour votre recherche'"
                    class="bg-transparent focus:ring-dark focus:bg-slate-50 block w-full rounded-lg border-none h-8 pr-3 pl-9 focus:ring-2 sm:text-xs sm:leading-6"
                    @focus="state.isInputFocused = true"
                    @blur="state.isInputFocused = false"
                    @input="(event) => onDebounceSearch((event.target as HTMLInputElement).value || '')"
                />
                <Spinner v-if="state.isloading" class="w-4 fill-primary absolute right-2 top-2" />
                <button
                    v-else-if="search"
                    type="button"
                    :class="['absolute right-1.5 top-1.5', !state.isInputFocused && 'opacity-0']"
                    @click="onSearch('')"
                >
                    <XCircleIcon class="h-5" />
                </button>
            </article>
            <article v-else-if="tabsList.length" class="flex-1 flex gap-2 items-center">
                <div class="block relative z-1 flex-1">
                    <nav class="flex gap-x-px tracking-tight" aria-label="Tabs">
                        <div
                            v-for="tab in tabsList"
                            :key="tab.name"
                            :href="tab.href"
                            :class="[
                                tabSelected == tab.id
                                    ? 'bg-gray-200 shadow-inner bg-rainbow rainbow-opacity-30'
                                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50',
                                'rounded-lg px-3 py-1.5 text-xs font-medium flex-center group relative cursor-pointer active:scale-95 t-2 h-8',
                            ]"
                            :aria-current="tabSelected == tab.id ? 'page' : undefined"
                            :style="{ order: tab.order || 0 }"
                            @click="onTabChange(tab)"
                        >
                            {{ tab.name }}
                            <Popup v-if="tab.isCustom" :arrow="false" position="bottom" :offset="16">
                                <ChevronDownIcon
                                    class="h-5 w-0 -mr-3 opacity-0 transition-all group-hover:w-5 group-hover:opacity-100 group-hover:ml-2"
                                />
                                <template #content>
                                    <div class="flex flex-col px-1 py-1 gap-2 min-w-40">
                                        <Button variant="info" link :icon="ThemeEditIcon" @click.stop="handleRenameTab(tab)">
                                            Renommer
                                        </Button>
                                        <Button variant="danger" link :icon="DeleteIcon" @click.stop="onDeleteTab(tab)"> Supprimer </Button>
                                    </div>
                                </template>
                            </Popup>
                        </div>
                    </nav>
                </div>
            </article>

            <Button
                v-if="!noSearch && !tabState.showSearch"
                variant="secondary"
                class="h-8 text-xs"
                :icon="SearchIcon"
                @click="tabState.showSearch = true"
            >
                <FilterIcon class="w-5" />
            </Button>
            <Button v-if="tabState.showSearch && tabsList.length" info link class="text-xs" @click="tabState.showSearch = false">
                Annuler
            </Button>
            <Button v-if="tabState.showSearch && search" variant="secondary" class="text-xs" @click="tabState.show = true">
                Enregistrer
            </Button>
            <Button v-if="state.reset && tabsList.length" variant="secondary" :icon="RefreshIcon" @click="handlClear" />
            <Button variant="secondary" class="h-8 text-xs" :icon="SortIcon" @click="onSort" />
        </div>
        <div class="h-rainbow" />

        <article v-if="showFilters || options.showSlot" class="p-3 flex items-center gap-3 flex-wrap">
            <slot />
        </article>
        <!-- <div class="h-rainbow -translate-y-px saturate-0 opacity-70" /> -->

        <Dialog :show="tabState.show" max-width="sm" title="Enregister tab" @close="tabState.show = false">
            <div class="p-10 border-t bg-white">
                <InputField v-model="tabState.field" label="Nom tab " />
                <p></p>
            </div>

            <div class="flex gap-3 justify-end p-3 border-t">
                <Button variant="secondary" @click="tabState.show = false"> Annuler  </Button>
                <Button variant="dark" :disabled="!tabState.field" @click="handleAddOrEditTab">
                    {{ tabState.edit ? 'Renommer' : 'Ajouter' }}
                </Button>
            </div>
        </Dialog>
    </section>
</template>
