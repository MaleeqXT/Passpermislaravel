<script setup lang="ts">
import { useApp } from '@shared/stores';

defineEmits(['sidebar:change']);
const props = defineProps({
    header: Object,
    sidebar: Object,
});
const { drawer } = useApp();
const Header = props.header;
const Sidebar = props.sidebar;
</script>
<template>
    <div class="min-h-screen w-full flex">
        <Sidebar />
        <div class="flex-1">
            <Header @sidebar:change="$emit('sidebar:change', $event)" />

            <div class="rainbow absolute top-[57px] w-full md:max-w-xs left-1/2 -translate-x-1/2" />
            <div class="flex min-h-[calc(100vh-60px)] relative">
                <div class="flex-1 bg-gray-100 rounded-t-xl relative shadow-box h-auto">
                    <slot />
                </div>
                <div :class="[drawer.inner ? 'side-bar-slide' : 'w-0', 't-4 sticky top-0 h-dvh overflow-y-auto']">
                    <div id="side-drawer" class="contents"></div>
                </div>
            </div>
        </div>
    </div>
</template>
<style lang="scss">
.side-bar-slide {
    @apply ml-1 w-[22rem];
    width: 22rem;
    // #side-drawer {
    //     @apply min-w-[22rem];
    // }
}
</style>
