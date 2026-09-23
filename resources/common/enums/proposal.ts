import { CheckIcon, ClockIcon, StatusActiveIcon, XIcon } from '@adersolutions/icons';
import { GlobalListType } from '@common/types';

export enum ProposalStatusEnum {
    PENDING = 1,
    CANCELLED,
    RESERVED,
}

export const ProposalLessonStatus: GlobalListType<ProposalStatusEnum> = {
    [ProposalStatusEnum.PENDING]: {
        id: ProposalStatusEnum.PENDING,
        name: 'En attente',
        class: 'secondary',
        desc: 'Cette proposition est en attente de validation',
        icon: ClockIcon,
        order: 1,
    },
    [ProposalStatusEnum.CANCELLED]: {
        id: ProposalStatusEnum.CANCELLED,
        name: 'Refusé',
        icon: XIcon,
        class: 'danger',
        desc: 'Cette proposition a été refusée',
        order: 3,
    },
    [ProposalStatusEnum.RESERVED]: {
        id: ProposalStatusEnum.RESERVED,
        icon: StatusActiveIcon,
        name: 'Réservé',
        desc: 'Cette proposition a été réservée',
        class: 'success',
        order: 4,
    },
};
