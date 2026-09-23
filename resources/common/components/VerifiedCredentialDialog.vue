<script setup lang="ts">
import { Button, Dialog, CheckField, PdfViewer } from '@shared/components';
import { useApp } from '@shared/stores';
import { useMutation } from '@shared/hooks';

const props = defineProps({
    src: {
        type: String,
        default: '/assets/termandconditions.pdf',
    },
    routeUrl: String,
});
const { user } = useApp();

const { form, isLoading, mutate } = useMutation({
    is_approved: false,
});

const handleSave = () => {
    mutate(props.routeUrl)
        .then(() => {
            user.approved_credential = { is_approved: 1 };
        })
        .finally(() => {
            user.approved_credential = { is_approved: 1 };
        });
};
</script>

<template>
    <Dialog
        :show="!user.approved_credential?.is_approved"
        max-width="md"
        title="Contract"
        subtitle="Veuillez lire attentivement les termes et conditions avant de continuer."
        blurred
        class-name="!p-2"
        :closeable="false"
    >
        <PdfViewer :src="src" />
        <div class="flex items-center justify-between px-6 py-4 bg-gray-100 sticky bottom-0 z-900">
            <div class="flex gap-2 items-center btn-m">
                <CheckField
                    id="accept-signer"
                    v-model:checked="form.is_approved"
                    label="J’ai lu et j’accepte et signer"
                    name="accept-signer"
                />
            </div>
            <Button class="ml-3" variant="primary" :loading="isLoading" :disabled="!form.is_approved" @click="handleSave">
                Accepter
            </Button>
        </div>
    </Dialog>
</template>
