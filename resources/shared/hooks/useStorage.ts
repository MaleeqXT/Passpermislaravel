import { StorageTypeEnum } from '@common/enums';
import { ref, Ref, readonly } from 'vue';
type UseStorageReturnType<T> = [Ref<T | null>, (newValue: T) => void, (key: string, newValue: any) => void, () => any];

// Utility function to retrieve a stored item from localStorage/sessionStorage
const getItem = (key: string, storage: Storage): any => {
    const value = storage.getItem(key);
    if (!value) {
        return null;
    }
    try {
        return JSON.parse(value);
    } catch (error) {
        return error;
    }
};

// Custom hook to interact with storage (localStorage or sessionStorage)
export const useStorage = <T>(
    key: string = '',
    defaultValue: T | null = null,
    type: StorageTypeEnum = StorageTypeEnum.LOCAL
): UseStorageReturnType<T> => {
    let storage: Storage | null = null;

    switch (type) {
        case StorageTypeEnum.SESSION:
            storage = sessionStorage;
            break;
        case StorageTypeEnum.LOCAL:
        default:
            storage = localStorage;
            break;
    }

    const storageValue = ref<T | typeof defaultValue>(getItem(key, storage) || defaultValue) as Ref<T | null>;

    const setValue = (storage: Storage) => {
        return (newValue: T) => {
            storageValue.value = newValue;
            storage.setItem(key, JSON.stringify(newValue));
        };
    };

    const getStorageValue = (): any => getItem(key, storage);

    const setCustomValue = (key: string, newValue: any) => {
        storage?.setItem(key, JSON.stringify(newValue));
    };

    return [storageValue, setValue(storage) as (v: T) => void, setCustomValue, getStorageValue];
};
