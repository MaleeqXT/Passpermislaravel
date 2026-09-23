export enum CodesEnum {
    RESERVATION_EXISTS = 'E0001',
    UPDATE_RESERVATION_EXISTS = 'E0002',
    RESERVATION_NOT_FOUND = 'E0003',
    INSUFFICIENT_BALANCE = 'E0004',
    RESERVATION_HAS_SCHEDULED = 'E0005',
    SESSION_ALREADY_RESERVED = 'E0006',
    SLOT_ALREADY_TAKEN = 'E0007',
    CANCELLATION_NOT_ALLOWED = 'E0008',
    CANCELLATION_ALREADY_JUSTIFIED = 'E0009',
}

export const ErrorsCodes: Record<CodesEnum, string> = {
    [CodesEnum.RESERVATION_EXISTS]: 'La réservation ou la disponibilité existe déjà.',
    [CodesEnum.UPDATE_RESERVATION_EXISTS]: 'La réservation ou la disponibilité existe déjà.',
    [CodesEnum.RESERVATION_NOT_FOUND]: 'La réservation demandée est introuvable.',
    [CodesEnum.INSUFFICIENT_BALANCE]: 'Votre balance est insuffisant pour effectuer cette action.',
    [CodesEnum.RESERVATION_HAS_SCHEDULED]: 'Une réservation existe déjà pour ce créneau.',
    [CodesEnum.SESSION_ALREADY_RESERVED]: 'Cette proposition ne peut pas être réservée car elle est déjà attribuée.',
    [CodesEnum.SLOT_ALREADY_TAKEN]: 'Le créneau a déjà été réservé.',
    [CodesEnum.CANCELLATION_NOT_ALLOWED]: 'Cette annulation ne peut pas être effectuée.',
    [CodesEnum.CANCELLATION_ALREADY_JUSTIFIED]: 'Cette annulation a déjà été justifiée.',
};
