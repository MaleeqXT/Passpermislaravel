import { defineAsyncComponent } from 'vue';
// document attestation d'entrée en formation
export const QuestionaireEntrerFormation = defineAsyncComponent(() => import('./qef/QuestionaireEntrerFormation.vue'));
export const QEFDownload = defineAsyncComponent(() => import('./qef/QEFDownload.vue'));

// document attestation d'honorabilité
export const DocumentAttestationHonor = defineAsyncComponent(() => import('./dah/DocumentAttestationHonor.vue'));
export const DAHDownload = defineAsyncComponent(() => import('./dah/DAHDownload.vue'));

// document attestation de fin de formation
export const DocumentAttestationFinFormation = defineAsyncComponent(() =>
    import('./daff/DocumentAttestationFinFormation.vue')
);
export const DAFFDownload = defineAsyncComponent(() => import('./daff/DAFFDownload.vue'));

// document questionnaire de satisfaction
export const QuestionaireSatisfaction = defineAsyncComponent(() => import('./dqs/QuestionaireSatisfaction.vue'));
export const DqsDownload = defineAsyncComponent(() => import('./dqs/DqsDownload.vue'));

// document evaluation de fin de formation
export const DEFFDownload = defineAsyncComponent(() => import('./deff/DEFFDownload.vue'));

// document suivi pro
export const SuiviPro = defineAsyncComponent(() => import('./dsp/SuiviPro.vue'));
export const DSPDownload = defineAsyncComponent(() => import('./dsp/DSPDownload.vue'));
