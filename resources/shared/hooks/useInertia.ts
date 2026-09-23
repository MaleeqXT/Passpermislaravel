import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

// Define types for the function and its parameters
interface UseInertiaOptions {
    onFinish?: () => void;
    onSuccess?: (res?: any) => void;
    onError?: (res?: any) => void;
    preserveScroll?: boolean;
}

type Form<T extends Record<string, any>> = T & {
    [key: string]: any;
    processing: boolean;
    error: any;
    post: (url?: string, options?: UseInertiaOptions) => void;
    put: (url?: string, options?: UseInertiaOptions) => void;
    delete: (url?: string, options?: UseInertiaOptions) => void;
    getData: () => T;
    setData: (item: T) => void;
    clear: () => void;
};

export function useInertia<T extends Record<string, any>>(data: T = {} as T, route?: string): Form<T> {
    const formKeys = Object.keys(data);

    // Reactive form state
    const form = reactive<Form<T>>({
        ...data,
        processing: false,
        error: null,
        post,
        put,
        delete: onDelete,
        getData,
        setData,
        clear,
    });

    // Get current form data
    function getData(): T {
        return formKeys.reduce((acc, key) => {
            // @ts-expect-error: form[key] might not exist on form
            acc[key] = form[key];
            return acc;
        }, {} as T);
    }

    // Set form data
    function setData(item: Partial<T>): void {
        Object.keys(item).forEach((key) => {
            form[key] = item[key];
        });
    }

    // Clear form data
    function clear(): void {
        formKeys.forEach((key) => {
            form[key] = '';
        });
    }

    // Post method to submit form data
    function post(url?: string, options: UseInertiaOptions = {}): void {
        form.processing = true;
        router.post(url ?? route ?? '', getData(), {
            ...options,
            onFinish: () => {
                form.processing = false;
                options.onFinish?.();
            },
        });
    }

    // Put method to update form data
    function put(url?: string, options: UseInertiaOptions = {}): void {
        form.processing = true;
        router.put(url ?? route ?? '', getData(), {
            ...options,
            onFinish: () => {
                form.processing = false;
                options.onFinish?.();
            },
        });
    }

    // Delete method to remove form data
    function onDelete(url?: string, options: UseInertiaOptions = {}): void {
        form.processing = true;
        router.delete(url ?? route ?? '', {
            ...options,
            onFinish: () => {
                form.processing = false;
                options.onFinish?.();
            },
        });
    }

    return form;
}
