<script setup lang="ts">
import { onMounted } from 'vue';
import { useScroll } from '@shared/hooks';
import { useApp } from '@shared/stores';
import { Button, Thumb } from '@shared/components';
import { getFilePath } from '@shared/utils';
import { ArrowLeftIcon, NotificationIcon } from '@adersolutions/icons';
import { Head } from '@inertiajs/vue3';
// import type { SizeListType } from '@shared/types';
import { SizeEnum } from '@shared/enums';
import { routes } from '@espace-student/routes';
import { useNotifications } from '@espace-student/stores';
import { ButtonType } from '@shared/types';
const emit = defineEmits(['back']);
type PropsType = Partial<{
    title: string;
    subtitle: string;
    profile: boolean;
    back: boolean | string | object | Function;
    slided: boolean;
    classWrapper: string;
    headerClass: string;
    src: string;
    as: string;
    width: '2xs' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';
    actions: ButtonType[];
    appearance: string;
}>;
const props = withDefaults(defineProps<PropsType>(), {
    profile: true,
    width: 'xl',
    actions: () => [],
    appearance: 'default',
});
const { user, drawer, settings } = useApp();
const { blurStyle } = useScroll();
const { count } = useNotifications();

const widthClass = () => {
    switch (props.width) {
        case 'full':
            return 'max-w-full';
        case '2xs':
            return 'max-w-xl';
        case 'xs':
            return 'max-w-2xl';
        case 'sm':
            return 'max-w-screen-sm';
        case 'md':
            return 'max-w-screen-md md:max-w-screen-lg';
        case 'lg':
            return 'max-w-screen-lg';
        case 'xl':
            return 'max-w-screen-xl';
        default:
            return 'max-w-screen-2xl';
    }
};

const onBack = () => {
    emit('back');

    if (typeof props.back === 'boolean') {
        return history.back();
    } else if (typeof props.back === 'function') {
        return props.back();
    }
};
onMounted(() => {
    settings.width = widthClass();
    if (props.slided) {
        setTimeout(() => {
            window.document.querySelector('.page-slide-up')?.classList?.remove('page-slide-up');
        }, 500);
    }
    if (settings.space === 'student') {
        count.fetch();
    }
});
</script>

<template>
    <div :class="['flex-1 min-h-screen flex flex-col isolate ', 'page-slide-up']">
        <Head :title="as ? title : 'Page'" />
        <header
            :class="['w-full inset-x-0 -top-px z-10  sticky  text-white flex flex-col mx-auto  ', settings.width, headerClass || '']"
            :style="blurStyle"
        >
            <nav class="flex justify-between items-center t-3 py-2 lg:py-3 px-3 relative">
                <ul class="flex items-center gap-2">
                    <li v-if="profile || back" class="flex items-center gap-2">
                        <Button
                            v-if="back"
                            :icon="ArrowLeftIcon"
                            :href="typeof back === 'string' ? back : ''"
                            variant="header"
                            @click="onBack"
                        />
                        <Thumb v-else-if="profile" :src="getFilePath(user)" :size="SizeEnum.XS" class="btn-m" @click="drawer.open()" />
                    </li>
                    <li class="flex-1">
                        <h1 class="t-300 font-semibold drop-shadow-sm text-base md:text-2xl">
                            {{ title }}
                        </h1>
                        <p v-if="subtitle" class="text-sm opacity-70 leading-none first-letter:capitalize">
                            {{ subtitle }}
                        </p>
                    </li>
                </ul>

                <div class="flex gap-2 md:gap-4 items-center print:hidden">
                    <slot name="nav" />
                    <Button v-if="settings.space === 'student'" :href="route(routes.notifications.index)" variant="header">
                        <NotificationIcon class="w-5" />
                        <span class="absolute -top-1 -right-1 bg-primary text-white text-2xs rounded-full w-4 h-4 flex-center">
                            {{ count.data }}
                        </span>
                    </Button>
                </div>
            </nav>
            <slot name="actions" :is-sticky="false" />
            <!-- <div v-if="$slots.header" :class="['mx-auto w-full', settings.width]"> -->
            <slot name="header" />
            <!-- </div> -->
        </header>
        <div v-if="$slots.content">
            <slot name="content" />
        </div>
        <main :class="['bg-gray-100 h-full relative z-20 rounded-t-2xl flex-1 flex flex-col', $slots.sticky ? 'overflow-clip' : '']">
            <div class="h-rainbow"></div>
            <div
                :class="[
                    settings.width,
                    'mx-auto sticky top-0 md:top-2 md:mt-2 w-full z-50 backdrop-blur-xl bg-white/50 bg-rainbow rainbow-opacity-30 overflow-hidden rounded-lg shadow-down',
                ]"
            >
                <slot name="sticky" />
                <div class="h-rainbow"></div>
            </div>
            <div :class="['flex flex-col flex-1 mx-auto w-full', classWrapper || 'px-3', !actions.length && 'pb-20', settings.width]">
                <slot />
            </div>
            <div v-if="actions.length" :class="['page-actions actions-mobile', settings.width]">
                <Button v-for="(action, key) in actions" :key="key" v-bind="action" @click="action.onAction?.()">
                    {{ action.label }}
                </Button>
            </div>
        </main>

        <!-- </Teleport> -->
        <!-- </slot> -->
    </div>
</template>
