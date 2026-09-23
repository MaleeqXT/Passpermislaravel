import { Ref, ref, reactive, watch, onMounted } from 'vue';
import { defineStore } from 'pinia';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { EvaluationType, ReservationType, UserType } from '@common/types';
import { useSidebar } from './useSidebar';

type DrawerKeys = 'value' | 'settings' | 'notifications' | 'types' | 'rapport' | 'cars-menu' | 'car-documents-menu';

interface IDrawer {
    name: string;
    inner: boolean;
    open: (name?: DrawerKeys) => void;
    show: (name?: DrawerKeys) => boolean;
    close: () => void;
    keys: Record<string, string>;
}

interface ISettings {
    stack: boolean;
    hideBottomBar: boolean;
    width: string;
    padding: string;
    space: 'student' | 'monitor' | 'admin' | 'manager' | '';
}

const DRAWER_KEYS = {
    value: 'value',
    settings: 'settings',
    notifications: 'notifications',
    types: 'types',
    rapport: 'RAPPORT_HOURS',
};

interface IPageProps extends Record<string, any> {
    auth?: {
        user?: UserType;
    };
    adminUser?: any;
    hideBottomBar?: boolean;
    firstReservation?: ReservationType;
}
type AppType = {
    settings: ISettings;
    drawer: IDrawer;
    actions: Ref<string[], string[]>;
    init: (_actions?: string[]) => void;
    logout: () => void;
    user: UserType;
    impersonate: any;
    isLogged: boolean;
    studentContract?: any;
    docs?: {
        eva: EvaluationType;
        fr: ReservationType;
    };
};

export const useApp = defineStore('App', () => {
    const page = usePage<IPageProps>();
    const sidebar = useSidebar();

    // Reactive states
    const actions = ref<string[]>([]);
    const settings = reactive<ISettings>({
        space: '',
        padding: '',
        stack: true,
        hideBottomBar: false,
        width: '',
    });

    // Drawer state
    const drawer = reactive<IDrawer>({
        name: '',
        inner: false,
        open(name = 'value') {
            if (drawer.show(name)) {
                drawer.close();
            } else {
                drawer.name = name;
                drawer.inner = true;
                name === 'value' && (sidebar.isCollapsed = true);
            }
        },
        show(name = 'value'): boolean {
            return drawer.name === name;
        },
        close() {
            if (drawer.inner) {
                sidebar.isCollapsed = false;
            }
            drawer.inner = false;
            drawer.name = '';
        },
        keys: DRAWER_KEYS,
    });

    // Initialization function for actions and badges
    const init = (_actions: string[] = []) => {
        actions.value = _actions;
    };

    // Logout function
   const logout = () => {
        router.post(
            route('logout'),
            {},
            {
                onFinish: () => {
                      setTimeout(() => {
                    // Redirect to login/home page after logout
                    window.location.href = '/login';
                }, 100);
                },
            }
        );
    };

    // On mounted lifecycle hook to set hideBottomBar
    onMounted(() => {
        settings.hideBottomBar = !!page?.props?.hideBottomBar;
    });

    // Watch for changes in the page props to update hideBottomBar
    watch(page, ({ props }) => {
        settings.hideBottomBar = !!props.hideBottomBar;
    });

    return {
        settings,
        drawer,
        actions,
        init,
        logout,
        user: page.props.auth?.user || {},
        impersonate: page.props?.adminUser || null,
        isLogged: !!page.props.auth?.user,
        studentContract: page.props?.studentContract || null,
        docs: page.props.docs || null,
    } as AppType;
});
