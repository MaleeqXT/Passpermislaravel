export type EventCallbackType = (...args: any[]) => void;

export interface IEventsKey {
    file: {
        submit: string;
    };
    table: {
        clear: string;
        loading: string;
    };
    schedule: {
        week: string;
        date: string;
    };
}

const eventMap = new Map<string, EventCallbackType[]>();

const EVENTS_KEY: IEventsKey = {
    file: {
        submit: 'media:submit',
    },
    table: {
        clear: 'table:selected:clear',
        loading: 'table:filter:loading',
    },
    schedule: {
        week: 'schedule:change:week',
        date: 'schedule:change:date',
    },
};

export const useEvents = () => {
    const on = (event: string, callback: EventCallbackType = () => {}) => {
        if (!eventMap.has(event)) {
            eventMap.set(event, []);
        }
        eventMap.get(event)?.push(callback);
    };

    const off = (event: string, callback: EventCallbackType = () => {}) => {
        if (!eventMap.has(event)) {
            return;
        }
        const callbacks = eventMap.get(event);
        const index = callbacks?.indexOf(callback);
        if (index !== undefined && index !== -1) {
            callbacks?.splice(index, 1);
        }
    };

    const emit = (event: string, ...args: any[]) => {
        if (!eventMap.has(event)) {
            return;
        }
        const callbacks = eventMap.get(event);
        for (const callback of callbacks!) {
            callback(...args);
        }
    };

    return {
        on,
        off,
        emit,
        keys: EVENTS_KEY,
    };
};
