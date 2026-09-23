import { useAlert } from '@shared/stores';
import axios from 'axios';
import { reactive } from 'vue';

export const fileapi = {
    medias: 'api.media.show.all',
    mediaShow: 'api.media.show',
    mediaStore: 'api.media.store',
    mediaStoreMany: 'api.media.store.many',
    mediaDestroy: 'api.media.destroy',
    mediaDestroyMany: 'api.media.destroy.many',
    mediaDestroyMedia: 'api.media.destroy.media',
};

/**
 * Validates file types before upload.
 */
const allowed = (files: File[], allowedTypes: string[]): boolean => {
    return files.every((file) => allowedTypes.includes(file.type));
};

export function useFiles() {
    const alert = useAlert();
    const loading = reactive({
        deleting: false,
        uploading: false,
    });

    /**
     * Deletes a single media item asynchronously.
     */
    const onDelete = async (id: string | number): Promise<any> => {
        loading.deleting = true;
        loading[id] = true;
        try {
            const res = await axios.delete(route(fileapi.mediaDestroy, id));
            alert.show({
                type: 'success',
                title: 'Media supprimé avec succès',
            });
            return res.data;
        } catch (err) {
            console.error('Error deleting media:', err);
            alert.show({ type: 'error', title: 'Une erreur est survenue' });
            throw err;
        } finally {
            loading.deleting = false;
            loading[id] = false;
        }
    };

    /**
     * Uploads one or multiple files asynchronously.
     */
    const onUpload = async (files: File[], multiple = false): Promise<any> => {
        loading.uploading = true;
        const formData = new FormData();

        if (!multiple) {
            formData.append('media', files[0]);
        } else {
            for (const file of files) {
                formData.append('media[]', file);
            }
        }

        try {
            const { data } = await axios.post(route(multiple ? fileapi.mediaStoreMany : fileapi.mediaStore), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            return data;
        } catch (err) {
            console.error('Error uploading files:', err);
            alert.show({ type: 'error', title: 'Une erreur est survenue' });
            throw err;
        } finally {
            loading.uploading = false;
        }
    };

    return {
        api: fileapi,
        loading,
        delete: onDelete,
        upload: onUpload,
        allowed,
    };
}
