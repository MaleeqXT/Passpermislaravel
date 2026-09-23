import { ConfettiIcon, PersonIcon, PersonSegmentIcon } from '@adersolutions/icons';

export const conduiteNavigation = [
    { name: 'Leçons', href: '#', icon: PersonIcon, current: true },
    { name: "Livret d'apprentissage", href: '#', icon: ConfettiIcon, current: false },
    { name: 'Passer le permis', href: '#', icon: PersonSegmentIcon, current: false },
];

export const LessonsHeading = [
    { name: 'Moniteur', className: 'text-start px-3 font-normal' },
    { name: 'Date', className: 'text-center px-3 font-normal' },
    { name: 'Heure', className: 'text-center px-3 font-normal' },
    { name: 'Lieu', className: 'text-center px-3 font-normal' },
    { name: 'Action', className: 'text-center px-3 sticky right-0 bg-[#f9fafb] border-l-[1px] border-[#e2e8f0]' },
];
