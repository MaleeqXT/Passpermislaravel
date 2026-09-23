<script setup lang="ts">
import { PageContainer } from '@espace-client/components';
import { HeroSection, OurAgencySection, OurFunctionalitySection, PricingSection, FAQSection, TestimonialsSection } from './partials';
defineProps({});
</script>
<template>
    <PageContainer>
        <HeroSection />
        <OurAgencySection />
        <OurFunctionalitySection />
        <PricingSection />
        <FAQSection />

        <TestimonialsSection />
        <div class="bg-white">
            <img src="/assets/clients/1h-de-conduite-1.webp" class="relative z-10 mx-auto max-w-screen-lg w-full" alt="" />
        </div>
    </PageContainer>
</template>
