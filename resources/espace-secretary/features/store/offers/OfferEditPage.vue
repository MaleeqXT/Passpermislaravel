<script setup lang="ts">
import { Page } from '@shared/components';
import { OfferForm } from './partials';
import type { OfferType } from '@common/types';

type PropsType = {
    offer: OfferType;
};
defineProps<PropsType>();
</script>

<template>
    <Page width="full" padding="none">
        <OfferForm :data="offer" is-edit />
    </Page>
</template>
