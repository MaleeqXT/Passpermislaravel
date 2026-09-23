import { defineAsyncComponent } from 'vue';

export const InputField = defineAsyncComponent(() => import('./InputField.vue'));
export const SearchField = defineAsyncComponent(() => import('./SearchField.vue'));
export const Select = defineAsyncComponent(() => import('./Select.vue'));
export const Switch = defineAsyncComponent(() => import('./Switch.vue'));
export const ColorField = defineAsyncComponent(() => import('./ColorField.vue'));
export const DateField = defineAsyncComponent(() => import('./DateField.vue'));
// export const DateField2 = defineAsyncComponent(() => import('./DateField§.vue'));
export const TabsField = defineAsyncComponent(() => import('./TabsField.vue'));
export const RadioField = defineAsyncComponent(() => import('./RadioField.vue'));
export const TabSwitch = defineAsyncComponent(() => import('./TabSwitch.vue'));
export const CheckField = defineAsyncComponent(() => import('./CheckField.vue'));
export const DateRangepicker = defineAsyncComponent(() => import('./DateRangepicker.vue'));
export const MultiCheckField = defineAsyncComponent(() => import('./MultiCheckField.vue'));
export const EditorField = defineAsyncComponent(() => import('./EditorField.vue'));
export const SliderField = defineAsyncComponent(() => import('./SliderField.vue'));

export const SignField = defineAsyncComponent(() => import('./SignField.vue'));
