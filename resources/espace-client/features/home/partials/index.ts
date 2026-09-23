import { defineAsyncComponent } from 'vue';

export const HeroSection = defineAsyncComponent(() => import('./HeroSection.vue'));
export const OurAgencySection = defineAsyncComponent(() => import('./OurAgencySection.vue'));
export const OurFunctionalitySection = defineAsyncComponent(() => import('./OurFunctionalitySection.vue'));
export const PricingSection = defineAsyncComponent(() => import('./PricingSection.vue'));
export const FAQSection = defineAsyncComponent(() => import('./FAQSection.vue'));
export const TestimonialsSection = defineAsyncComponent(() => import('./TestmonialsSection.vue'));
export const LoopingSlider = defineAsyncComponent(() => import('./LoopingSlider.vue'));
