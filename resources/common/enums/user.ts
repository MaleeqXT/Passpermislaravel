import { GlobalListType } from '@common/types';

// Enum for Gearbox Types
export enum GearboxTypeEnum {
    AUTO = 1,
    MANUAL = 0,
}

// Gearbox Types constants
export const GearboxTypes: GlobalListType<GearboxTypeEnum> = {
    [GearboxTypeEnum.AUTO]: {
        id: GearboxTypeEnum.AUTO,
        name: 'Auto',
        sn: 'BA',
        class: 'dark',
    },
    [GearboxTypeEnum.MANUAL]: {
        id: GearboxTypeEnum.MANUAL,
        name: 'Manuel',
        sn: 'BM',
        class: 'default',
    },
};

// Enum for User Roles
export enum UserRoleEnum {
    ADMIN = 1,
    STUDENT = 2,
    MONITOR = 3,
}

// User Roles constants
export const UserRoles: GlobalListType<UserRoleEnum> = {
    [UserRoleEnum.ADMIN]: {
        id: UserRoleEnum.ADMIN,
        name: 'Admin',
    },
    [UserRoleEnum.STUDENT]: {
        id: UserRoleEnum.STUDENT,
        name: 'Student',
    },
    [UserRoleEnum.MONITOR]: {
        id: UserRoleEnum.MONITOR,
        name: 'Monitor',
    },
};

// Enum for Balance Types
export enum BalanceTypeEnum {
    ACTIVE = 1,
    PENDING = 2,
    INACTIVE = 3,
}

// Balance Types constants
export const BalanceTypes: GlobalListType<BalanceTypeEnum> = {
    [BalanceTypeEnum.ACTIVE]: {
        id: BalanceTypeEnum.ACTIVE,
        name: 'Actif',
        class: 'bg-gradient-to-tl from-blue-100 to-blue-50 text-blue-600',
    },
    [BalanceTypeEnum.PENDING]: {
        id: BalanceTypeEnum.PENDING,
        name: 'Pending',
        class: 'bg-gradient-to-tl from-green-100 to-green-50 text-green-600',
    },
    [BalanceTypeEnum.INACTIVE]: {
        id: BalanceTypeEnum.INACTIVE,
        name: 'Inactive',
        class: 'bg-gradient-to-tl from-red-100 to-red-50 text-red-600',
    },
};
