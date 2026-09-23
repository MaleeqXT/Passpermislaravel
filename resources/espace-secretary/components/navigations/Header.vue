<script setup lang="ts">
import { useApp, useSidebar } from '@shared/stores';
import { getFilePath } from '@shared/utils';
import { Button, Popup } from '@shared/components';
import { SearchIcon, NotificationIcon, LayoutSidebarRightIcon } from '@adersolutions/icons';
import { routes } from '@espace-secretary/routes';
import { reactive } from 'vue';
import { navigation } from '@espace-secretary/enums';
import { computed } from 'vue';

defineEmits(['sidebar:change']);
const { drawer, user, settings } = useApp();
const sidebar = useSidebar();
const state = reactive({
    q: '',
    show: false,
});
const filteredNavigation = computed(() => {
    if (!state.q.trim()) return navigation;
    return navigation
        .flatMap((item) => (item.children ? [item, ...item.children] : [item]))
        .filter((item) => item.name.toLowerCase().includes(state.q.toLowerCase()));
});
const onFocus = () => {
    if (filteredNavigation.value.length) state.show = true;
};

const onBlur = () => {
    setTimeout(() => (state.show = false), 200);
};
</script>

<template>
    <header class="flex w-full bg- text-dark z-0 relative">
        <div
            :class="[
                'grid grid-cols-4 items-center flex-1 mx-auto w-full !py-3 gap-2 t-5',
                settings.padding || 'px-6',
                drawer.inner ? 'max-w-full' : settings.width,
            ]"
        >
            <div class="">
                <Button link variant="primary" class="!h-9 w-9 !p-0" @click="sidebar.isCollapsed = !sidebar.isCollapsed">
                    <LayoutSidebarRightIcon class="h-5 w-5" />
                </Button>
            </div>
            <Popup class="col-span-2 grid flex-1 grid-cols-1 btn-header !p-0 !h-9 relative">
                <input
                    v-model="state.q"
                    type="text"
                    name="search"
                    autocomplete="off"
                    aria-label="Search"
                    class="col-start-1 row-start-1 border-none block bg-transparent pl-8 text-white outline-none rounded-lg text-xs focus:ring-2 focus:ring-primary isolate unset"
                    placeholder="Rechercher les pages..."
                    @focus="onFocus"
                    @blur="onBlur"
                />
                <SearchIcon
                    class="pointer-events-none col-start-1 row-start-1 size-5 self-center ml-1.5 text-gray-500"
                    aria-hidden="true"
                />
                <!-- Dropdown -->
                <!-- v-if="state.show" -->
                <template #content>
                    <div class="absolute left-0 right-0 mt-1 bg-white shadow-lg rounded-lg overflow-hidden max-h-56 overflow-y-auto z-10">
                        <ul>
                            <li
                                v-for="item in filteredNavigation"
                                :key="item.name"
                                class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                            >
                                <a :href="item.href" class="flex items-center gap-2">
                                    <component :is="item.icon" class="w-4 h-4 text-gray-500" v-if="item.icon" />
                                    <small
                                        v-else-if="item.letter"
                                        class="h-5 w-5 shrink-0 fill-current text-white bg-dark rounded-lg flex-center"
                                    >
                                        {{ item.name[0] }}
                                    </small>
                                    {{ item.name }}
                                </a>
                            </li>
                            <li v-if="!filteredNavigation.length">
                                <div class="px-4 py-2 text-sm text-gray-700">Aucun résultat trouvé</div>
                            </li>
                        </ul>
                    </div>
                </template>
            </Popup>

        <div class="flex gap-3 flex-1 justify-end items-center">
  <!-- Approvels button -->
  <Button
    link
    variant="outline"
    class="!h-9 px-4 border border-green-500 text-green-600 font-semibold rounded-lg hover:bg-green-50 transition"
    :href="route('admin.admin.approvels.index')"
  >
    Approvels
  </Button>

  <!-- Notification button -->
  <Button
    link
    variant="primary"
    class="!h-9 w-9 !p-0 ml-2 flex items-center justify-center"
    :href="route(routes.contact.index)"
  >
    <NotificationIcon class="h-5 w-5" />
  </Button>

  <!-- User avatar -->
  <button
    class="flex max-w-xs items-center rounded-lg shadow-box bg-white text-sm focus:outline-none !h-9 w-9 btn-header !p-0"
    @click="drawer.open()"
  >
    <img
      class="size-full rounded-lg"
      :src="getFilePath(user)"
      :alt="user?.name"
    />
  </button>
</div>

        </div>
    </header>
</template>
