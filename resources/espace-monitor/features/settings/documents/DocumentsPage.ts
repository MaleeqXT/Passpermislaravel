import { SizeEnum } from '@shared/enums';
import { useApp } from '@shared/stores';

export const useDocumentsPersonelMixin = () => {
    const { drawer } = useApp();
    const attrs = {
        width: SizeEnum.XS,
        classWrapper: '!pb-0',
        back: () => {
            history.back();
            drawer.open('cars-menu');
            // drawer.open();
        },
        slided: true,
    };
    return {
        attrs,
    };
};
