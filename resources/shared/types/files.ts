export type StorageMediaType = {
    id: string;
    user_id: number;
    path: string;
    thumb: string;
    name: string;
    type: string;
    is_active: number;
    created_at: string;
    updated_at: string;
};

export type MediaType = {
    id: string;
    user_id: number;
    mediable_type: string;
    mediable_id: string;
    storage_media_id: string;
    created_at: string;
    updated_at: string;
    storage_media: StorageMediaType;
};
