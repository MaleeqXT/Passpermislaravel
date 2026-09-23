export const useDebounce = (wait: number = 300) => {
    return <T extends (...args: any[]) => void>(func: T) => {
        let timeout: ReturnType<typeof setTimeout> | null = null;

        return (...args: Parameters<T>): void => {
            if (timeout) clearTimeout(timeout);

            timeout = setTimeout(() => {
                timeout = null;
                func(...args);
            }, wait);
        };
    };
};
