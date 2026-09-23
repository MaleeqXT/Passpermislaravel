<script setup lang="ts">
import { computed, ref, useSlots, watch } from 'vue';
import { CheckIcon, SelectIcon, SearchIcon, XIcon } from '@adersolutions/icons';
import { Badge, EmptyState, Popup, Scrollable, Spinner, InfosList, Errors, SearchField } from '@shared/components';
import { ISelectProps } from '@shared/types';
// import { onMounted } from 'vue';

// Define emits interface
interface SelectEmits {
    (event: 'update:modelValue', value: string | object | null): void;
    (event: 'change', value: any): void;
    (event: 'open', value: string): void;
    (event: 'search', value: string): void;
    (event: 'scroll:end'): void;
    (event: 'change:full', value: any): void;
}

const emit = defineEmits<SelectEmits>();

const props = withDefaults(defineProps<ISelectProps>(), {
    keys: () => ['name', 'id'],
    max: 1,
    isFilter: false,
    required: false,
    clear: true,
});

const getDefaultValue = (usedefault = true): any => {
    const df = usedefault ? props.defaultValue : null;
    if (
        typeof props.modelValue === 'boolean' ||
        typeof props.modelValue === 'string' ||
        (typeof props.modelValue === 'number' && !props.multiple)
    ) {
        const data = props.query?.data || props.items || [];
        return data.find((item) => item[props.keys[1]] === props.modelValue) || df || {};
    }
    return df || (props.multiple ? props.modelValue || [] : {});
};
// Computed properties and refs
const hasSlotSingle = computed(() => !!useSlots().default && !props.multiple);
const inputref = ref<HTMLElement | null>(null);
const search = ref<string>('');
const selectedItem = ref<any>(getDefaultValue());

// Utility functions for handling data

const filtredItems = computed(() => {
    const data = props.query?.data || props.items || [];
    if (props.ssr || !props.showSearch || props.query) {
        return data;
    }
    return [...data].filter((value) => {
        if (typeof value === 'number') {
            return value.toString().includes(search.value?.toLocaleLowerCase() || '');
        }
        return value.name.toLocaleLowerCase()?.includes(search.value?.toLocaleLowerCase() || '');
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (value && !props.multiple) {
            const data = props.query?.data || props.items || [];
            const el = data.find((item: Record<string, any>) => item[props.keys[1]] === props.modelValue);
            if (el) {
                selectedItem.value = el;
            }
        } else if (!value && !props.multiple && Object.values(selectedItem.value).length) {
            selectedItem.value = {};
        }
    }
);

watch(selectedItem, (value) => {
    if (props.multiple) {
        emit('update:modelValue', value || []);
        emit('change', value || []);
    } else {
        emit('update:modelValue', value?.[props.keys?.[1]] || null);
        emit('change', value?.[props.keys?.[1]] || null);
    }
    emit('change:full', value);
});

// Event handlers
const handleSearch = (value: string) => {
    emit('search', value);
    if (props.query) {
        props.query.fetch(undefined, { search: value });
    }
};

const showClear = computed(() => {
    if (props.multiple) {
        return selectedItem.value?.length === 0;
    }
    return Object.values(selectedItem.value || {}).length === 0;
});

const isDisabled = (item: any) => {
    if (props.multiple) {
        return (
            Array.isArray(props.modelValue) &&
            props.max <= props.modelValue.length &&
            !props.modelValue.some((x) => x[props.keys[1]] === item?.[props.keys[1]])
        );
    }
    return false;
};

const isSelected = (item: any) => {
    if (props.multiple) {
        return selectedItem.value?.some((v: Record<string, any>) => v[props.keys[1]] === item[props.keys[1]]);
    }
    return item[props.keys[1]] === selectedItem.value?.[props.keys[1]];
};

const handleSelectClick = (item: any, close: () => void) => {
    if (props.multiple) {
        const index = selectedItem.value?.findIndex((v: Record<string, any>) => v[props.keys[1]] === item[props.keys[1]]);
        if (index > -1) {
            selectedItem.value.splice(index, 1);
        } else {
            selectedItem.value.push(item);
        }
        return;
    }
    selectedItem.value = item;
    close();
};

const handleScrollEnd = () => {
    emit('scroll:end');
    if (props.query) {
        props.query.fetch(undefined, { ...props.query.params, search: search.value }, true);
    }
};
watch(
    () => props.onOpened,
    (value) => {
        if (value && props.query) {
            props.query.fetch();
        }
    }
);
</script>

<template>
    <div class="contents">
        <Popup
            :class="['w-full', $attrs.class, isFilter && 'max-w-60']"
            :offset="3"
            :arrow="false"
            :position="position || 'bottom'"
            :disabled="disabled"
            @popover-opened="$emit('open', search)"
        >
            <div
                ref="inputref"
                :class="[
                    inputClass || 'bg-white',
                    isFilter && 'filter-control !h-8 min-w-[12rem] !pt-0 divide-x-0 max-w-60',
                    disabled ? 'pointer-events-none !shadow-none !bg-gray-100' : '',
                    !hasSlotSingle &&
                        'relative w-full px-3 h-[42px] pt-3.5 rounded-md shadow-sm text-sm border border-gray-300 ring-gray-300 focus:ring-primary flex items-center cursor-pointer focus:ring',
                ]"
            >
                <label
                    v-if="label"
                    :class="[
                        'form-label t-2',
                        !isFilter ? 'absolute' : 'pr-1',
                        Object.values(selectedItem || {}).length ? 'top-0.5' : 'top-[11px]',
                        error && 'text-red-600',
                    ]"
                >
                    {{ label }} <span v-if="required" class="text-red-500">*</span>
                </label>

                <b v-if="prefix && Object.values(selectedItem || {}).length">{{ prefix }}:&nbsp;</b>
                <p v-if="Object.values(selectedItem || {}).length && !multiple" class="block truncate" :title="selectedItem?.[keys[0]]">
                    <slot :selected-item="selectedItem">
                        {{ selectedItem?.[keys[0]] }}
                    </slot>
                </p>

                <template v-else-if="multiple && selectedItem.length">
                    <slot name="selectedItems" :selected-items="selectedItem">
                        <Badge
                            v-for="(item, index) in selectedItem"
                            :key="index"
                            dark
                            class="ring-transparent mr-1"
                            :style="{ backgroundColor: item.color }"
                        >
                            {{ item[keys[0]] }}
                        </Badge>
                    </slot>
                </template>
                <!-- <span v-else-if="placeholder" class="block truncate opacity-60">{{ placeholder }}</span> -->

                <div
                    v-if="!disabled && showClear"
                    type="button"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
                >
                    <SelectIcon aria-hidden="true" class="h-5 w-5 text-gray-400" />
                </div>
                <button
                    v-else-if="clear && !disabled"
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-1"
                    @click.stop="selectedItem = multiple ? [] : {}"
                >
                    <XIcon aria-hidden="true" class="h-6 w-6 text-dark00 hover:bg-gray-200 t-3 rounded-lg p-1" />
                </button>
            </div>

            <template #content="{ close }">
                <div
                    class="w-full min-w-64 text-xs focus:outline-none sm:text-sm grid user-select-none rounded-lg overflow-hidden"
                    :style="{
                        width: inputref?.offsetWidth + 'px',
                    }"
                >
                    <div v-if="(showSearch && search === '' && filtredItems.length > 5) || !!search" class="p-2 border-b flex">
                        <SearchField
                            v-model="search"
                            :loading="fetching || query?.fetching"
                            class="bg-slate-200 rounded-lg h-8 flex-1"
                            @change="handleSearch"
                        />
                    </div>
                    <EmptyState
                        v-if="!filtredItems.length && !fetching && !query?.fetching && !query?.fetchingMore && !fetchingMore"
                        :image="SearchIcon"
                        class="md:p-6"
                        heading="Aucun résultat"
                    >
                        <p v-if="emptyState">{{ emptyState }}</p>
                    </EmptyState>
                    <Scrollable v-else class="max-h-60" @scroll:end="handleScrollEnd">
                        <template v-if="!fetching && !query?.fetching">
                            <ul
                                v-for="(item, idx) in filtredItems"
                                :key="idx"
                                :disabled="isDisabled(item)"
                                @click="handleSelectClick(item, close)"
                            >
                                <li
                                    class="relative cursor-default select-none py-2 pl-8 pr-2 hover:bg-primary/10 hover:text-primary text-gray-900 t-2"
                                >
                                    <span :class="[isSelected(item) ? 'font-medium' : 'font-normal', 'block truncate']">
                                        {{ item[keys[0]] }}
                                    </span>
                                    <span v-if="isSelected(item)" class="absolute inset-y-0 left-0 flex items-center pl-2 text-amber-600">
                                        <CheckIcon aria-hidden="true" class="h-5 w-5" />
                                    </span>
                                </li>
                            </ul>
                        </template>
                        <div
                            v-if="ssr || props.query"
                            :class="{
                                '!opacity-100': fetching || fetchingMore || query?.fetching || query?.fetchingMore,
                            }"
                            class="p-2 flex justify-center opacity-0"
                        >
                            <Spinner class="w-5 fill-primary" />
                        </div>
                    </Scrollable>
                </div>
            </template>
        </Popup>
        <InfosList v-if="helperText" :items="helperText" />
        <Errors v-if="error" :errors="error" />
    </div>
</template>
