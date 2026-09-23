<script setup lang="ts">
import { Button, InputField, Dialog } from '@shared/components';
import { useStorage } from '@shared/hooks';
import { reactive } from 'vue';

const emit = defineEmits(['close']);
defineProps({
    show: Boolean,
});
const [links, setLinks] = useStorage<any[]>('HEADER:LINKS', []);

const state = reactive({
    name: '',
    url: '',
    showMenuLogo: false,
});
const checkEspace = (value = '') => value?.includes(window.location.hostname + '/admin/');
const onClose = (links: any | null = null) => {
    state.name = '';
    state.url = '';
    emit('close', links);
};
const onSubmit = () => {
    const external = window.location.hostname !== new URL(state.url || '').hostname;
    const newLink = {
        name: state.name,
        href: state.url,
        letter: true,
        external: external,
        self: !checkEspace(state.url) && !external,
        custom: true,
    };
    setLinks([...(links.value ?? []), newLink]);
    onClose(newLink);
};
</script>

<template>
    <Dialog :show="show" max-width="xs" title="Ajouter un lien rapid" @close="onClose()">
        <!-- <ul :key="$page.url" class="flex gap-1">
                <template v-for="(item, idx) in items" :key="idx">
                    <li v-if="!item.divider" class="relative group">
                        <component
                            :is="checkEspace(item.href) ? Link : 'a'"
                            :href="item.href"
                            :class="[
                                isActive(item.href) ? 'bg-primary' : 'text-gray-100 hover:bg-primary/20',
                                'p-1 text-2xs font-medium flex-center gap-1 rounded-lg h-8 min-w-8',
                            ]"
                            aria-current="page"
                        >
                            <component :is="item.icon" v-if="item.icon" class="h-5 w-5" />
                            <b v-else-if="item.name" class="md:hidden font-black">{{ getInitials(item.name) }}</b>
                            <span v-if="item.name" class="max-md:hidden">{{ item.shortName || item.name }}</span>
                        </component>
                        <Card
                            v-if="!item.sticky"
                            as="ul"
                            block
                            :separated="false"
                            class="absolute top-full left-1/2 -translate-x-1/2 border mt-1 text-dark w-full min-w-40 opacity-0 invisible group-hover:opacity-100 group-hover:visible t-4 divide-y"
                        >
                            <li
                                class="py-2 !px-3 cursor-pointer flex gap-2 items-center btn btn-link btn-danger btn-sm !text-xs"
                                @click="setLinks(links.filter((link) => link !== item))"
                            >
                                <DeleteIcon class="h-4 w-4" />
                                Supprimer
                            </li>
                        </Card>
                    </li>
                </template>
            </ul>
            <Popup>
                <Button class="btn-header">
                    <PlusIcon class="h-5 w-5 text-white" />
                </Button>
                <template #content="{ close }">
                  
                </template>
            </Popup> -->
        <form @submit.prevent="onSubmit()" class="flex flex-col gap-3 p-3">
            <div class="relative grid gap-3 min-w-80">
                <InputField v-model="state.name" required label="Nom" />
                <InputField v-model="state.url" required type="url" label="URL" />
            </div>
            <p class="text-xs bg-yellow-50 rounded-lg px-3 py-1">
                <b>Note:</b>
                vous pouvez ajouter des filtres et effectuer une recherche à l'aide de paramètres d'URL sur le lien Enregistrer comme
                nouveau pour une utilisation rapide.
            </p>
            <div class="bg-white/5 flex gap-3">
                <Button variant="secondary" type="button" @click="onClose()">Fermer</Button>
                <Button variant="primary" full type="submit" :disabled="!state.url">Créer</Button>
            </div>
        </form>
    </Dialog>
</template>
