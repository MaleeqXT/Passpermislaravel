import { CodesEnum, ErrorsCodes } from '@common/enums';
import { useAlert } from '@shared/stores';
import axios, { AxiosResponse } from 'axios';
import { ref, reactive, watch } from 'vue';

type MutationOptions = {
    notify: boolean;
};

type FormType<T> = T & {
    errors: Partial<Record<keyof T, string | string[] | {message: string}>>;
    isDirty: boolean;
    mutating: boolean;
    processing: boolean;
    transform: (callback: (data: T) => Partial<T>) => void;
    mutate: (url: string, method?: 'post' | 'delete' | 'put', payload?: Partial<T>) => Promise<any>;
    post: (url: string) => Promise<any>;
    delete: (url: string) => Promise<any>;
    put: (url: string) => Promise<any>;
    reset: (fieldKey?: keyof T) => void;
    getData: () => T;
    setData: (item: Partial<T>) => void;
    defaults: () => void;
    clear: () => void;
    response: AxiosResponse | null;
};

export function useMutation<T extends Record<string, any>>(data: T = {} as T, options: MutationOptions = { notify: true}) {
    const alert = useAlert();
    const nativeData = JSON.parse(JSON.stringify(data || {})) as T;
    const dataKeys = Object.keys(nativeData) as (keyof T)[];
    const controller = ref<AbortController | null>(null);
    const transformedData = ref<T | null>(null);

    const form = reactive({
        ...(data || {}),
        errors: {
            message: ''
        } as Partial<Record<keyof T, string>>,
        isDirty: false,
        mutating: false,
        processing: false,
        transform,
        mutate,
        post: (url: string) => mutate(url, 'post'),
        delete: (url: string) => mutate(url, 'delete', {}),
        put: (url: string) => mutate(url, 'put'),
        reset,
        getData,
        setData,
        defaults,
        clear,
        response: null as AxiosResponse | null,
    }) as FormType<T>;

    function getData(): T {
        return dataKeys.reduce((acc, key) => {
            acc[key] = form[key];
            return acc;
        }, {} as T);
    }

    function setData(item: Partial<T>) {
        Object.entries(item).forEach(([key, value]) => {
            if (dataKeys.includes(key as keyof T)) {
                (form as T)[key as keyof T] = value as T[keyof T];
            }
        });
    }

    function defaults() {
        const d = getData();
        dataKeys.forEach((key) => {
            nativeData[key] = d[key];
        });
        form.isDirty = false;
    }

    function reset(fieldKey?: keyof T) {
        if (fieldKey) {
            (form as T)[fieldKey] = nativeData[fieldKey];
        } else {
            defaults();
        }
        form.isDirty = false;
    }

    function clear() {
        dataKeys.forEach((key) => {
            (form as T)[key] = '' as T[keyof T];
        });
        defaults();
    }

    function transform(callback: (data: T) => Partial<T> = (data) => data) {
        transformedData.value = callback(getData());
    }

    async function mutate(url: string, method: 'post' | 'delete' | 'put' = 'post', payload?: Partial<T>) {
        form.mutating = true;
        form.processing = true;
        form.errors = {
            message: ''
        };
        form.response = null;

        try {
            if (controller.value) {
                controller.value.abort();
            }

            controller.value = new AbortController();
            const requestData = payload || transformedData.value || getData();

            const { data } = await axios[method](url, requestData, {
                signal: controller.value.signal,
            });

            form.response = data;

            if (options.notify) {
                alert.show({
                    type: 'success',
                    title: data?.message || 'Operation completed successfully',
                });
            }

            return data;
        } catch (err: any) {
            console.log('err', err?.response?.data?.message);
            
            form.errors = err?.response?.data?.errors || {};
            let msg = err?.response?.data?.message || err?.message || 'An error occurred';

            if (err?.response?.data?.message?.startsWith('E')) {
                const errorCode = err?.response?.data?.message as CodesEnum;
                msg = ErrorsCodes[errorCode] || msg;
                form.errors.message = msg;
            }

            alert.show({ type: 'error', title: msg });
            throw err;
        } finally {
            form.mutating = false;
            form.processing = false;
        }
    }

    watch(
        form,
        () => {
            form.isDirty = JSON.stringify(nativeData) !== JSON.stringify(getData());
        },
        { deep: true }
    );

    return form;
}
