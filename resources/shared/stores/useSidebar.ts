import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
const SIDEBAR_STORAGE_NAME = 'SIDEBAR_STORE';

export const useSidebar = defineStore('Sidebar', () => {
    const isCollapsed = ref(!!localStorage.getItem(SIDEBAR_STORAGE_NAME));
    watch(isCollapsed, (value) => {
        value ? localStorage.setItem(SIDEBAR_STORAGE_NAME, String(value)) : localStorage.removeItem(SIDEBAR_STORAGE_NAME);
    });
    return {
        isCollapsed,
    };
});
