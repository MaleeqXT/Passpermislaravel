import { ref } from 'vue';
import { defineStore } from 'pinia';

export interface IAlert {
    type?: 'success' | 'error' | 'info';
    id: number;
    content?: string;
    title: string;
    group?: 'global' | 'local';
    location?: 'inner' | 'default';
}

export const useAlert = defineStore('Alerts', () => {
    // List of alerts
    const list = ref<IAlert[]>([

    ]);

    // Function to dismiss an alert by ID
    const dismiss = (id: number): void => {
        list.value = list.value.filter((t) => t.id !== id);
    };

    // Function to show with an alert
    const show = (data: Omit<IAlert, 'id'>, timeout: number = 10000): void => {
        const id = performance.now(); // Use performance.now() to generate a unique ID
        list.value = [...list.value, { type: 'success', id, group: 'global', ...data }];
        setTimeout(() => {
            dismiss(id);
        }, timeout);
    };

    return {
        list,
        show,
        dismiss,
    };
});
