<script setup lang="ts">
import { ContentFormSection, RegisterMoniteurform } from './partials';
import { PageContainer } from '@espace-client/components';
</script>
<template>
    <PageContainer>
        <section class="container-app flex flex-col lg:flex-row">
            <ContentFormSection />
            <RegisterMoniteurform />
        </section>
    </PageContainer>
</template>
