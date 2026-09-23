import { ref, reactive, watch } from 'vue';
import { defineStore } from 'pinia';
import { fileapi, useEvents } from '@shared/hooks';
import { FileType } from '@shared/enums';
import { MediaType, StorageMediaType } from '@shared/types';

interface ListState {
    links: Record<string, any>;
    meta: Record<string, any>;
    storageMedia: StorageMediaType[];
}

interface LoadingState {
    upload: boolean;
    delete: boolean;
    takingPhoto: boolean;
}

interface SettingsState {
    showTypeUpload: boolean;
}

export const useFileLibrary = defineStore('FileLibrary', () => {
    // State variables
    const show = ref<boolean>(false); // Visibility of the file manager
    const source = ref<string>(''); // Source of the file manager
    const search = ref<string>(''); // Search input
    const types = ref<FileType[] | null>(null); // File types
    const selectedTab = ref<number>(1); // Active tab index
    const selectedMedia = ref<MediaType[]>([]); // Selected media items
    const errors = ref<Record<string, string>>({}); // Error messages
    const multiple = ref<boolean>(false); // Allow multiple selection
    const fetching = ref(false);

    const event = useEvents(); // Event emitter

    const settings = reactive<SettingsState>({
        showTypeUpload: false, // Upload type menu visibility
    });

    const list = reactive<ListState>({
        links: {}, // Pagination or related links
        meta: {}, // Metadata
        storageMedia: [], // List of media items
    });

    const loading = reactive<LoadingState>({
        upload: false, // Uploading state
        delete: false, // Deleting state
        takingPhoto: false, // Camera activity state
    });

    const onFetch = (loadmore = false, filters: any = {}) => {
        const params = { name: search.value, ...filters };
        if ((loadmore && !list.links?.next) || fetching.value) {
            return;
        }
        if (loadmore) {
            params.page = list.meta?.current_page + 1;
        }
        fetching.value = true;
        axios
            .get(route(fileapi.medias), { params })
            .then(({ data }) => {
                if (data?.storageMedia) {
                    list.storageMedia = loadmore ? [...list.storageMedia, ...data.storageMedia] : data.storageMedia;
                    list.links = data.links;
                    list.meta = data.meta;
                }
            })
            .finally(() => {
                fetching.value = false;
            });
    };

    /**
     * Trigger a "media:submit" event with optional data and the current source.
     * @param data - Optional data to emit with the event.
     */
    const trigger = (data: any = null) => {
        event.emit('media:submit', {
            data,
            source: source.value,
        });
    };

    /**
     * Open the file manager and optionally set the source.
     * @param val - Source identifier (default is an empty string).
     */
    const open = (val: string = '') => {
        errors.value = {};
        show.value = true;
        source.value = val;
    };

    /**
     * Open the upload type menu and optionally set the source.
     * @param val - Source identifier (default is an empty string).
     */
    const openMenu = (val: string = '') => {
        settings.showTypeUpload = true;
        source.value = val;
    };

    /**
     * Close the file manager and reset state variables.
     */
    const close = () => {
        errors.value = {};
        show.value = false;
        source.value = '';
        types.value = null;
        settings.showTypeUpload = false;
    };

    watch(show, (value) => {
        if (value) {
            onFetch(false);
        } else {
            setTimeout(() => {
                selectedMedia.value = [];
            }, 350);
        }
    });
    return {
        // State
        show,
        source,
        search,
        types,
        selectedTab,
        selectedMedia,
        errors,
        multiple,
        settings,
        list,
        loading,
        fetching,
        // Methods
        open,
        openMenu,
        close,
        trigger,
        fetch: onFetch,
        /**
         * Register a callback for the "media:submit" event.
         * @param callback - Function to execute when the event is triggered.
         */
        submit: (callback: (e: any) => void) => {
            event.on('media:submit', (e: any) => {
                callback(e);
                event.off('media:submit', callback);
            });
        },
    };
});
