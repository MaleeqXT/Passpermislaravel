import axios from 'axios';

declare global {
    var route: (name: string, params?: RouteParamsWithQueryOverload | RouteParam, absolute?: boolean) => string;
    interface Window {
        axios: typeof axios;
    }
}
