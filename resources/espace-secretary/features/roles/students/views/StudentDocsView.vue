<script setup lang="ts">
import { Button, Card, EmptyState } from '@shared/components';
// import { CpfItem } from './partials';
import ViewContainer from './ViewContainer.vue';
import { NoteIcon, ContractIcon } from '@adersolutions/icons';
import { routes } from '@espace-admin/routes';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    studentContract: {
        type: Object,
        default: () => ({}),
    },
    evaluation: {
        type: Object,
        default: () => ({}),
    },
});

// 🔹 CONTRACT URL (safe + fallback)
const getContractUrl = () => {
    const contract = props.studentContract || {};

    const studentId =
        contract?.student?.id ??
        contract?.student_id ??
        contract?.studentId ??
        props.user?.student?.id ??
        props.user?.id ??
        contract?.id;

    if (!studentId) {
        console.warn('No student ID found for contract', { contract, user: props.user });
        alert('❌ Aucun contrat disponible pour ce candidat.');
        return null;
    }

    const contractUrlCandidate =
        contract?.contract_path ??
        contract?.contractPath ??
        contract?.path ??
        contract?.url;

    let url = contractUrlCandidate;

    if (!url) {
        try {
            url = route(routes.users.students.contract, { student: studentId });
        } catch {
            url = `/admin/users/students/${studentId}/contract`;
        }
    }

    return url;
};

const openContract = () => {
    const url = getContractUrl();
    if (url) window.open(url, '_blank');
};

// 🔹 EVALUATION URL
const evaluationUrl = props.evaluation
    ? route(routes.pdf.evaluations, props.evaluation.id)
    : null;

const openEvaluation = () => {
    if (evaluationUrl) window.open(evaluationUrl, '_blank');
};
</script>
<style>
.card_wrap * {
  pointer-events: none;
}

.card_wrap a,
.card_wrap button {
  pointer-events: auto;
}
</style>
<template>
    <ViewContainer :user="user">
        <div class="p-5 max-w-lg mx-auto w-full grid md:grid-cols-2 gap-5">
            <!-- ✅ CONTRACT CARD -->
            <Card block class="cursor-pointer card_wrap" @click="openContract">
                <EmptyState
                    :title="studentContract ? 'Contract disponible' : 'Contrat entre l\'élève et Passpermisfacile'"
                    :class="['pb-4 pt-2', studentContract ? 'text-green-500' : '']"
                    :image="ContractIcon"
                >
                    <p class="font-bold">
                        {{
                            studentContract
                                ? 'Voir le contract de premiere séance'
                                : "Vous n'avez pas encore le contrat pour cet condidat"
                        }}.
                    </p>
                    <Button
                        v-if="studentContract"
                        class="mt-4"
                        variant="success"
                        @click.stop="openContract"
                    >
                        Voir le contrat
                    </Button>
                </EmptyState>
            </Card>
            <!-- ✅ EVALUATION CARD -->
            <Card block class="cursor-pointer card_wrap" @click="openEvaluation">
                <EmptyState
                    :title="evaluation ? 'Fiche d\'evaluation disponible' : 'Aucun Fiche d\'evaluation disponible'"
                    :class="['pb-4 pt-2', evaluation ? 'text-indigo-500' : '']"
                    :image="NoteIcon"
                >
                    <p class="font-bold">
                        {{ evaluation ? "Voir le detail d'evaluations" : "Vous n'avez pas encore une évaluation pour cet condidat" }}.
                    </p>
                    <Button v-if="evaluation" class="mt-4" variant="indigo" @click.stop="openEvaluation">
                        Voir l'évaluation
                    </Button>
                </EmptyState>
            </Card>
        </div>
    </ViewContainer>
</template>
