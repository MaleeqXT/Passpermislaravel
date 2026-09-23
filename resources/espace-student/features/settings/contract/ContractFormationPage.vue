<script setup lang="ts">
import { PageMobile } from '@common/components';
import { ChevronLeftIcon } from '@adersolutions/icons';
import { computed, ref } from 'vue';
import { useApp } from '@shared/stores';
import { downloadPdf } from '@common/utils';
import { ContratFormationContent } from './partials';

const { studentContract } = useApp();
const contractRef = ref(null);

const attrs = computed(() => ({
    width: 'md',
    classWrapper: '0',
    profile: false,
    variant: 'dark',
    title: 'Contract de formation',
    back: true,
    slided: true,
    actions: [
        {
            label: 'Retour',
            link: true,
            variant: 'dark',
            icon: ChevronLeftIcon,
            onAction: () => history.back(),
        },
        {
            label: 'Télécharger contrat',
            variant: 'warning',
            full: true,
            onAction: () =>
                downloadPdf(contractRef.value, {
                    name: 'contrat de formation',
                    unit: 'px',
                    x: 0,
                    y: 0,
                    width: 450,
                    windowWidth: 900,
                }),
        },
    ],
}));
</script>

<template>
    <PageMobile v-bind="attrs" class="!h-auto mb-20" width="max-w-[900px] mt-5" immediate back>
        <div ref="contractRef" class="block text-[#333]">
            <ContratFormationContent v-if="studentContract" :studentContract="studentContract" />
        </div>
    </PageMobile>
</template>
