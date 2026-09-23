<template>
    <div class="h-full">
        <ImiterBar />
        <!-- <Sidebar class="h-screen fixed left-0 top-0 bottom-0 w-72 max-lg:hidden" /> -->
        <StudentHeader />
        <div id="layout" class="t-300 h-full">
            <Alerts type="mobile" />
            <slot />
        </div>
        <!-- <VerifiedCredentialDialog route-url="#" /> -->
        <StudentDrawer />
        <LocationDialog />
        <MobileBottomBar v-if="!settings.hideBottomBar" :navigation="navigation" />
    </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { Alerts } from '@shared/components';
import { useAlert, useApp } from '@shared/stores';
import { MobileBottomBar, ImiterBar } from '@common/components';
import { StudentDrawer, Sidebar, LocationDialog } from '@espace-student/components';
import { navigation } from '@espace-student/constants';
import { CodesEnum, ErrorsCodes } from '@common/enums';
import { StudentHeader } from './components';

const props = defineProps({
    auth: Object,
    flash: Object,
});
const { settings } = useApp();
const alert = useAlert();
settings.space = 'student';
watch(props, ({ flash }) => {
    if (flash?.success || flash?.error) {
        let msg: CodesEnum | string = flash?.success || flash?.error;
        if (msg?.startsWith('E0')) {
            msg = ErrorsCodes[msg as CodesEnum];
        }
        alert.show({
            title: msg,
            type: flash.success ? 'success' : 'error',
        });
    }
});
</script>
