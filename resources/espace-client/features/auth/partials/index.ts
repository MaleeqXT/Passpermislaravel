import { defineAsyncComponent } from 'vue';
export const RegisterStudentform = defineAsyncComponent(() => import('./RegisterStudentform.vue'));

export const RegisterMoniteurform = defineAsyncComponent(() => import('./RegisterMoniteurform.vue'));
export const ContentFormSection = defineAsyncComponent(() => import('./ContentFormSection.vue'));
export const FormRestPassowrd = defineAsyncComponent(() => import('./FormRestPassowrd.vue'));
