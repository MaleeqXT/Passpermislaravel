import axios, { AxiosError } from 'axios';
import { onMounted, reactive, ref } from 'vue';
import type { DataListType } from '@shared/types';

type IUseQueryProps<K> = {
    url?: string;
    dataType?: any;
    params?: K;
    key?: string;
    init?: Record<string, any>;
    transformable?: boolean;
    mounted?: boolean;
    callback?: (data: any) => any;
};
type BaseParamsType = Partial<{ search: string; page: number }>;
export type UseQueryType<T, K = any> = DataListType<T> & {
    params: Partial<K> & Partial<BaseParamsType>;
    fetching: boolean;
    fetchingMore: boolean;
    error: AxiosError | null;
    fetch: (_url?: string, _params?: K, loadmore?: boolean) => Promise<unknown>;
    fetchNext: () => Promise<unknown>;
    reset: () => void;
};

const initialTransformed = (
    init: Record<string, any>,
    transformable: boolean = false,
    key: string = 'data'
): Partial<DataListType<any>> | null => {
    if (transformable) {
        return {
            data: init[key],
            meta: {
                total: init.total || 0,
                current_page: init.current_page || 1,
                per_page: init.per_page || 10,
                links: init.links || [],
                path: init.path,
                from: init.from,
                to: init.to,
            },
            links: {
                first: init.first_page_url || null,
                prev: init.prev_page_url || null,
                next: init.next_page_url || null,
                last: init.last_page_url || null,
            },
        };
    }
    if (Object.keys(init)?.length) {
        return {
            data: init[key],
        };
    }
    return null;
};
export function useQuery<T = any, K = any>(props: IUseQueryProps<K>, isMounted: boolean = false): UseQueryType<T, K> {
    const { url = '', params, key = 'data', init = {}, dataType = [], transformable, callback } = props;
    const initialData = initialTransformed(init, !!transformable, key) || dataType;

    const state = reactive<UseQueryType<T, K>>({
        data: callback ? callback(initialData?.data || dataType) : initialData?.data || dataType,
        links: initialData.links || { first: null, prev: null, next: null, last: null },
        meta: initialData.meta || {
            total: 0,
            current_page: 1,
            per_page: 10,
            links: [],
        },
        params: params || {},
        fetching: false,
        fetchingMore: false,
        error: null,
        fetch: onFetch,
        fetchNext: (): Promise<unknown> => onFetch('', {} as K, true),
        reset: reset,
    });
    // state.params.
    const controller = ref<AbortController | null>(null);

    async function onFetch(_url: string = '', _params: K = {} as K, loadmore: boolean = false): Promise<T | AxiosError> {
        // Early return if already fetching or no more data to load
        if (state.fetching || state.fetchingMore || (loadmore && !state.links.next)) {
            throw {
                message: 'Already fetching or no more data to load',
                name: 'AbortError',
            } as AxiosError;
        }

        // Merge payload with existing params
        const payload = { ...state.params, ..._params };

        if (payload.search === '') {
            delete payload.search;
        }
        if (loadmore) {
            payload.page = (state.meta.current_page || 1) + 1; // Increment page for loadmore
            state.fetchingMore = true;
        } else {
            state.fetching = true;
        }

        // Abort previous request if it exists
        if (controller.value) {
            controller.value.abort();
        }
        controller.value = new AbortController();

        try {
            // Fetch data using axios
            const response = await axios.get(_url || url, {
                params: payload,
                signal: controller.value.signal,
            });

            const { data } = response;

            const newData = callback ? callback(data[key]) : data[key];

            // Update links and meta data
            if (data.links && !data.meta) {
                const transformedData = initialTransformed(data, true) || dataType;
                state.links = transformedData.links || state.links;
                state.meta = transformedData.meta || state.meta;
            } else if (data.links) {
                state.links = data.links;
                state.meta = data.meta;
            }

            // Update state data
            state.data = loadmore ? [...(Array.isArray(state.data) ? state.data : []), ...(newData ?? dataType)] : newData ?? dataType;
            return state.data as T;
            // Call onSuccess callback if provided
        } catch (error) {
            // Handle errors
            console.error('Error fetching data:', error);
            state.error = error as AxiosError;
            return error as AxiosError;
            // Call onError callback if provided
        } finally {
            // Reset fetching states
            state.fetching = false;
            state.fetchingMore = false;
        }
    }
    function reset() {
        state.data = dataType;
        state.meta = {
            total: 0,
            current_page: 1,
            per_page: 10,
            links: [],
        };
        state.params = {};
        state.fetching = false;
        state.fetchingMore = false;
        state.error = null;
    }
    onMounted(() => {
        if (props.mounted || (isMounted && url)) {
            onFetch();
        }
    });

    return state as UseQueryType<T, K>;
}
