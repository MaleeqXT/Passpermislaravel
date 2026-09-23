<template>
    <div v-if="alerts.list.length">
        <!-- Mobile Modal -->
        <div
            v-if="type === 'mobile'"
            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm z-[1000] p-2"
        >
            <BaseAlert
                v-for="alert in alerts.list"
                :key="alert.id"
                class="bg-white rounded-xl shadow-lg max-w-sm w-full relative text-white overflow-hidden page-slide-up"
                :alert="alert"
                :type="type"
            >
                <div class="flex justify-end p-2">
                    <Button variant="secondary" full @click="alerts.dismiss(alert.id)"> Fermer </Button>
                </div>
            </BaseAlert>
        </div>

        <!-- Default Toast -->
        <ul v-else class="w-full relative space-y-2 mb-5">
            <BaseAlert
                v-for="alert in alerts.list"
                :key="alert.id"
                :alert="alert"
                class="relative isolate rounded-xl p-0 overflow-hidden text-white box bg-white"
            />
        </ul>
    </div>
</template>

<script setup lang="ts">
import { useAlert } from '@shared/stores';
import BaseAlert from './BaseAlert.vue';
import { Button } from '../actions';

type PropsType = {
    type?: 'dialog' | 'mobile' | 'default';
};
withDefaults(defineProps<PropsType>(), {
    type: 'default',
});
const alerts = useAlert();
</script>
