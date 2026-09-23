<script setup lang="ts">
import { Popup } from '@shared/components';
import Button from '@shared/components/actions/Button.vue';
import { routes } from '@espace-admin/routes';
import { ref } from 'vue';
import { useAlert } from '@shared/stores';

const alert = useAlert();
const props = defineProps({
    studentBalance: Number,
    commandeBalance: Number,
    orderId: String,
    isDisabled: Boolean,
});
const inProgress = ref(false);

const submit = (close) => {
    inProgress.value = true;
    const data = {
        studentBalance: props.studentBalance,
        commandeBalance: props.studentBalance,
    };
    axios
        .post(route(routes.shop.commandes.refund, props.orderId), data)
        .then((res) => {
            if (res.data) {
                window.location.reload();
            }
            close();
        })
        .catch(() => {
            alert.show({ type: 'error', title: 'an error happend' });
        })
        .finally(() => {
            inProgress.value = false;
        });
};
</script>

<template>
    <Popup :delay="100" :disabled="props.isDisabled" :offset="25" :arrow="false" position="left">
        <slot />
        <template #content="{ close }">
            <div class="grid">
                <h2 class="font-semibold text-lg my-1">MONTANT DE REMBOURCEMENT</h2>
                <div class="bg-white/5 py-2 rounded-lg px-1">
                    <div class="flex justify-between gap-2 text-white/70">
                        <span class="">Balance du forfait :</span>
                        <span class="font-semibold">{{ studentBalance }} H</span>
                    </div>
                    <div class="flex justify-between gap-2 text-white/70">
                        <span>Heure à retire du balance :</span>
                        <span class="font-semibold">{{ commandeBalance }} H</span>
                    </div>
                </div>

                <Button variant="primary" class="self-center px-8 mt-2" :loading="inProgress" @click="submit(close)">
                    Appliquer le remboursement
                </Button>
            </div>
        </template>
    </Popup>
</template>

<style scoped></style>
