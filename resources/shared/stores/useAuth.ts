import { defineStore } from 'pinia';
import { reactive } from 'vue';

// Define the store's state interface
interface AuthState {
    isActiveDialog: boolean;
    shouldReloadCartData: boolean;
    isAuthenticated: boolean;
}

export const useAuth = defineStore('Auth', () => {
    // Reactive state variables
    const state = reactive<AuthState>({
        isActiveDialog: false,
        shouldReloadCartData: false,
        isAuthenticated: false,
    });

    return state;
});
