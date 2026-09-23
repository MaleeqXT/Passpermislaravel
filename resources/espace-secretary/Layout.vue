<script setup lang="ts">
import { watch, ref } from 'vue';
import { useAlert, useApp } from '@shared/stores';
import { Frame, Drawer } from '@shared/components';
import { DrawerAdmin, Sidebar, Header } from './components/navigations';
import { ImiterBar } from '@common/components';
import { CodesEnum, ErrorsCodes } from '@common/enums';
// const { drawer } = useApp();

const props = defineProps({
    auth: Object,
    flash: Object,
    adminUser: Object,
});

const alert = useAlert();
const sidebarOpen = ref(false);
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
<template>
    <div class="contents">
        <Drawer :show="sidebarOpen" position="left" z-index="z-[999]" @close="sidebarOpen = false">
            <Sidebar class="flex grow flex-col gap-y-5 overflow-y-auto bg-white" />
        </Drawer>
        <!-- Secretary user drawer (avatar menu / logout) -->
        <DrawerAdmin />
        <Frame :header="Header" :sidebar="Sidebar" @sidebar:change="sidebarOpen = $event">
            <ImiterBar />
            <slot />
        </Frame>
    </div>
</template>
