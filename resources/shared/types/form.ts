import { ArrayListType } from '@common/types';
import { Placement } from '@popperjs/core';
import { UseQueryType } from '@shared/hooks';

export interface ISelectProps {
    items?: ArrayListType;
    keys?: string[];
    label?: string;
    error?: string | string[];
    defaultValue?: any;
    modelValue: any;
    query?: UseQueryType<any>;
    emptyState?: string;
    prefix?: string;
    inputClass?: string;
    position?: Placement;
    helperText?: Array<string>;
    ssr?: boolean;
    clear?: boolean;
    onOpened?: boolean;
    placeholder?: string;
    disabled?: boolean;
    multiple?: boolean;
    required?: boolean;
    max?: number;
    showSearch?: boolean;
    fetching?: boolean;
    fetchingMore?: boolean;
    isFilter?: boolean;
}
