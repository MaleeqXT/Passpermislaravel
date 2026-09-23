// Define the Enum for forfait IDs
export enum Forfait {
    BOITE_MANUAL = '1',
    CODE = '2',
    BOITE_AUTO = '3',
    EXAMEN = '4',
    NEPH_OR_PERMIS = '5',
}

// Map the forfait IDs to category names
export const categoryNameById: { [key in Forfait]: string } = {
    [Forfait.BOITE_MANUAL]: 'Forfait Code',
    [Forfait.BOITE_AUTO]: 'Forfait Boite Manuelle',
    [Forfait.EXAMEN]: 'Forfait Boite Auto',
    [Forfait.CODE]: 'Forfait Preparation Examen',
    [Forfait.NEPH_OR_PERMIS]: 'Forfait inscription NEPH/Creation Permis',
};

// Define the undefined category as a constant
export const INDIFINI = 'Indéfini';
