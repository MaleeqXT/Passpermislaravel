<script setup lang="ts">
import { PageMobile } from '@common/components';
import { reactive, computed } from 'vue';
import {
    QEFDownload,
    QuestionaireEntrerFormation,
    DocumentAttestationHonor,
    DAHDownload,
    DocumentAttestationFinFormation,
    DAFFDownload,
    QuestionaireSatisfaction,
    DqsDownload,
    DEFFDownload,
    SuiviPro,
    DSPDownload,
} from './partials';
import { Badge, Card, Button } from '@shared/components';
import {
    ATTESTATION_FIN_FORMATION,
    ATTESTATION_HONOR,
    DOCUMENT_SUIVI_PRO,
    DOCUMENT_SUIVI_PRO_2,
    QUESTIONS_FORMATION,
    QUESTIONS_SATISFACTION,
} from '@common/enums';
import { dateFormat } from '@shared/utils';
import moment from 'moment-timezone';
const props = defineProps({
    cpf: {
        type: Object,
        default: () => ({}),
    },
});

const disabled = computed(() => ({
    finFormation: moment().isBefore(props.cpf.end_at), // cuurent true cpf_end
    dsp: moment().isBefore(moment(props.cpf.end_at).add(1, 'month')),
    dsp2: moment().isBefore(moment(props.cpf.end_at).add(2, 'month')),
    ah: !props.cpf.document_questionnaire_entre_formation,
}));
const state = reactive({
    selected: null,
});
</script>

<template>
    <div class="contents">
        <PageMobile width="md" class-wrapper=" h-screen mx-auto" :title="cpf?.offer?.name" :profile="false" slided dark back>
            <div class="p-3 flex flex-col gap-5 cpf-doc">
                <Card as="ul" class="flex flex-col !gap-2" title="Documents d'entré en formation">
                    <li :class="['md:flex gap-3 bg-white shadow-sm rounded-lg p-3']">
                        <p class="md:w-2/5">Questionnaire d'entré en formation</p>
                        <Badge
                            class="max-md:my-3"
                            :warning="!cpf.document_questionnaire_entre_formation"
                            :success="!!cpf.document_questionnaire_entre_formation"
                        >
                            {{ cpf.document_questionnaire_entre_formation ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button
                            v-if="!cpf.document_questionnaire_entre_formation"
                            class="btn-pdf"
                            link
                            @click="state.selected = QUESTIONS_FORMATION"
                        >
                            Commencer
                        </Button>
                        <QEFDownload v-else :item="cpf.document_questionnaire_entre_formation" />
                    </li>
                    <li :class="['md:flex gap-3 bg-white shadow-sm rounded-lg p-3', disabled.ah && 'disabled-doc']">
                        <p class="md:w-2/5">Attestation sur l'honneur</p>
                        <Badge
                            class="max-md:my-3"
                            :warning="!cpf.document_attestation_honneur"
                            :success="!!cpf.document_attestation_honneur"
                        >
                            {{ cpf.document_attestation_honneur ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button v-if="!cpf.document_attestation_honneur" class="btn-pdf" link @click="state.selected = ATTESTATION_HONOR">
                            Commencer
                        </Button>
                        <DAHDownload v-else :item="cpf.document_attestation_honneur" />
                    </li>
                </Card>
                <Card
                    as="ul"
                    :class="['flex flex-col !gap-2', disabled.finFormation && 'disabled-doc']"
                    title="Documents de fin de formation"
                >
                    <template #action>
                        <small class="font-semibold">Fin formation: {{ dateFormat(cpf.end_at, 'fr') }}</small>
                    </template>
                    <li class="md:flex gap-3 bg-white shadow-sm rounded-lg p-3">
                        <p class="md:w-2/5">Attestation de fin de formation</p>
                        <Badge
                            class="max-md:my-3"
                            :warning="!cpf.document_attestation_fin_formation"
                            :success="!!cpf.document_attestation_fin_formation"
                        >
                            {{ cpf.document_attestation_fin_formation ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button
                            v-if="!cpf.document_attestation_fin_formation"
                            class="btn-pdf"
                            link
                            @click="state.selected = ATTESTATION_FIN_FORMATION"
                        >
                            Commencer
                        </Button>
                        <DAFFDownload v-else :item="cpf.document_attestation_fin_formation" :cpf="cpf" />
                    </li>
                    <li class="md:flex gap-3 bg-white shadow-sm rounded-lg p-3">
                        <p class="md:w-2/5">Questionnaire de satisfaction</p>
                        <Badge
                            class="max-md:my-3"
                            :warning="!cpf.document_questionnaire_satisfaction"
                            :success="!!cpf.document_questionnaire_satisfaction"
                        >
                            {{ cpf.document_questionnaire_satisfaction ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button
                            v-if="!cpf.document_questionnaire_satisfaction"
                            class="btn-pdf"
                            link
                            @click="state.selected = QUESTIONS_SATISFACTION"
                        >
                            Commencer
                        </Button>
                        <DqsDownload v-else :item="cpf.document_questionnaire_satisfaction" :cpf="cpf" />
                    </li>
                    <li class="md:flex gap-3 bg-white shadow-sm rounded-lg p-3">
                        <p class="md:w-2/5">Evaluation de sortie de formation</p>
                        <Badge class="max-md:my-3" success> Document validé </Badge>
                        <DEFFDownload :cpf="cpf" :student-id="cpf.student_id" />
                    </li>
                </Card>
                <Card as="ul" :class="['flex flex-col !gap-2']" title="Documents de suivi professionnel">
                    <li :class="['md:flex gap-3 bg-white shadow-sm rounded-lg p-3', disabled.dsp && 'disabled-doc']">
                        <p class="md:w-2/5">Questionnaire de suivi de formation</p>
                        <Badge class="max-md:my-3" :warning="!cpf.document_suivi_pro" :success="!!cpf.document_suivi_pro">
                            {{ cpf.document_suivi_pro ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button v-if="!cpf.document_suivi_pro" class="btn-pdf" link @click="state.selected = DOCUMENT_SUIVI_PRO">
                            {{ disabled.dsp ? 'A venir' : 'Commencer' }}
                        </Button>
                        <DSPDownload v-else :item="cpf.document_suivi_pro" :cpf="cpf" />
                    </li>
                    <li :class="['md:flex gap-3 bg-white shadow-sm rounded-lg p-3', disabled.dsp2 && 'disabled-doc']">
                        <p class="md:w-2/5">Questionnaire de suivi de formation</p>
                        <Badge class="max-md:my-3" :warning="!cpf.document_suivi_pro" :success="!!cpf.document_suivi_pro">
                            {{ cpf.document_suivi_pro ? 'Document validé' : 'Compléter mon document' }}
                        </Badge>
                        <Button v-if="!cpf.document_suivi_pro2" class="btn-pdf" link @click="state.selected = DOCUMENT_SUIVI_PRO_2">
                            {{ disabled.dsp2 ? 'A venir' : 'Commencer' }}
                        </Button>
                        <DSPDownload v-else :item="cpf.document_suivi_pro2" :cpf="cpf" />
                    </li>
                </Card>
            </div>
        </PageMobile>
        <QuestionaireEntrerFormation :show="state.selected === QUESTIONS_FORMATION" :cpf="cpf" @close="state.selected = null" />
        <DocumentAttestationHonor :show="state.selected === ATTESTATION_HONOR" :cpf="cpf" @close="state.selected = null" />
        <DocumentAttestationFinFormation :show="state.selected === ATTESTATION_FIN_FORMATION" :cpf="cpf" @close="state.selected = null" />
        <QuestionaireSatisfaction :show="state.selected === QUESTIONS_SATISFACTION" :cpf="cpf" @close="state.selected = null" />

        <SuiviPro
            :key="state.selected || DOCUMENT_SUIVI_PRO"
            :show="state.selected === DOCUMENT_SUIVI_PRO || state.selected === DOCUMENT_SUIVI_PRO_2"
            :type-doc="state.selected"
            :cpf="cpf"
            @close="state.selected = null"
        />
    </div>
</template>
<style lang="scss">
canvas {
    display: block;
    width: 100%;
    height: 100%;
}
.cpf-doc {
    .disabled-doc {
        @apply opacity-50 saturate-0 pointer-events-none;
    }
    .btn.btn-pdf {
        @apply ml-auto;
        @media screen and (max-width: 768px) {
            @apply mr-auto w-full block bg-current py-2;
            span {
                @apply text-white;
            }
        }
    }
}
</style>
