import { router } from '@inertiajs/vue3';
import { getParams } from '@shared/utils';
import { Reactive, reactive } from 'vue';
import { useEvents } from './useEvents';

// Define types for the state and options
interface UseRouteOptions {
    onFinish?: () => void;
    onSuccess?: () => void;
    override?: boolean;
    tableload?: boolean;
}

export type ParamsType<T> = Partial<T> & {
    getAll: (url?: string | null) => Record<string, string>;
    get: (name: string, d?: any) => string;
    set: (updates: Record<string, string | number | null>, options?: UseRouteOptions) => void;
    reload: (options?: UseRouteOptions) => void;
    loading: boolean;

    // [key: string]: string | boolean | ((...args: any[]) => any); // Allow any key to be dynamic (for URL params or functions)
};

// Hook to manage route parameters reactively and integrate with Inertia.js router
export function useRoute<T = Record<string, string>>(_reload: boolean = true): ParamsType<T> {
    // Initialize state with reactive URL parameters and related methods
    const events = useEvents();
    const state: ParamsType<T> = reactive({
        ...(getParams() as T),
        getAll: getParams as (url?: string | null) => Record<string, string>,
        get: (name: string, d?: any): string => (getParams()[name] as string) || d,
        set: update,
        reload: reload,
        loading: false,
    });

    // Function to update the URL parameters
    function update(
        updates: Record<string, string | number | null>,
        { onFinish = () => {}, onSuccess = () => {}, override = false, tableload = false }: UseRouteOptions = {}
    ) {
        const searchParams = new URLSearchParams(override ? '' : window.location.search);
        searchParams.delete('page'); // Remove the "page" parameter by default

        // Update the search parameters based on the provided updates
        for (const key in updates) {
            if (updates[key] !== null && updates[key] !== undefined) {
                searchParams.set(key, String(updates[key])); // Add or update the parameter
            } else {
                searchParams.delete(key); // Remove the parameter if its value is null or undefined
            }
            (state as Record<string, any>)[key] = updates[key];
        }

        // Construct the updated URL and modify the browser's history
        const updatedUrl = searchParams.toString() ? `${window.location.pathname}?${searchParams.toString()}` : window.location.pathname;
        window.history.replaceState({}, '', updatedUrl);

        if (!_reload) return;

        // Set loading state and trigger reload
        state.loading = true;
        reload({ onFinish, onSuccess, tableload });
    }

    // Function to reload the route
    function reload({ onFinish = () => {}, onSuccess = () => {}, tableload = false }: UseRouteOptions = {}) {
        if (tableload) {
            events.emit(events.keys.table.loading, { value: true });
        }
        router.reload({
            replace: true, // Ensures the current page is replaced in the history
            queryStringArrayFormat: 'brackets', // Formatting option for query strings
            onFinish: () => {
                state.loading = false; // Reset loading state after reload
                onFinish(); // Invoke the finish callback
                events.emit(events.keys.table.loading, { value: false });
            },
            onSuccess: onSuccess, // Invoke the success callback if provided
        });
    }

    return state; // Return the reactive state with all associated methods
}
