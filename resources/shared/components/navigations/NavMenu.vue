<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { isActive } from '@shared/utils';
import NavSubMenu from './NavSubMenu.vue';
import { useSidebar } from '@shared/stores';
// import { useDimensions } from '@shared/hooks';

// Define the structure of the item prop
interface NavItem {
    name: string;
    href?: string;
    divider?: boolean;
    grow?: boolean;
    icon?: string | object;
    letter?: boolean;
    external?: boolean;
    self?: boolean;
    disabled?: boolean;
    children?: NavItem[];
}

// Props interface
interface Props {
    item: NavItem;
}

// Define props using TypeScript
const props = defineProps<Props>();

// Sidebar state
const sidebar = useSidebar();
// const { md } = useDimensions();

// Disclosure state
const isDisclosureOpen = ref(false);

// Toggle disclosure
const toggleDisclosure = () => {
    isDisclosureOpen.value = !isDisclosureOpen.value;
};

// Check if any child is active
const hasActiveChild = computed(() => {
    return props.item.children?.some((child) => isActive(child.href)) || isActive(props.item.href);
});

// Determine if a subitem is passed
const hasSelectedPassed = (subItems: NavItem[], index: number): boolean => {
    const activeIndex = subItems.findIndex((subItem) => isActive(subItem.href));
    return activeIndex !== -1 && index < activeIndex;
};
</script>
<template>
    <li v-if="item.divider" :class="['text-white/70 border-t border-white/10 my-1', sidebar.isCollapsed && 'md:hidden']">
        <span :class="['px-4 font-semibold text-2xs pt-3 pb-1 block']">
            {{ item.name }}
        </span>
    </li>
    <li v-else-if="item.grow" class="flex-1"></li>
    <li v-else :class="[sidebar.isCollapsed ? 'flex max-lg:flex-col lg:justify-center' : 'px-3', 'text-gray-100']">
        <div>
            <component
                :is="item.href ? (item.external ?? item.self ? 'a' : Link) : 'div'"
                :href="item.href"
                :target="item.external ? '_blank' : item.self ? '_self' : '-'"
                :class="[
                    isActive(item.href) && 'btn-header font-bold',
                    sidebar.isCollapsed ? ' lg:w-8 lg:h-8 lg:justify-center pl-4 lg:pl-0 w-full mr-4 lg:mr-0' : 'pl-2',
                    'group flex items-center gap-x-2 rounded-md py-1 text-sm leading-6 cursor-pointer active:opacity-75 hover:bg-white/10',
                ]"
                @click="toggleDisclosure"
            >
                <component
                    :is="item.icon"
                    v-if="item.icon"
                    :class="['h-5 w-5 shrink-0 fill-current rounded-lg  ', isActive(item.href) ? 'text-dark bg-white' : '']"
                />
                <small v-else-if="item.letter" class="h-5 w-5 shrink-0 fill-current text-white bg-white/10 rounded-lg flex-center">
                    {{ item.name[0] }}
                </small>
                <span :class="sidebar.isCollapsed ? 'md:hidden max-md:flex-1' : 'flex-1'">{{ item.name }}</span>
            </component>
            <transition name="collapse">
                <ul v-show="isDisclosureOpen || hasActiveChild" v-if="item.children?.length" class="flex flex-col gap-px mt-px h-full">
                    <NavSubMenu
                        v-for="(subitem, index) in item.children"
                        :key="index"
                        :subitem="subitem"
                        :is-passed="hasSelectedPassed(item.children || [], index)"
                        :class="!sidebar.isCollapsed ? '' : 'lg:hidden'"
                    />
                </ul>
            </transition>
        </div>
    </li>
</template>
