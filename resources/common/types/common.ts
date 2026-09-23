export type GlobalDataType = {
    [key: string]: any;
    id?: string;
    name?: string;
    created_at?: string;
    updated_at?: string;
    status?: boolean;
    deleted_at?: string | null;
};
export type GlobalObjType<T> = {
    id: T;
    name: string; // 'nom' translated to 'name'
    class?: 'info' | 'success' | 'warning' | 'danger' | 'dark' | 'default'; // 'class' translated to 'classe'
    sn?: string; // 'sn' translated to 'short name'
    order?: number; // 'order' translated to 'ordre'
    content?: string; // 'order' translated to 'ordre'
    desc?: string; // 'order' translated to 'ordre'
    icon?: any; // 'icon' translated to 'icone'
};

export type GlobalListType<T> = Record<any, GlobalObjType<T>>;
export type ArrayListType = GlobalObjType<number | string | unknown>[];
