<template>
    <div class="contents">
        <Sidebar class="hidden lg:fixed lg:inset-y-0 lg:z-1 lg:flex lg:w-72 lg:flex-col" />
        <div id="layout" :class="['t-300 md:pb-0 min-h-screen flex flex-col lg:ml-72 relative']">
            <ImiterBar />
            <Alerts type="mobile" />
            <slot />
        </div>
        <MobileBottomBar v-if="!settings.hideBottomBar" :navigation="navigation" others />
        <DrawerMonitor />
        <DocumentsMoniteurDrawer />
        <CarsMenuDrawer />
    </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useAlert, useApp } from '@shared/stores';
import { Alerts } from '@shared/components';
import { Sidebar, DrawerMonitor, DocumentsMoniteurDrawer, CarsMenuDrawer } from '@espace-monitor/components';
import { MobileBottomBar, ImiterBar } from '@common/components';
import { navigation } from '@espace-monitor/constants';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const { settings } = useApp();
const alert = useAlert();
settings.space = 'monitor';
watch(page, ({ props }) => {
    if (props.flash?.success || props.flash?.error) {
        alert.show({
            title: props.flash?.error || props.flash?.success,
            type: props.flash?.error ? 'error' : 'success',
        });
    }
});
</script>
