import { onMounted } from 'vue';
import { defineStore } from 'pinia';
import { routes } from '@espace-student/routes';
import { useQuery } from '@shared/hooks';
import { ProposalStatusEnum } from '@common/enums';
import { ProposalType } from '@common/types';

export const useNotifications = defineStore('student/Notifications', () => {
    const proposals = useQuery<Record<string, ProposalType[]>>({
        url: route(routes.api.notifications.proposals.index),
        params: {
            all_dates: true,
            status: ProposalStatusEnum.PENDING,
        },
    });
    const count = useQuery({
        url: route(routes.api.notifications.proposals.count),
        transformable: true,
        dataType: 0,
        init: {
            data: 0,
        },
        params: {
            status: ProposalStatusEnum.PENDING,
        },
    });

    const refresh = () => {
        proposals.params.page = 1;
        proposals.fetch();
    };

    onMounted(() => {});

    return {
        proposals,
        count,
        refresh,
    };
});
